<?php

class ManageSuggestions extends UnlistedSpecialPage {
	function __construct() {
		parent::__construct( 'ManageSuggestions');
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgUser;
		$fname = "wfManageSuggestions";

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->setHeaders();
		$wgOut->addHTML("<style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/createpage/createpage.css?') . WH_SITEREV . "'; /*]]>*/</style> ");
		if ($wgRequest->wasPosted() && $wgRequest->getVal('q') != null) {
			$matches = SuggestionSearch::matchKeyTitles($wgRequest->getVal('q'), 30);
			if (count($matches) == 0) {
				$wgOut->addHTML( wfMessage('createpage_nomatches')->text() );
				return;
			}
			$wgOut->addHTML( wfMessage('createpage_matches')->text() );
			$wgOut->addHTML("<div class='wh_block'><form method='POST'><table class='cpresults'><tr>");
			for ($i = 0; $i < count($matches); $i++) {
				$t = Title::newFromDBkey($matches[$i][0]);
				if (!$t) continue;
				if ($t)
					$name = htmlspecialchars($t->getDBKey());
					$wgOut->addHTML("<td><!--id {$matches[$i][1]} --><input type='checkbox' name=\"{$matches[$i][1]}\"/>&nbsp;<a href='{$t->getEditURL()}' class='new'>{$t->getFullText()}</a><input type='hidden' name='title_{$matches[$i][1]}' value='{$name}'/></td>");
				if ($i % 3 == 2) $wgOut->addHTML("</tr><tr>");
			}
			$wgOut->addHTML("</tr></table><br/>To delete any of these, select the checkbox and hit the delete button.<br/>
			<input type='hidden' name='delete' value='1'/>
			<input type='submit' value='Delete'/></form></div>
			");
			return;
		} elseif ($wgRequest->wasPosted() && $wgRequest->getVal('delete') != null) {
			$dbw = wfGetDB(DB_MASTER);
			$log = new LogPage( 'suggestion', true );
			foreach($wgRequest->getValues() as $key => $value) {
				if ($value != 'on') continue;
				$xx = $wgRequest->getVal("title_" . $key);
				if ($dbw->delete('suggested_titles', array('st_id' => $key), __METHOD__)) {
					$wgOut->addHTML("The suggestion \"{$xx}\" has been removed.<br/>");
					$msg= wfMessage('managesuggestions_log_remove', $wgUser->getName(), $xx)->text();
					$t = Title::makeTitle(NS_SPECIAL, "ManageSuggestions");
					$log->addEntry( 'removed', $t, $msg);
				} else {
					$wgOut->addHTML("Could not remove \"{$key}\", report this to Travis.<br/>");
				}
			}
			$wgOut->addHTML("<br/><br/>");
		} elseif ($wgRequest->wasPosted() && $wgRequest->getVal('new_suggestions') != null) {
			$dbw = wfGetDB(DB_MASTER);
			$sugg = $wgRequest->getVal('new_suggestions');
			$format = $wgRequest->getVal('formatted') != 'on';
			$lines = explode("\n", $sugg);
			$log = new LogPage( 'suggestion', true );
			foreach ($lines as $line) {
				$title = trim($line);
				if ($format) {
					$title = GuidedEditorHelper::formatTitle($title);
				}
				$key = TitleSearch::generateSearchKey($title);

				$count = $dbw->selectField('suggested_titles', array('count(*)'), array('st_key' => $key));
				if ($count > 0) {
					$wgOut->addHTML("Suggestion \"{$title}\" <b>not</b> added - duplicate suggestion.<br/>");
					continue;
				}

				$t = Title::newFromText($title);
				if ($t->getArticleID() > 0) {
					$wgOut->addHTML("Suggestion \"{$title}\" <b>not</b> added - article exists. <br/>");
					continue;
				}

				$dbw->insert('suggested_titles', array('st_title' => $title, 'st_key' => $key));
				$msg= wfMessage('managesuggestions_log_add', $wgUser->getName(), $title)->text();
				$log->addEntry( 'added', $t, $msg);
				$wgOut->addHTML("Suggestion \"{$title}\" added (key $key) <br/>");
			}
			$wgOut->addHTML("<br/><br/>");
		} elseif ($wgRequest->wasPosted() && $wgRequest->getVal('remove_suggestions') != null) {
			$dbw = wfGetDB(DB_MASTER);
			$sugg = $wgRequest->getVal('remove_suggestions');
			$lines = explode("\n", $sugg);
			$wgOut->addHTML("<ul>");
			foreach ($lines as $line) {
				$title = trim($line);
				if ($title == "") continue;
				$t = Title::newFromText($title);
				if (!$t) {
					$wgOut->addHTML("<li>Can't make title out of $title</li>");
					continue;
				}

				if ($dbw->delete('suggested_titles', array('st_title' => $t->getDBKey()))) {
					$wgOut->addHTML("<li>{$t->getText()} deleted</li>");
				} else {
					$wgOut->addHTML("<li>{$t->getText()} NOT deleted, is that the right title?</li>");
				}
			}
			$wgOut->addHTML("</ul>");
		}

		$wgOut->addHTML( wfMessage('managesuggestions_boxes')->text() );
	}
}
