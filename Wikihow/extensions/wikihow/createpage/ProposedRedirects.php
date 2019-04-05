<?php

/*
CREATE TABLE `proposedredirects` (
  `pr_id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_user` int(10) unsigned NOT NULL,
  `pr_user_text` varchar(255) NOT NULL DEFAULT '',
  `pr_from` varchar(255) NOT NULL DEFAULT '',
  `pr_to` varchar(255) NOT NULL DEFAULT '',
  `pr_timestamp` varchar(14) NOT NULL DEFAULT '',
  PRIMARY KEY (`pr_id`),
  KEY `pr_from` (`pr_from`)
);
 */

class ProposedRedirects extends SpecialPage {
	function __construct() {
		parent::__construct('ProposedRedirects');
	}

	public static function deleteProposedRedirect($from, $to, $deleteAllFrom = false) {
		$dbw = wfGetDB(DB_MASTER);
		if ($deleteAllFrom) {
			$dbw->delete('proposedredirects',
				['pr_from' => $from],
				__METHOD__);
		} else {
			$dbw->delete('proposedredirects',
				array('pr_from' => $from, 'pr_to' => $to),
				__METHOD__);
		}
	}

	function createProposedRedirect($from, $to, $user = null) {
		global $wgUser;

		if ($user == null) {
			$user = $wgUser;
		}

		if (strpos($from, '_') !== false
			|| strpos($to, '_') !== false
			|| preg_replace('@^[- ]+@', '', $from) != $from
			|| preg_replace('@[- ]+$@', '', $from) != $from
		) {
			// Silently reject any proposed redirects that have underscores,
			// or start or end with spaces or '-' characters, since they
			// seem to perpetually cause projects in the admin tools. This
			// was done after conversation with Anna and in
			// response to bug #1956.
			return;
		}

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('proposedredirects',
			array('pr_from' => $from,
				'pr_to'=> $to,
				'pr_user' => $user->getID(),
				'pr_user_text' => $user->getName(),
				'pr_timestamp' => wfTimestampNow()),
			__METHOD__
		);
	}

