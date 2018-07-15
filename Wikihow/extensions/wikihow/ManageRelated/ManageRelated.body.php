<?php

class ManageRelated extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('ManageRelated');
	}

	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		$this->setHeaders();

		if( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		$target = isset($par) ? $par : $wgRequest->getVal('target');
		if (!$target) {
			$wgOut->addHTML(wfMessage('notarget'));
			return;
		}

		$titleObj = Title::newFromUrl(urldecode($target));
		if (!$titleObj || !$titleObj->exists()) {
			$wgOut->addHTML('Error: bad target');
			return;
		}

		$whow = WikihowArticleEditor::newFromTitle($titleObj);
		$rev = Revision::newFromTitle($titleObj);
		$article = Article::newFromTitle($titleObj, $this->getContext());
		$text = $rev->getText();

		if ($wgRequest->wasPosted()) {
			// protect from users who can't edit
			if ( ! $titleObj->userCan('edit') ) {
				$wgOut->readOnlyPage( $article->getContent(), true );
				return;
			}

			// construct the related wikihow section
			$rel_array = explode('|', $wgRequest->getVal('related_list'));
			$result = "";
			foreach ($rel_array as $rel) {
				$rel = urldecode(trim($rel));
				if (!$rel) continue;
				$result .= "* [[" . $rel . "]]\n";
			}

			if (strpos($text, "\n== "  . wfMessage('relatedwikihows') .  " ==\n") !== false) {
				// no newline neeeded to start with
				$result = "== "  . wfMessage('relatedwikihows') .  " ==\n" . $result;
			} else {
				$result = "\n== "  . wfMessage('relatedwikihows') .  " ==\n" . $result;
			}

			$text = "";
			$index = 0;
			$content = $article->getContent();
			$last_heading = "";
			$inserted = false;

			$section = -1;
			$ext_links_section = -1;

			if ($article->getSection($content, $index) == null) {
				$index++; // weird where there's no summary
			}

			while ( ($sectiontext = $article->getSection($content, $index)) != null) {
				$i = strpos($sectiontext, "\n");
				if ($i > 0) {
					$heading = substr($sectiontext, 0, $i);
					$heading = trim(str_replace("==", "", $heading));
					if ($heading == wfMessage('relatedwikihows') ) {
						$section = $index;
						break;
					}
					if ($heading == wfMessage('sources')) {
						$ext_links_section = $index;
					}
				}
				$index++;
			}

			$text = $result;
			$tail = '';
			$text = $article->getContent();

			// figure out which section to replace if related wikihows
			// don't exist
			$just_append = false;
			if ($section <= 0) {
				if ($ext_links_section > 0) {
					// related wikihows have to go before external links
					$section = $ext_links_section;
					// glue external links and related wikihows together
					// and replace external links
					$result = $result . "\n" . $article->getSection($content, $section);
				} else {
					$section = $index;
					$result = "\n" . $result; // make it a bit prettier
					$just_append = true;
				}
			} else {
				$s = $article->getSection($content, $section);
				$lines = explode("\n", $s);
				for ($i = 1; $i < sizeof($lines); $i++) {
					$line = $lines[$i];
					if (strpos($line, "*") !== 0) {
						// not a list item
						$tail .= "\n" . $line;
					}
				}
			}

			$result .= $tail;

			$summary = '';
			if (!$just_append) {
				$text = $article->replaceSection($section, $result, $summary);
			} else {
				$text = $text . $result;
			}

			$watch = false;
			$minor = false;
			$forceBot = false;
			if ($wgUser->getID() > 0) {
				$watch = $wgUser->isWatched($titleObj);
			}
			$summary = wfMessage('relatedwikihows'); // summary for the edit

			$article->updateArticle( $text, $summary, $minor, $watch, $forceBot);
			$this->getContext()->getOutput()->redirect( $article->getTitle()->getFullURL() );
		}

		// MW should handle editing extensions better, duplication of code sucks

		if( $titleObj->isProtected( 'edit' ) ) {
			if( $titleObj->isSemiProtected() ) {
				$notice = wfMessage( 'semiprotectedpagewarning' );
			} else {
				$notice = wfMessage( 'protectedpagewarning' );
			}
			$wgOut->addWikiText( $notice );
		}

		$relatedHTML = "";
		$text = $article->getContent();

		$relwh = $whow->getSection("related wikihows");

		if ($relwh != "") {
			$related_vis = "show";
			preg_match_all( '/\[\[([^[]*)\]\]/i', $relwh, $matches );

			$wikilinks = $matches[1];

			if (count($wikilinks) > 0) {
				foreach ($wikilinks as $wikilink) {
					$linkParts = explode( '|', $wikilink );
					$linkPage = trim($linkParts[0]);
					$relatedHTML .= "<option value=\"" . str_replace("\"", "&quote", $linkPage) . "\">$linkPage</option>\n";

				}
			}

		}

		$me = Title::makeTitle(NS_SPECIAL, "ManageRelated");
		$wgOut->addModules(['ext.wikihow.ManageRelated']);

		$targetEnc = htmlspecialchars($target, ENT_QUOTES);

		$wgOut->addHTML(<<<EOHTML
	<style type='text/css' media='all'>/*<![CDATA[*/ @import '{$cssFile}'; /*]]>*/</style>
	<script type='text/javascript' src='{$jsFile}'></script>

	<form method='POST' action='{$me->getFullURL()}' name='temp' onsubmit='return WH.ManageRelated.check();'>

	You are currently editing related wikiHows for the article
	<a href='{$titleObj->getFullURL()}' target='new'>{$titleObj->getFullText()}</a>.<br/>

	<table style='padding: 10px 5px 25px 5px;'>
	<tr><td valign='top'>
	<ol><li>Enter some search terms to find related wikiHows and press 'Search'.</li></ol>
	<input type='hidden' name='target' value='{$targetEnc}'/>
	<input type='hidden' name='related_list'/>
	<input type='text' name='q'/>
	<input type='button' class='btn' onclick='WH.ManageRelated.check();' value='Search' style="margin-top: 5px;" />
	</td>
	<td valign='top' style='padding-left: 10px; border-left: 1px solid #ddd;'>
	<div style='width: 175px; float: left;'>
	<u>Related wikiHows</u>
	</div>
	<div style='width: 175px; float: right; text-align: right; margin-bottom:5px;'>
	Move <input type=button value='Up &uarr;' class='btn' onclick='WH.ManageRelated.moveRelated("up");'/> <input type=button value='Down &darr;' class='btn' onclick='WH.ManageRelated.moveRelated("down");'/>
	</div>
	<select size='5' id='related' ondblclick='WH.ManageRelated.viewRelated();'>
		{$relatedHTML}
	</select>
	<br/><br/>
	<div style='width: 205px; float: left; text-align: left; font-size: xx-small; font-style: italic;'>
	(double click item to open wikiHow in new window)
	</div>
	<div style='width: 175px; float: right; text-align: right;'>
	<input type=button onclick='WH.ManageRelated.removeRelated();' value='Remove' class='btn'/>
	<input type=button value='Save' onclick='WH.ManageRelated.submitForm();' class='btn'/>
	</div>
	</td></tr>
	<tr>
		<td id='lucene_results' colspan='2' valign='top' class='lucene_results' style="padding-top: 10px;"></td>
	</tr><tr>
		<td id='previewold' colspan='2' valign='top'></td>
	</tr></table>

	</form>

	<div id='preview'></div>
EOHTML
		);

	}
}

class PreviewPage extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('PreviewPage');
	}

	public function execute($par) {
		global $wgRequest, $wgOut;

		$wgOut->setArticleBodyOnly(true);
		$wgOut->clearHTML();

		$target = isset($par) ? $par : $wgRequest->getVal('target');
		$title = Title::newFromUrl($target);
		if (!$title || !$title->exists()) {
			$wgOut->addHTML('Title no longer exists: ' . $target);
			return;
		}

		$article = new Article($title);
		$text = $article->getContent(true);
		$snippet = $article->getSection($text, 0) . "\n"
			. $article->getSection($text, 1);
		$html = $wgOut->parse($snippet);

		$wgOut->addHTML($html);
	}

}

