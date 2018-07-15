<?php
/**
 */

/**
 *
 */
if( !defined( 'MEDIAWIKI' ) )
	die( 1 );

/**
 * Special handling for image description pages
 *
 * @addtogroup Media
 */
class VideoPage extends Article {

	var $mTitle = null;
	function __construct( $title) {
		$this->mTitle = $title;
	}

	/**
	 * Handler for action=render
	 * Include body text only; none of the image extras
	 */
	function render() {
		global $wgOut;
		$wgOut->setArticleBodyOnly( true );
		$wgOut->addSecondaryWikitext( $this->getContent() );
	}

	function view() {
		global $wgOut, $wgShowEXIF, $wgRequest, $wgUser;

		$diff = $wgRequest->getVal( 'diff' );
		$diffOnly = $wgRequest->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );

		if ( $this->mTitle->getNamespace() != NS_VIDEO || ( isset( $diff ) && $diffOnly ) )
			return Article::view();


		# No need to display noarticletext, we use our own message, output in openShowImage()
		if ( $this->getID() ) {
			Article::view();
		} else {
			# Just need to set the right headers
			$wgOut->setStatusCode(404);
			$wgOut->setArticleFlag( true );
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->setPageTitle( $this->mTitle->getPrefixedText() );
			//$this->viewUpdates();
		}

		# Show shared description, if needed
		if ( $this->mExtraDescription ) {
			$fol = wfMessage( 'shareddescriptionfollows' )->plain();
			if( $fol != '-' && !wfMessage( 'shareddescriptionfollows' )->isBlank() ) {
				$wgOut->addWikiText( $fol );
			}
			$wgOut->addHTML( '<div id="shared-image-desc">' . $this->mExtraDescription . '</div>' );
		}

		$this->videoLinks();
		$this->videoHistory();