	static function removePostProcessing($title, &$processHTML) {
		$processHTML = false;
		return true;
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgHooks;

		if (( !in_array( 'sysop', $wgUser->getGroups() ) ) and ( !in_array( 'newarticlepatrol', $wgUser->getRights() ) )) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$wgHooks['PreWikihowProcessHTML'][] = array('ProposedRedirects::removePostProcessing');

		$this->setHeaders();
		$wgOut->addHTML("<div class='minor_section'>");
		if ($wgRequest->getInt("tool") == 1) {
			$wgOut->addHTML(wfMessage('proposedredirects_info_tool'));
		} else {
			$wgOut->addHTML(wfMessage('proposedredirects_info'));
		}
		$t = Title::makeTitle(NS_PROJECT, "Proposed Redirects");
		$a = new Article($t);

		$wgOut->addHTML("<style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/createpage/createpage.css?') . WH_SITEREV . "'; /*]]>*/</style> ");

		if ($wgRequest->wasPosted()) {
			// deal with collisions of loading and saving
			$changes = array();
			foreach ($wgRequest->getValues() as $key=>$value) {
				if (strpos($key, "id-") === false) continue;
				$id = str_replace("id-", "btn-", $key);
				$newval = $wgRequest->getVal($id);
				switch ($newval) {
					case 'accept':
					case 'reject':
						$changes[$value] = $newval;
				}
			}
			foreach ($changes as $c=>$v) {
				$params = explode("_", $c);
				$from = Title::makeTitle(NS_MAIN, str_replace("_", " ", $params[0]));
				$to = Title::makeTitle(NS_MAIN, str_replace("_", " ", $params[1]));
				$response = self::handleRedirectVote($from, $to, $v);
				if ($response !== true) {
					$wgOut->addHTML($response);
				}
			}
			$wgOut->redirect('');
		}

		// regrab the text if necessary
		$r = Revision::newFromTitle($t);
		$text = "";
		if ($r) {
			$text = ContentHandler::getContentText( $r->getContent() );
		}
		$lines = explode("\n", $text);
		$s = "";
		$conditions = [];
		if ($wgRequest->getInt("tool") == 1) {
			$conditions['pr_user_text'] = DuplicateTitles::BOT_NAME;
		}
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('proposedredirects',
			array('pr_from', 'pr_to', 'pr_user_text'),
			$conditions,
			__METHOD__,
			array("LIMIT" => 250));
		foreach ($res as $row) {
			$u = User::newFromName($row->pr_user_text);
			$to = Title::newFromText($row->pr_to);
			$from = Title::newFromText($row->pr_from);
			$key = htmlspecialchars($from->getDBKey() . "_" . $to->getDBKey());
			if (!$u)
				$url = "/User:{$row->pr_user_text}";
			else
				$url = $u->getUserPage()->getFullURL();
			$id = rand(0,1000000);
			$s .= "<tr>
					<td>{$row->pr_user_text}</td>
					<td><a href='{$from->getFullURL()}' target='new'>{$from->getText()}</td>
					<td>
						<input type='hidden' name='id-{$id}' value=\"{$key}\"/>
						<a href='{$to->getFullURL()}' target='new'>{$to->getText()}</td>
					<td class='btn'><input type='radio' name='btn-{$id}' value='accept'/></td>
					<td class='btn'><input type='radio' name='btn-{$id}' value='skip' CHECKED/></td>
					<td class='btn'><input type='radio' name='btn-{$id}' value='reject'/></td>
					</tr>";
		}
		if ($s == "") {
			$wgOut->addHTML("There are currently no proposed redirects to show. Please check again later.");
		} else {
			$wgOut->addHTML("
					<script>
						function clearForm() {
							for (i = 0; i < document.proposedform.elements.length; i++) {
								var e = document.proposedform.elements[i];
								if (e.type=='radio') {
									if (e.value=='skip') {
										e.checked = true;
									} else {
										e.checked = false;
									}
								}
							}
						}
						function rejectAll() {
							for (i = 0; i < document.proposedform.elements.length; i++) {
								var e = document.proposedform.elements[i];
								if (e.type=='radio') {
									if (e.value=='reject') {
										e.checked = true;
									} else {
										e.checked = false;
									}
								}
							}
							alert('Warning! You have chosen to reject all of the proposed redirects, please use this carefully. Press Reset to Undo.');
						}
					</script>
					<form method='POST' action='/Special:ProposedRedirects' name='proposedform'>
						<table class='p_redirects'>
						<tr class='toprow'>
							<td>User</td><td>Title</td><td>Current Article</td><td>Accept</td><td>Skip</td><td>Reject</td>
						</tr>
						$s
						</table>
					<table width='100%'>
						<tr><td>
			");
			if ($wgUser->isSysop()) {
				$wgOut->addHTML("<input type='button' class='guided-button' value='Reject all' onclick='rejectAll();'>");
			}
			$wgOut->addHTML("</td><td style='text-align: right;'>
								<input type='button' class='guided-button' value='Reset' onclick='clearForm();'>
								<input type='button' class='guided-button' value='" .  wfMessage('Submit') . "'onclick='document.proposedform.submit();'>
							</td></tr></table>
						</form>");
		}
		$wgOut->addHTML("</div><!--end minor_section-->");
	}

	public static function handleRedirectVote($from, $to, $action) {
		$deleteAllFrom = false;
		if ($action == 'accept') {
			$a = new Article($from);
			if ($from->getArticleID() == 0) {
				$a->insertNewArticle("#REDIRECT [[{$to->getText()}]]\n", "Creating proposed redirect", false, false);
				$log = new LogPage( 'redirects', true );
				$log->addEntry('added', $from, 'added', array($to, $from));
				$deleteAllFrom = true;
			}  else {
				self::deleteProposedRedirect($from->getDBKey(), $to->getDBKey(), true);
				return "{$from->getText()} is an existing article, skipping<br/>";
			}
		}
		self::deleteProposedRedirect($from->getDBKey(), $to->getDBKey(), $deleteAllFrom);
		return true;
	}
}
