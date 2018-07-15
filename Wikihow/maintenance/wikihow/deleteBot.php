<?php

require_once __DIR__ . '/../Maintenance.php';
/** 
  * The delete bot deletes stuff marked for deletion by the algorithm.
  */
class DeleteBot extends Maintenance {

	// Which algorithm are we using
	const ALGORITHM_NUMBER="5";
	// Minimum score for us to delete the article
	const MIN_DSCORE=".97";

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Deletes pages marked for deletion by algorithm";
    }
	
	function getExcludeList() {
		$list = ConfigStorage::dbGetConfig('delete-article-user-exclude-list');	
		$excludes = preg_split('@ *[\r\n]+@', $list);
		$ids = array();
		foreach ( $excludes as $exclude ) {
			$u = false;
			if( is_int( $exclude ) ) {
				$u = User::newFromId($exclude);
			}
			else {
				$u = User::newFromName($exclude);	
			}
			if( !$u || $u->getId() == 0 ) {
                $to = new MailAddress("gershon@wikihow.com");
                $from = new MailAddress("alerts@wikihow.com");
                $subject = "Delete bot exclude list error";
				$errors = "The following user on the exclude list was not found: \"" . $exclude . "\" Article delete bot was aborted."; 
                UserMailer::send($to,$from, $subject, $errors);
				die("Aborted because user: " . $exclude . "\nin exclude list was not found\n");
	
			}
			$ids[] = $u->getId();
		}
		return $ids;
	}

	/** 
	 * Called command line.
	 */
	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$user = User::newFromName( 'NewArticleCleanupBot' );
		$reason = wfMessage( 'auto_delete_reason' )->plain();
		$userIdExcludeList = $this->getExcludeList();
		
		$res = $dbr->select( 'auto_nfd', array('an_page_id'),array('an_dscore >= ' . self::MIN_DSCORE, 'an_algorithm' => self::ALGORITHM_NUMBER, 'an_timestamp > date_sub(now(), interval 2 hour)'), __METHOD__ );
		foreach ( $res as $row ) {
			$t = Title::newFromId( $row->an_page_id );
			if ( $t && $t->exists() ) {
			
				// Check for inuse and that it isn't written by a top contributor
				// The list should already exclude inuse articles, but we double-check here just in case.

				$feUserId = $dbr->selectField( 'firstedit', 'fe_user', array('fe_page' => $t->getArticleId()) );
				$r = Revision::newFromTitle( $t );

				if ( !preg_match( "@{{inuse@i", $r->getText(), $matches ) 
					&& !in_array( $feUserId, $userIdExcludeList ) ) {
					$p = WikiPage::factory( $t );
					print( "Deleting article: " . $t->getText() . "\n" ); 
					$p->doDeleteArticle( $reason, false, 0, false, $error, $user );
				}
				else {
					print( $t->getText() . " excluded from deletion for being inuse or written by an excluded contributor\n" );
				}
			}
		}
	}
}

$maintClass = "DeleteBot";
require_once RUN_MAINTENANCE_IF_MAIN;
