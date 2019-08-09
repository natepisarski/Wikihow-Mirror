<?php

class AdminMarkPromoted extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('AdminMarkPromoted');
	}

	/**
	 * Execute special page. Only available to wikihow staff.
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if (!$this->userAllowed()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$out->setHTMLTitle('Admin - Mark Promoted - wikiHow');
		$out->setPageTitle('Mark Promoted');

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$html = '';

			set_time_limit(0);

			$pageList = $req->getVal('pages-list', '');
			$comment = '[Batch Clear] ' . $req->getVal('comment', '');

			if ($pageList) $pageList = urldecode($pageList);
			$pageList = preg_split('@[\r\n]+@', $pageList);
			$pageData = array();
			$failedPages = array();

			// Get the page titles from the URLs:
			foreach ($pageList as $url) {
				$trurl = trim($url);
				$partial = preg_replace('/ /', '-', self::getPartial($trurl));
				if (!empty($partial)) {
					$pageData[] = array('partial' => $partial, 'url' => $trurl);
				} elseif (!empty($trurl)) {
					$failedPages[] = $url;
				}
			}

			$html .= $this->generateResults($pageData, $failedPages, $comment);

			if (!empty($failedPages)) {
				$html .= '<br/><p>Unable to parse the following URLs:</p>';
				$html .= '<p>';
				foreach ($failedPages as $p) {
					$html .= '<b>' . $p . '</b><br />';
				}
				$html .= '</p>';
			}
			$result = array('result' => $html);
			print json_encode($result);
		} else {
			$out->addModules( 'ext.wikihow.adminmarkpromoted' );
			$tmpl = self::getGuts();
			$out->setRobotPolicy('noindex,nofollow');
			$out->addHTML($tmpl);
		}
	}

	/**
	 * Given a URL or partial, give back the page title
	 */
	public static function getPartial($url) {
		$partial = preg_replace('@^https?://[^/]+@', '', $url);
		$partial = preg_replace('@^/@', '', $partial);
		return $partial;
	}

	private static function getGuts() {
		return "		<form method='post' action='/Special:AdminMarkPromoted'>
		<h4 style='margin-left:0'>Enter a list of full URLs such as <code>https://www.wikihow.com/Kill-a-Scorpion</code> or partial URLs like <code>/Research-Wallabies</code> for pages that should be marked as nabbed in <a href='/Special:NewArticleBoost'>Special:NewArticleBoost</a>.  One per line.</h4>
		<br/>
		** Note this tool promotes articles.
		<br /><br />
		<table><tr><td>Pages:</td><td><textarea id='pages-list' type='text' rows='10' cols='70'></textarea></td></tr>
		<button id='pages-clear' disabled='disabled'>Promote</button>
		<br/><br/>
		<div id='pages-result'>
		</div>
		</form>";
	}

	private function userAllowed() {
		$user = $this->getUser();
		$userGroups = $user->getGroups();
		$hasNABrights = in_array('staff', $userGroups);

		// On int'l, admins should do this NAB marking too
		if (RequestContext::getMain()->getLanguage()->getCode() != 'en') $hasNABrights = $hasNABrights || in_array('sysop', $userGroups);

		if ($user->isBlocked() || !$hasNABrights) {
			return false;
		}

		return true;
	}

	private function generateResults($pageData, $failedPages, $comment) {
		global $wgServer;

		$user = $this->getUser();

		// Set up the output table
		$html = '<style>.tres tr:nth-child(even) {background: #e0e0e0;} .failed {color: #a84810;} .success {color: #48a810;}</style>';
		$html .= '<table class="tres"><tr>';
		$html .= '<th width="350px"><b>Page</b></th>';
		$html .= '<th width="240px"><b>Status</b></th></tr>';

		$dbw = wfGetDB( DB_MASTER );
		$userid = $user->getID();
		foreach ($pageData as &$dataRow) {
			$html .= '<tr>';
			$p = $dataRow['partial'];
			$title = Title::makeTitleSafe(NS_MAIN, $p);
			$dataRow['title'] = $title;
			$dataRow['type'] = 'none';
			$notFound = false;

			if ($title && $title->exists() && $title->inNamespace(NS_MAIN)) {
				// It's an article in NS_MAIN
				$artId = $title->getArticleID();
				if ($artId > 0) {
					$dataRow['type'] = 'article';
					$dataRow['pageId'] = $artId;
					$dataRow['nabbed'] = NewArticleBoost::isNABbed($dbw, $artId);
				} else {
					$notFound = true;
				}
			} else {
				$notFound = true;
			}

			if ($notFound) {
				$html .= "<td>{$dataRow['url']}</td>";
				$html .= "<td><b><span class=\"failed\">Page not found</span></b></td>";
			} elseif ($dataRow['nabbed']) {
				$html .= "<td><a href='{$wgServer}/{$dataRow['title']}' rel='nofollow'>{$dataRow['title']}</a></td>";
				$html .= "<td><b><span class=\"failed\">Already nabbed</span></b></td>";
			} else {
				$status = '<span class="success">Nabbed</span>';
				NewArticleBoost::markNabbed($dbw, $artId, $userid);

				$html .= "<td><a href='{$wgServer}/{$dataRow['title']}' rel='nofollow'>{$dataRow['title']}</a></td>";
				$html .= "<td><b>{$status}</b></td>";
			}
		}

		$html .= '</table>';

		return $html;
	}
}
