<?php

class SpamDiffTool extends SpecialPage {

	public function __construct() {
		parent::__construct('SpamDiffTool');
		$this->setListed(false);
	}

	public function execute($par) {
		global $wgContLang, $wgSpamBlacklistArticle, $wgScript;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$title = Title::newFromDBKey($req->getVal('target'));
		$diff = $req->getVal( 'diff2' );
		$rcid = $req->getVal( 'rcid' );
		$rdfrom = $req->getVal( 'rdfrom' );

		$out->setHTMLTitle(wfMessage('pagetitle', 'Spam Tool'));

		// can the user even edit this?
		$sb = Title::newFromDBKey($wgSpamBlacklistArticle);
		if (!$sb->userCan('edit')) {
			$out->addHTML(wfMessage('spamdifftool_cantedit'));
			return;
		}

		// do the processing
		if ($req->wasPosted() ) {

			if ( $req->getVal('confirm') ) {
				$t = Title::newFromDBKey($wgSpamBlacklistArticle);
				$article = new Article($t);
				$text = ContentHandler::getContentText( $article->getPage()->getContent() );

				// insert the before the <pre> at the bottom  if there is one
				$i = strrpos($text, "</pre>");
				if ($i !== false) {
					$text = substr($text, 0, $i)
							. $req->getVal('newurls')
							. "\n" . substr($text, $i);
				} else {
					$text .= "\n" . $req->getVal('newurls');
				}
				$watch = false;
				if ($user->getID() > 0) {
					$watch = $user->isWatched($t);
				}
				$article->updateArticle($text, wfMessage('spamdifftool_summary'), false, $watch);
				$returnto = $req->getVal('returnto');
				if ($returnto) {
					$out->redirect($wgScript . "?" . urldecode($returnto) ); // clear the redirect set by updateArticle
				}
				return;
			}
			$vals = $req->getValues();
			$text = '';
			$urls = array();
			$source = wfMessage( 'top_level_domains' )->inContentLanguage()->text();
			$tlds = explode("\n", $source);

			foreach ($vals as $key=>$value) {
				if (strpos($key, "http://") === 0) {
					$url = str_replace("%2E", ".", $key);
					if ($value == 'none') continue;
					switch ($value) {
						case 'domain':
							$t = "";
							foreach ($tlds as $tld) {
								if (preg_match("/" . $tld . "/i", $url)) {
									$t = $tld;
									$url = preg_replace("/" . $tld . "/i", "", $url,  1);
									break;
								}
							}
							$url = preg_replace("@^http://([^/]*\.)?([^./]+\.[^./]+).*$@", "$2", $url);
							$url = str_replace(".", "\.", $url); // escape the periods
							$url .= $t;
							break;
						case 'subdomain':
							$url = str_replace("http://", "", $url);
							$url = str_replace(".", "\.", $url); // escape the periods
							$url = preg_replace("/^([^\/]*)\/.*/", "$1", $url); // trim everything after the slash
							break;
						case 'dir':
							$url = str_replace("http://", "", $url);
							$url = preg_replace("@^([^/]*\.)?([^./]+\.[^./]+(/[^/?]*)?).*$@", "$1$2", $url); // trim everything after the slash
							$url = preg_replace("/^(.*)\/$/", "$1", $url); // trim trailing / if one exists
							$url = str_replace(".", "\.", $url); // escape the periods
							$url = str_replace("/", "\/", $url); // escape the slashes
							break;
					}
					if (!isset($urls[$url])) {
						$text .= "$url\n";
						$urls[$url] = true;
					}
				}
			}
			if (trim($text) == '') {
				$out->addHTML( wfMessage('spamdifftool_notext', $wgScript . "?" . urldecode($req->getVal('returnto') )));
				return;
			}
			$out->addHTML("<form method=POST>
					<input type='hidden' name='confirm' value='true'>
					<input type='hidden' name='newurls' value=\"" . htmlspecialchars($text) . "\">
					<input type='hidden' name='returnto' value=\"" . htmlspecialchars($req->getVal('returnto')) . "\">
				");
			$out->addHTML(wfMessage('spamdifftool_confirm') . "<pre style='padding: 10px'>$text</pre>");
			$out->addHTML("</table><input type=submit value=\"" . htmlspecialchars(wfMessage('submit')) . "\"></form>");
			return;
		}

		if ( !is_null( $diff ) ) {
			// Get the last edit not by this guy
			$current = Revision::newFromTitle( $title );
			$dbw = wfGetDB(DB_MASTER);
			$currentUser = (int)$current->getUser();
			$user_text = $dbw->addQuotes( $current->getUserText() );
			$s = $dbw->selectRow( 'revision',
				array( 'rev_id', 'rev_timestamp' ),
				array(
					'rev_page' => $current->getPage(),
					"rev_user <> {$currentUser} OR rev_user_text <> {$user_text}"
				), $fname,
				array(
					'USE INDEX' => 'page_timestamp',
				'ORDER BY'  => 'rev_timestamp DESC' ) );
			if ($s) {
				// set oldid
				$oldid = $s->rev_id;
			}

			if ($req->getVal('oldid2') < $oldid)
				$oldid = $req->getVal('oldid2');

			$de = new DifferenceEngine( $title, $oldid, $diff, $rcid );
			$de->loadText();
			$otext = $de->mOldtext;
			$ntext = $de->mNewtext;
			$ota = explode( "\n", $wgContLang->segmentForDiff( $otext ) );
			$nta = explode( "\n", $wgContLang->segmentForDiff( $ntext ) );
			$diffs = new Diff($ota, $nta);
			foreach ($diffs->edits as $edit) {
				if ($edit->type != 'copy' && $edit->closing != "") {
					$text .= implode("\n", $edit->closing) . "\n";
				}
			}
		} elseif ($title) {
			$wikiPage = WikiPage::factory($title);
			$text = ContentHandler::getContentText( $wikiPage->getContent() );
		}

		$matches = array();
		$preg = "/http:\/\/[^] \n'\"\>\<]*/im";
		preg_match_all($preg, $text, $matches);

		if (sizeof($matches[0]) == 0) {
			$out->addHTML( wfMessage('spamdifftool_no_urls_detected', $wgScript . "?" . urldecode($req->getVal('returnto') )));
			return;
		}

		$out->addHTML("
			<form method='POST'>
					<input type='hidden' name='returnto' value=\"" . htmlspecialchars($req->getVal('returnto')) . "\">
				<style type='text/css'>
						td.spam-url-row {
							border: 1px solid #ccc;
						}
				</style> " . wfMessage('spamdifftool_urls_detected') . "
			<br/><br/><table cellpadding='5px' width='100%'>");

		$urls = array();
		foreach ($matches as $match) {
			foreach ($match as $url) {
				if (isset($urls[$url])) continue; // avoid dupes
				$urls[$url] = true;
				$name = htmlspecialchars(str_replace(".", "%2E", $url));
				$out->addHTML("<tr>
					<td class='spam-url-row'><b>$url</b><br/>
					" . wfMessage('spamdifftool_block') . " &nbsp;&nbsp;
					<INPUT type='radio' name=\"" . $name . "\"	value='domain' checked> " . wfMessage('spamdifftool_option_domain') . "
					<INPUT type='radio' name=\"" . $name . "\"	value='subdomain'> " . wfMessage('spamdifftool_option_subdomain') . "
					<INPUT type='radio' name=\"" . $name . "\"	value='dir'>" . wfMessage('spamdifftool_option_directory') . "
					<INPUT type='radio' name=\"" . $name . "\"	value='none'>" . wfMessage('spamdifftool_option_none') . "
				</td>
				</tr>
				");
			}
		}

		$out->addHTML("</table><input type=submit value=\"" . htmlspecialchars(wfMessage('submit')) . "\"></form>");
		// DifferenceEngine directly fetched the revision:
		$RevIdFetched = $de->mNewid;
	}

}
