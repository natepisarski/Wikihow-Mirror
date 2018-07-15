<?php
/**
 * Undeletes a list of videos that may have been accidentally removed
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 * @author Lojjik Braughler
 */

require_once "../../Maintenance.php";

class RestoreVideos extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Undelete a list of videos";
		$this->addArg( 'filename', 'File to read from' );
		$this->addOption( 'test', 'Whether to actually make edits or just test.', false, false, 't' );
	}

	public function execute() {
		global $wgUser;
		$videosDone = 0;

		$wgUser = User::newFromName( 'Vidbot' );
		$filename = $this->getArg(); // should contain newline separated db keys

		$dbKeys = explode("\n", file_get_contents($filename));

		foreach ( $dbKeys as $dbKey ) {

			$video = Title::makeTitle( NS_VIDEO, $dbKey );

			// Undelete the video
			$this->output( "Undeleting " . $video->getFullText() . "\n" );
			if ( !$this->hasOption( 'test' ) )  {
				$archive = new PageArchive($video);
				$archive->undelete( array(), "restoring removed video" );
				$this->output( "\tVideo restored.\n" );
			}

			$article = Title::makeTitle( NS_MAIN, $dbKey );
			
			// Try to find our revision id

			$dbr = wfGetDB(DB_SLAVE);

			$res = $dbr->select( 'revision', array( '*' ), array( 'rev_user' => $wgUser->getId(),
																	'rev_page' => $article->getArticleID(),
																	'rev_comment' => "Removing unavailable video from article"
																), __METHOD__
			);

			foreach ( $res as $row ) {
				$revision = Revision::newFromRow($row);
			}

			if ( !$revision ) {
				$this->output( "\t[WARNING] Couldn't find our revision for undo\n" );
				continue;
			}

			$this->output( "\tFound revision to undo (revid=" . $revision->getId() . ")" . "\n" ); 

			$oldrev = $article->getPreviousRevisionID($revision->getId());

			if ( !$oldrev ) {
				$this->output( "\t[WARNING] Unable to find old revision. Aborting undo.\n" );
				continue;
			}

			$this->output( "\tFound previous revision (revid=$oldrev)\n");

			// Undo the revision
			$wp = WikiPage::factory( $article );

			if ( !$wp->exists() ) {
				$this->output("\tCan't restore text because article doesn't exist. It might have been deleted.");
				continue;
			}

			$oldContents = $wp->getUndoContent($revision, Revision::newFromId($oldrev));


			if ( !$oldContents ) {
				$this->output( "\tUnable to fetch contents. Aborting.\n" );
				continue;
			}
			// Save the edit

			if ( !$this->hasOption( 'test' ) ) {
				$wp->doEditContent($oldContents, "restoring removed video");
				$videosDone++;
			}

		}
	}

}

$maintClass = "RestoreVideos";
require_once RUN_MAINTENANCE_IF_MAIN;