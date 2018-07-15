<?php

class SpamDiffTool extends SpecialPage {

	function __construct() {
		parent::__construct('SpamDiffTool');
		$this->setListed(false);
	}

	function getDiffLink($title) {
		global $wgUser, $wgRequest, $wgSpamBlacklistArticle;
		$sk = $wgUser->getSkin();
		$sb = Title::newFromDBKey($wgSpamBlacklistArticle);
		if (!$sb->userCan('edit')) {
			return '';
		}
		$link = '[' . $sk->makeKnownLinkObj( Title::newFromText("SpamDiffTool", NS_SPECIAL), wfMessage('spamdifftool_spam_link_text'),
					'target=' . $title->getPrefixedURL().
					'&oldid2=' . $wgRequest->getVal('oldid') .
					'&rcid='. $wgRequest->getVal('rcid') .
					'&diff2='. $wgRequest->getVal('diff')  .
					'&returnto=' . urlencode($_SERVER['QUERY_STRING'])
					) .
					']';
		return $link;
	}

	function execute($par) {
		global $wgRequest, $wgContLang, $wgOut, $wgSpamBlacklistArticle, $wgUser, $wgScript;
		$title = Title::newFromDBKey($wgRequest->getVal('target'));
		$diff = $wgRequest->getVal( 'diff2' );
		$rcid = $wgRequest->getVal( 'rcid' );
		$rdfrom = $wgRequest->getVal( 'rdfrom' );

		$wgOut->setHTMLTitle(wfMessage('pagetitle', 'Spam Tool'));

		// can the user even edit this?
		$sb = Title::newFromDBKey($wgSpamBlacklistArticle);
		if (!$sb->userCan('edit')) {
			$wgOut->addHTML(wfMessage('spamdifftool_cantedit'));
			return;
		}

		// do the processing
		if ($wgRequest->wasPosted() ) {

			if ($wgRequest->getVal('confirm', null) != null) {
				$t = Title::newFromDBKey($wgSpamBlacklistArticle);
				$a = new Article($t);
				$text = $a->getContent();

				// insert the before the <pre> at the bottom  if there is one
				$i = strrpos($text, "</pre>");
				if ($i !== false) {
					$text = substr($text, 0, $i)
							. $wgRequest->getVal('newurls')
							. "\n" . substr($text, $i);
				} else {
					$text .= "\n" . $wgRequest->getVal('newurls');
				}
				$watch = false;
				if ($wgUser->getID() > 0)
				$watch = $wgUser->isWatched($t);
				$a->updateArticle($text, wfMessage('spamdifftool_summary'), false, $watch);
				$returnto = $wgRequest->getVal('returnto', null);
				if ($returnto != null && $returnto != '')
					$wgOut->redirect($wgScript . "?" . urldecode($returnto) ); // clear the redirect set by updateArticle
				return;
			}
			$vals = $wgRequest->getValues();
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
				$wgOut->addHTML( wfMessage('spamdifftool_notext', $wgScript . "?" . urldecode($wgRequest->getVal('returnto') )));
				return;
			}
			$wgOut->addHTML("<form method=POST>
					<input type='hidden' name='confirm' value='true'>
					<input type='hidden' name='newurls' value=\"" . htmlspecialchars($text) . "\">
					<input type='hidden' name='returnto' value=\"" . htmlspecialchars($wgRequest->getVal('returnto')) . "\">
				");
			$wgOut->addHTML(wfMessage('spamdifftool_confirm') . "<pre style='padding: 10px'>$text</pre>");
			$wgOut->addHTML("</table><input type=submit value=\"" . htmlspecialchars(wfMessage('submit')) . "\"></form>");
			return;
		}

		if ( !is_null( $diff ) ) {
			require_once( 'DifferenceEngine.php' );

			// Get the last edit not by this guy
			$current = Revision::newFromTitle( $title );
			$dbw = wfGetDB(DB_MASTER);
			$user = intval( $current->getUser() );
			$user_text = $dbw->addQuotes( $current->getUserText() );
			$s = $dbw->selectRow( 'revision',
				array( 'rev_id', 'rev_timestamp' ),
				array(
					'rev_page' => $current->getPage(),
					"rev_user <> {$user} OR rev_user_text <> {$user_text}"
				), $fname,
				array(
					'USE INDEX' => 'page_timestamp',
				'ORDER BY'  => 'rev_timestamp DESC' ) );
			if ($s) {
				// set oldid
				$oldid = $s->rev_id;
			}

			if ($wgRequest->getVal('oldid2') < $oldid)
				$oldid = $wgRequest->getVal('oldid2');

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
		} else {
			if ($title != "") {
				$a = new Article($title);
				$text = $a->getContent(true);
			}
		}

		$matches = array();
		$preg = "/http:\/\/[^] \n'\"\>\<]*/im";
		preg_match_all($preg, $text, $matches);

		if (sizeof($matches[0]) == 0) {
			$wgOut->addHTML( wfMessage('spamdifftool_no_urls_detected', $wgScript . "?" . urldecode($wgRequest->getVal('returnto') )));
			return;
		}

		$wgOut->addHTML("
			<form method='POST'>
					<input type='hidden' name='returnto' value=\"" . htmlspecialchars($wgRequest->getVal('returnto')) . "\">
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
				$wgOut->addHTML("<tr>
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

		$wgOut->addHTML("</table><input type=submit value=\"" . htmlspecialchars(wfMessage('submit')) . "\"></form>");
		// DifferenceEngine directly fetched the revision:
		$RevIdFetched = $de->mNewid;
	}

}

