<?php

class MWMessages extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'MWMessages' );
	}

	private function getExtensionInfo($key) {
		global $wgExtensionCredits;

		foreach ($wgExtensionCredits as $kind=>$args) {
			foreach ($args as  $r) {
				if (stripos($r['name'], $key) !== false) return " - {$r['description']}";
			}
		}
		return '';
	}

	public function execute($par) {
		global $wgExtensionMessagesFiles;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( !in_array( 'sysop', $user->getGroups() ) ) {
			$out->setArticleRelated( false );
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$target = isset( $par ) ? $par : $req->getVal( 'target' );

		$filename = $req->getVal('mwextension');
		if ($target || $filename) {

			if ($target && !$filename) {
				foreach ($wgExtensionMessagesFiles as $m) {
					if (stripos($m, $target) !== false) {
						$filename = $m;
						break;
					}
				}
			}
		}

		$out->addHTML('<link rel="stylesheet" href="/extensions/min/f/extensions/wikihow/mwmessages.css,/skins/WikiHow/popupEdit.css" type="text/css" />');
		$out->addScript('<script type="text/javascript" src="/extensions/min/f/skins/WikiHow/popupEdit.js"></script>');
		$out->addHTML("<div class='mwmessages'/><form action='/Special:MWMessages' method='POST' name='mwmessagesform'>");
		$out->addHTML("Browse by Extension<br/><select name='mwextension' onchange='document.mwmessagesform.submit();'>");

		$foundExt = false;
		foreach ($wgExtensionMessagesFiles as $m) {
			$key = preg_replace("@.*/@", "", $m);
			$key = preg_replace("@\..*@", "", $key);
			$addinfo = $this->getExtensionInfo($key);
			if ($filename == $m) {
				$out->addHTML("<OPTION VALUE='$m' SELECTED>{$key}{$addinfo}</OPTION>\n");
				$foundExt = true;
			} else {
				$out->addHTML("<OPTION VALUE='$m'>{$key}{$addinfo}</OPTION>\n");
			}
		}

		$out->addHTML("</select><input type='submit' value='Go'>");
		$out->addHTML("</form>");

		if ($req->wasPosted()) {

			$search = $req->getVal('mwmessagessearch');
			if ($search) {
				$lang = 'en';
				$allMsgs = MessageCache::singleton()->getAllMessageKeys($lang);
				$langMsgs = Language::getMessagesFor($lang);
				$sortedArray = array_merge( $langMsgs, $allMsgs );
				$out->addHTML("<table class='mwmessages'>
						<tr><td><b>Lang</b></td><td><b>Key</b></td><td><b>Value</b></td></tr>");
				foreach ($sortedArray as $key=>$val) {
					$val = wfMessage($key);
					if (stripos($val, $search) !== false) {
						$t = Title::makeTitle(NS_MEDIAWIKI, $key);
						$qe_url = '<a href="/' . htmlspecialchars($t->getPrefixedURL()) . '?action=edit" target="_blank">' . $key .'</a>';
						$out->addHTML("<tr><td class='mw_lang'>{$lang}</td><td class='mw_key'>{$qe_url}</td><td class='mw_val'>" . htmlspecialchars($val) ."</td></tr>");
					}
				}

				$dbr = wfGetDB(DB_REPLICA);
				$res = $dbr->select('page', array('page_title', 'page_namespace'), array('page_namespace'=>NS_MEDIAWIKI));
				foreach ($res as $row) {
					$t = Title::makeTitle($row->page_namespace, $row->page_title);
					if (!$t) continue;
					$r = Revision::newFromTitle($t);
					if (!$r) continue;
					$val = ContentHandler::getContentText( $r->getContent() );
					if (stripos($val, $search) !== false) {
						$qe_url = '<a href="/' . htmlspecialchars( $t->getPrefixedURL() ) . '?action=edit" target="_blank">' . $row->page_title .'</a>';
						$out->addHTML("<tr><td class='mw_lang'>{$lang}</td><td class='mw_key'>{$qe_url}</td><td class='mw_val'>" . htmlspecialchars($val) ."</td></tr>");
					}
				}

				$out->addHTML("</table>");
			}

		}

		// for security, so that it's not possibly for admins to execute arbitrary php files
		if ($foundExt && $filename) {
			// reset messages
			$messages = array();
			require_once($filename);
			$out->addHTML("<table class='mwmessages'>
					<tr><td><b>Lang</b></td><td><b>Key</b></td><td><b>Value</b></td></tr>");
			$index = 0;
			foreach ($messages as $lang=>$arrs) {
				foreach ($arrs as $key=>$val) {
					$newval = wfMessage($key);
					if ($newval != "&lt;{$key}&gt;")
						$val = $newval;
					$t = Title::makeTitle(NS_MEDIAWIKI, $key);
					$out->addHTML("<tr><td class='mw_lang'>{$lang}</td><td class='mw_key'>{$key}</td><td class='mw_val' id='mw_{$index}'>" . htmlspecialchars($val) ."</td></tr>");
					$index++;
				}
			}
			$out->addHTML("</table>");
		}

		$out->addHTML("</div><br/><br/><div class='mwmessages'>Or search for a message that's not an extension message:<br/><br/>
				<form action='/Special:MWMessages' method='POST' name='mwmessagesform_search'>
				<center>
				<input type='text' name='mwmessagessearch' value=\"" .htmlspecialchars($req->getVal('mwmessagessearch')) . "\" style='width:300px;font-size:110%;'>
				<input type='submit' value='Search for messages'/>
				</center>
				</form>
			</div>
			");
	}
}
