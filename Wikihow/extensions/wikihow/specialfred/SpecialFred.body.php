<?php

class SpecialFred extends UnlistedSpecialPage {

	const FRED_DB_TABLE = 'wikivisual_article_status';

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'Fred' );

		$this->out = $this->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();

		$wgHooks['ShowSideBar'][] = [$this, 'removeSideBarCallback'];
		$wgHooks['ShowBreadCrumbs'][] = [$this, 'removeBreadCrumbsCallback'];
	}

	public function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
	}

	public function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
	}

	private function isUserAllowed(\User $user): bool {
		$permittedGroups = [
			'staff',
			'staff_widget',
			'sysop'
		];

		return $user &&
					!$user->isBlocked() &&
					!$user->isAnon() &&
					count(array_intersect($permittedGroups, $user->getGroups())) > 0;
	}

	public function execute( $subPage ) {
		$this->out->setRobotPolicy( "noindex,follow" );

		if ( $this->user->getId() == 0 ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ( \Misc::isMobileMode() || !$this->isUserAllowed( $this->user ) ) {
			$this->out->setRobotPolicy( 'noindex, nofollow' );
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( !( $this->getLanguage()->getCode() == 'en' || $this->getLanguage()->getCode() == 'qqx' ) ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'setallreviewed' ) {
			$this->setAllReviewed( $this->request );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'removelastweek' ) {
			$this->removeLastWeek( $this->request );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'get_data' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->getData( $this->request );
			print json_encode( $data );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'saveline' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->saveData( $this->request );
			print json_encode( $data );
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'deleterow' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->deleteRow( $this->request );
			print json_encode( $data );
			return;
		}

		$this->out->setPageTitle( wfMessage( 'fred_page_title' )->text() );
		$this->out->addModuleStyles( 'ext.wikihow.specialfred.styles' );
		$this->out->addModules( 'ext.wikihow.specialfred' );

		$html = $this->getMainHTML();
		$this->out->addHTML( $html );
	}

	private function setAllReviewed( $request ) {
		$lang = $request->getVal('lang');
		$dbw = wfGetDB( DB_MASTER );
        $table = self::FRED_DB_TABLE;
		if ( $lang != 'en' ) {
			$table = 'wikidb_' . $lang . "." . $table;
		}

		$values = array( 'reviewed' => 1 );
		$conds = array( 'reviewed' => 0 );
		$dbw->update( $table, $values, $conds, __METHOD__ );
	}

	private function removeLastWeek( $request ) {
		$lang = $request->getVal('lang');
        $timestamp = date(' YmdHis', strtotime( 'today - 1 week' ) );

		$dbw = wfGetDB( DB_MASTER );
        $table = self::FRED_DB_TABLE;
		if ( $lang != 'en' ) {
			$table = 'wikidb_' . $lang . "." . $table;
		}

		$conds = array( "status in ( 20, 30 )", "processed > '$timestamp'" );

		$dbw->delete( $table, $conds, __METHOD__ );
	}

	public function isMobileCapable() {
		return false;
	}

	private function getMainHTML() {
		global $wgActiveLanguages;
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$langs = $wgActiveLanguages;
		array_unshift( $langs, 'en' );
		$vars = [
			'titleTop' => wfMessage( 'fred_page_title' )->text(),
			'addNew' => wfMessage( 'stv_admin_add_new' )->text(),
			'originalFred' => 'Go to Original Fred',
			'setAllReviewed' => 'mark all articles as reviewed',
			'remove20or30lastWeek' => 'remove all entries with status 20 or 30 from past week',
			'downloadCSV' => 'Download CSV',
			'originalFred' => 'Original Fred',
			'original_fred_url' => 'https://fred.wikiknowhow.com/',
			'langs' => $langs,
		];
		$html = $m->render( 'specialfred', $vars );

		return $html;
	}

	private function makeDate( $input ) {
		if ( !$input ) {
			return "";
		}
		$ts = wfTimestamp( TS_UNIX, $input );
		$date = date("Y-m-d", $ts);
		return $date;
	}

	private function cleanWarnings( $text ) {
		if ( !$text ) {
			return "";
		}
		$len = strlen( $text );
		$len = $len / 2;

		$first = trim( substr( $text, 0, $len ) );
		$second = trim( substr( $text, $len ) );
		//var_dump( $first );
		//var_dump( $second );
		if ( $first == $second ) {
			return $first;
		} else {
			return $text;
		}
	}

	private function createArticleLink( $articleName ) {
		$url = str_replace( 'http:', 'https:', $articleName );
		//$articleName = str_replace( 'http://www.wikihow.com/', '', $articleName );
		$link = Html::element( 'a', ['href'=>$url, 'target'=>'_blank'], $articleName );
		return $link;
	}

	private function getDataWithParams( $lang, $limit, $orderBy ) {
		$dbr = wfGetDB( DB_REPLICA );
        $table = self::FRED_DB_TABLE;
		if ( $lang != 'en' ) {
			$table = 'wikidb_' . $lang . "." . $table;
		}
        $var = '*';
        $cond = array();

		$rows = "status, article_id, creator, reviewed, processed, vid_processed, photo_processed, warning, error, article_url, photo_cnt, vid_cnt, replaced, incubation, leave_old_media";
		$sql = "select $rows from (select * from " . $table . " order by processed DESC LIMIT $limit) as w order by $orderBy";

		//$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
		$res = $dbr->query( $sql, __METHOD__ );
		$data = [];

		foreach ( $res as $row ) {
			$row->processed = $this->makeDate( $row->processed );
			$row->vid_processed = $this->makeDate( $row->vid_processed );
			$row->photo_processed = $this->makeDate( $row->photo_processed );
			$row->warning = $this->cleanWarnings( $row->warning );
			$row->article_url = $this->createArticleLink( $row->article_url );
			$data[] = $row;
		}
		$data =(array)$data;

		return $data;
	}

	private function deleteRow( \WebRequest $request ): array {
		$result = array();

		$lang = $request->getVal('lang');
		if ( !$lang ) {
			return $result;
		}

		$pageId = $request->getVal('pageid');
		if ( !$pageId ) {
			return $result;
		}
		$pageId = intval( $pageId );

		$dbw = wfGetDB( DB_MASTER );

        $table = self::FRED_DB_TABLE;
		if ( $lang != 'en' ) {
			$table = 'wikidb_' . $lang . "." . $table;
		}
		$conds = ["article_id" => $pageId];
		$list = $dbw->makeList( $conds, LIST_AND );
		$dbw->delete( $table, $conds, __METHOD__ );
		$result = [];
		return $result;
	}

	private function saveData( \WebRequest $request ): array {
		$result = array();

		$saveData = $request->getArray( 'savedata' );
		if ( empty( $saveData ) ) {
			return $result;
		}
		$lang = $request->getVal('lang');
		if ( !$lang ) {
			return $result;
		}

		$pageId = $request->getVal('pageid');
		if ( !$pageId ) {
			return $result;
		}

		$dbw = wfGetDB( DB_MASTER );

        $table = self::FRED_DB_TABLE;
		if ( $lang != 'en' ) {
			$table = 'wikidb_' . $lang . "." . $table;
		}
		$values = $saveData;
		$conds = array( 'article_id' => $pageId );
		$dbw->update( $table, $values, $conds, __METHOD__ );
		return $saveData;
	}

	private function getData( \WebRequest $request ): array {
		// TODO get some params like from  date or lang
		//$startDate = $request->getVal('start_date');
		$lang = $request->getVal('lang');
		$limit = $request->getInt('shownum');
		$orderBy = $request->getVal('orderby');
		$orderByDirection = $request->getVal('orderbydirection');
		$orderBy = $orderBy . " " . $orderByDirection;

		// first line will be the header
		$firstLine = [
			'status' => 'Status',
			'article_id' => 'PageId',
			'creator' => 'Creator',
			'reviewed' => 'Reviewed',
			'processed' => 'Processed',
			'vid_processed' => 'Vid Processed',
			'photo_processed' => 'Photo Processed',
			'warning'  => 'Warning',
			'error'  => 'Error',
			'article_url' => 'Article URL',
			'photo_cnt' => 'Photos',
			'vid_cnt'  => 'Videos',
			'replaced' => 'Replaced',
			'incubation' => 'Incubation',
			'leave_old_media' => 'Leave Old',
		];

		$data = [$firstLine];
		$data = array_merge( $data, $this->getDataWithParams( $lang, $limit, $orderBy ) );

		return $data;
	}

}