		if ( $showmeta ) {
			global $wgStylePath, $wgStyleVersion;
			$expand = htmlspecialchars( wfEscapeJsString( wfMessage( 'metadata-expand' )->text() ) );
			$collapse = htmlspecialchars( wfEscapeJsString( wfMessage( 'metadata-collapse' )->text() ) );
			$wgOut->addHTML( Xml::element( 'h2', array( 'id' => 'metadata' ), wfMessage( 'metadata' )->text() ). "\n" );
			$wgOut->addWikiText( $this->makeMetadataTable( $formattedMetadata ) );
			$wgOut->addHTML(
				"<script type=\"text/javascript\" src=\"$wgStylePath/common/metadata.js?$wgStyleVersion\"></script>\n" .
				"<script type=\"text/javascript\">attachMetadataToggle('mw_metadata', '$expand', '$collapse');</script>\n" );
		}
	}

	/**
	 * Create the TOC
	 *
	 * @access private
	 *
	 * @param bool $metadata Whether or not to show the metadata link
	 * @return string
	 */
	function showTOC( $metadata ) {
		global $wgLang;
		$r = '<ul id="filetoc">
			<li><a href="#file">' . $wgLang->getNsText( NS_VIDEO ) . '</a></li>
			<li><a href="#filehistory">' . wfMessage( 'filehist' ) . '</a></li>
			<li><a href="#filelinks">' . wfMessage( 'imagelinks' ) . '</a></li>' .
			($metadata ? ' <li><a href="#metadata">' . wfMessage( 'metadata' ) . '</a></li>' : '') . '
		</ul>';
		return $r;
	}

	/**
	 * Make a table with metadata to be shown in the output page.
	 *
	 * FIXME: bad interface, see note on MediaHandler::formatMetadata(). 
	 *
	 * @access private
	 *
	 * @param array $exif The array containing the EXIF data
	 * @return string
	 */
	function makeMetadataTable( $metadata ) {
		$r = wfMessage( 'metadata-help' )->text() . "\n\n";
		$r .= "{| id=mw_metadata class=mw_metadata\n";
		foreach ( $metadata as $type => $stuff ) {
			foreach ( $stuff as $v ) {
				$class = Sanitizer::escapeId( $v['id'] );
				if( $type == 'collapsed' ) {
					$class .= ' collapsable';
				}
				$r .= "|- class=\"$class\"\n";
				$r .= "!| {$v['name']}\n";
				$r .= "|| {$v['value']}\n";
			}
		}
		$r .= '|}';
		return $r;
	}

	/**
	 * Overloading Article's getContent method.
	 * 
	 * Omit noarticletext if sharedupload; text will be fetched from the
	 * shared upload server if possible.
	 */
	function getContent() {
		if( $this->img && !$this->img->isLocal() && 0 == $this->getID() ) {
			return '';
		}
		return Article::getContent();
	}

	function getUploadUrl() {
		$uploadTitle = SpecialPage::getTitleFor( 'Upload' );
		return $uploadTitle->getFullUrl( 'wpDestFile=' . urlencode( $this->img->getName() ) );
	}

	/**
	 * Print out the various links at the bottom of the image page, e.g. reupload,
	 * external editing (and instructions link) etc.
	 */
	function uploadLinksBox() {
		global $wgUser, $wgOut;

		if( !$this->img->isLocal() )
			return;

		$wgOut->addHtml( '<br /><ul>' );
		
		# "Upload a new version of this file" link
		if( UploadForm::userCanReUpload($wgUser,$this->img->name) ) {
			$ulink = Linker::makeExternalLink( $this->getUploadUrl(), wfMessage( 'uploadnewversion-linktext' )->text() );
			$wgOut->addHtml( "<li><div class='plainlinks'>{$ulink}</div></li>" );
		}
		
		# External editing link
		$elink = Linker::link( $this->mTitle, wfMessage( 'edit-externally' )->text(), array(), 'action=edit&externaledit=true&mode=file' );
		$wgOut->addHtml( '<li>' . $elink . '<div>' . wfMessage( 'edit-externally-help' )->parseAsBlock() . '</div></li>' );
		
		$wgOut->addHtml( '</ul>' );
	}

	function closeShowImage()
	{
		# For overloading

	}

	/**
	 * If the page we've just displayed is in the "Image" namespace,
	 * we follow it with an upload history of the image and its usage.
	 */
	function videoHistory()
	{
		global $wgUser, $wgOut, $wgLang;

		$dbr = wfGetDB(DB_SLAVE);

		$wgOut->addHTML( Xml::element( 'h2', array( 'id' => 'filehistory' ), wfMessage( 'filehist' ) ));
		$wgOut->addHTML("<table width='100%'>
			<tr><td><b>Preview</b></td>
			<td><b>User</b></td>
			<td><b>When</b></td></tr>");

		$res = $dbr->query(
				"SELECT rev_id, rev_user, rev_user_text, rev_timestamp FROM revision 
				WHERE rev_page = {$this->mTitle->getArticleID()}
				ORDER BY rev_timestamp DESC"
			);
		while ($row = $dbr->fetchObject($res)) {
			$r = Revision::newFromId($row->rev_id);
			$u = User::newFromName($row->rev_user_text, false);
			$uurl = "";
			$name = "";
			if ($u) {
				$up = $u->getUserPage();
				$uurl= $up->getFullURL();
				$name = $u->getName();
			}
			$ts = $wgLang->timeanddate($row->rev_timestamp, true, true);
			$wgOut->addHTML("<tr>" 
					. "<td valign='top'>" . $wgOut->parse($r->getText())  . "</td>\n"
					. "<td valign='top'><a href='{$uurl}'>{$name}</a></td>" 
					. "<td valign='top'> {$ts} </td>\n"
					. "</tr>");
		}
		
		$wgOut->addHTML("</table>");

	}

	function videoLinks() {
		global $wgUser, $wgOut;

		$wgOut->addHTML( Xml::element( 'h2', array( 'id' => 'filelinks' ), wfMessage( 'imagelinks' ) ) . "\n" );

		$dbr = wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );
		$templatelinks = $dbr->tableName( 'templatelinks' );

		$sql = "SELECT page_namespace,page_title FROM $templatelinks,$page WHERE tl_title=" .
		  $dbr->addQuotes( $this->mTitle->getDBkey() ) . " AND tl_namespace = " . NS_VIDEO . " AND tl_from=page_id";
		$sql = $dbr->limitResult($sql, 500, 0);
		$res = $dbr->query( $sql, "VideoPage::videoLinks" );

		if ( 0 == $dbr->numRows( $res ) ) {
			$wgOut->addHtml( '<p>' . wfMessage( "nolinkstoimage" )->text() . "</p>\n" );
			return;
		}
		$wgOut->addHTML( '<p>' . wfMessage( 'linkstoimage' )->text() .  "</p>\n<ul>" );

		while ( $s = $dbr->fetchObject( $res ) ) {
			$title = Title::MakeTitle( $s->page_namespace, $s->page_title );
			$link = Linker::link( $title );
			$wgOut->addHTML( "<li>{$link}</li>\n" );
		}
		$wgOut->addHTML( "</ul>\n" );
	}

	/**
	 * Display an error with a wikitext description
	 */
	function showError( $description ) {
		global $wgOut;
		$wgOut->setPageTitle( wfMessage( "internalerror" )->text() );
		$wgOut->setRobotpolicy( "noindex,nofollow" );
		$wgOut->setArticleRelated( false );
		$wgOut->enableClientCache( false );
		$wgOut->addWikiText( $description );
	}

}

