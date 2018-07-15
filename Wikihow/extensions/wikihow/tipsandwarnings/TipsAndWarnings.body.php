<?php
/*
* 
*/
class TipsAndWarnings extends UnlistedSpecialPage {
	
	const EDIT_COMMENT = "edited tip from [[Special:TipsPatrol|Tips Patrol]]";
	
	function __construct() {
		parent::__construct('TipsAndWarnings');
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;
		
		$articleId = intval($wgRequest->getVal('aid'));
		$tip = $wgRequest->getVal('tip');
		if($articleId != 0 && $tip != "") {		
			$wgOut->setArticleBodyOnly(true);
			if($tip != "") {
				//$result['success'] = $this->addTip($articleId, $tip);
				$tipId = $this->addTip($articleId, $tip);
				$tp = new TipsPatrol;
				$result['success'] = $tp->addToQG($tipId, $articleId, $tip);
				print_r(json_encode($result));
				return;
			}
		}
		
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups))
		{
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		
		$llr = new NewTipsAndWarnings();
    	$llr->getList();
		return;
		
	}
	
	private function addTip($articleId, $tip) {
		global $wgParser, $wgUser;
		
		$title = Title::newFromID($articleId);
		if($title) {
			
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('tipsandwarnings', array('tw_page' => $articleId, 'tw_tip' => $tip, 'tw_user' => $wgUser->getID(), 'tw_timestamp' => wfTimestampNow()),__METHOD__);
			
			//return true;
			$tipId = $dbw->selectField('tipsandwarnings', array('tw_id'), array('tw_page' => $articleId, 'tw_tip' => $tip, 'tw_user' => $wgUser->getID()),__METHOD__);
			
			$logPage = new LogPage('addedtip', false);
			$logData = array($tipId);
			$logMsg = wfMessage('addedtip-added-logentry', $title->getFullText(), $tip)->text();
			$logS = $logPage->addEntry("Added", $title, $logMsg, $logData);
			
			return $tipId;
		}
		
		//return false;
		return '';
	}
	
	public static function injectCTAs(&$xpath, &$t) {
		if (self::isActivePage() && self::isValidTitle($t)) {
			$nodes = $xpath->query('//div[@id="tips"]/ul');
			foreach ($nodes as $node) {
				$newHtml = "Insert new tip here:<br/>";
				$newHtml .= "<textarea class='newtip' style='margin-right:5px; height:35px; width:200px;'></textarea>";
				$newHtml .= "<a href='#' class='addtip button white_button' style='vertical-align:top'>Add</a>";
				$newHtml .= "<img class='tip_waiting' style='display:none' src='" . wfGetPad('/extensions/wikihow/rotate.gif') . "' alt='' />";
				$newNode = $node->ownerDocument->createElement('div', "");
				$newNode->setAttribute('class', 'addTipElement');
				$newNode->innerHTML = $newHtml;
				
				if($node->nextSibling !== null) {
					$node->parentNode->insertBefore($newNode, $node->nextSibling);
				}
				else {
					$node->parentNode->appendChild($newNode);
				}
				
				$i++;
			}
		}
	}
	
	public static function injectRedesignCTAs(&$xpath, &$t) {
		if (self::isValidTitle($t) && self::isActivePage()) {
			$nodes = $xpath->query('//div[@id="tips"]/ul');
			foreach ($nodes as $node) {
				$newHtml = "<textarea class='newtip' placeholder='Know a good tip? Add it.'></textarea>";
				$newHtml .= "<a href='#' class='addtip'>Add</a>";
				$newNode = $node->ownerDocument->createElement('div', "");
				$newNode->setAttribute('class', 'addTipElement');
				$newNode->innerHTML = $newHtml;
				
				if($node->nextSibling !== null) {
					$node->parentNode->insertBefore($newNode, $node->nextSibling);
				}
				else {
					$node->parentNode->appendChild($newNode);
				}
				
				$i++;
			}
		}
	}

	public static function addRedesignCTAs(&$doc, &$t) {
		if (self::isValidTitle($t) && self::isActivePage()) {

			foreach(pq("#tips > ul") as $node) {
				$newHtml = "<textarea class='newtip' placeholder='Know a good tip? Add it.'></textarea>";
				$newHtml .= "<a href='#' class='addtip op-action' role='button' aria-label='" . wfMessage('aria_add_tip')->showIfExists() . "'>Add</a>";

				$newNode = "<div class='addTipElement'>{$newHtml}</div>";

				$nextNode = pq($node)->next();

				if($nextNode->length > 0 ) {
					pq($newNode)->insertBefore($nextNode);
				}
				else {
					pq($node)->parent()->append($newNode);
				}
				return; //only one
			}
		}
	}
	
	public static function isValidTitle(&$t) {
		return $t && $t->exists() && $t->getNamespace() == NS_MAIN && !$t->isProtected();
	}

	public static function isActivePage() {
		return true;
	}
	
	function getSQL() {
		return "SELECT *, rc_timestamp as value from recentchanges WHERE rc_comment = '" . TipsAndWarnings::EDIT_COMMENT . "'";
	}
}

class NewTipsAndWarnings extends QueryPage {

	function __construct() {
		parent::__construct('TipsAndWarnings');
	}

	function getName() {
		return "NewTipsAndWarnings";
	}

	function isExpensive() {
		return false;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return TipsAndWarnings::getSql();
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		
		$title = Title::makeTitle( $result->rc_namespace, $result->rc_title );
		$diffLink = $title->escapeFullUrl(
					'diff=' . $result->rc_this_oldid .
					'&oldid=' . $result->rc_last_oldid );
		$diffText = '<a href="' .
					$diffLink .
					'">(diff)</a>';
		
		$date = date('m-d-y', wfTimestamp(TS_UNIX, $result->rc_timestamp));
		
		return $title->getText() . " $diffText on $date";
	}
	
	function getPageHeader( ) {
		global $wgOut;
		$wgOut->setPageTitle("New Tips/Warnings");
		return;
	}

	function getList() {
		list( $limit, $offset ) = wfCheckLimits();
		$this->limit = $limit;
		$this->offset = $offset;
		
		parent::execute('');
	}
}
