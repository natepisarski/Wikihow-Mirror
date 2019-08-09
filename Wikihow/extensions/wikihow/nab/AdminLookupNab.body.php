<?php

class AdminLookupNab extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminLookupNab');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$out->setRobotPolicy('noindex,nofollow');
		$req->response()->header('x-robots-tag: noindex, nofollow');

		//$userGroups = $user->getGroups();
		if ($user->isBlocked() || $user->isAnon()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$dbr = wfGetDB(DB_REPLICA);

			$pageList = $req->getVal('pages-list', '');
			$out->setArticleBodyOnly(true);
			$pageList = preg_split('@[\r\n]+@', $pageList);
			foreach ($pageList as $url) {
				$url = trim($url);
				$pagename = self::getTitleFromURL($url);
				if (!empty($pagename)) {
					$t = Title::newFromURL($pagename);
					if (!empty($t)) {
						$is_nabbed = NewArticleBoost::isNABbed($dbr,$t->getArticleId());
						if ($is_nabbed) {
							$nabbed = 'yes';
						}
						else {
							$nabbed = 'no';
							$nabbed .= '<br />[<a href="/Special:NewArticleBoost/'.$pagename.'" target="_blank">boost it</a>]';
						}
					}
					$urls[] = array('url' => $url, 'id' => $t->getArticleId(), 'boosted' => $nabbed);
				}
			}

			$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
			$html .= '<table class="tres"><tr><th width="450px">URL</th><th>ID</th><th>Boosted?</th></tr>';
			foreach ($urls as $row) {
				$html .= "<tr><td class='nab_url'><a href='{$row['url']}'>{$row['url']}</a></td><td class='nab_id'>{$row['id']}</td><td class='boost'>{$row['boosted']}</td></tr>";
			}
			$html .= '</table>';

			$result = array('result' => $html);
			print json_encode($result);
			return;
		}

		$out->addModules('ext.wikihow.adminlookup');

		$out->setHTMLTitle('Admin - Lookup NAB - wikiHow');
		$out->addStyle('/extensions/wikihow/nab/adminlookup.css?rev='. WH_SITEREV);

		$tmpl = self::getGuts();

		$out->addHTML($tmpl);
	}

	/**
	 * Given a URL, give back the page title
	 */
	public static function getTitleFromURL($url) {
		$partialUrl = preg_replace('@^(https?://)?([^/]*(wikihow|wikidogs|wikiknowhow)\.[^/]+)?/?@', '', $url);
		return $partialUrl;
	}

	private static function getGuts() {
		return "<form method='post' action='/Special:AdminLookupNab'>
		<h4>Enter a list of URLs such as <code>https://www.wikihow.com/Lose-Weight-Fast</code> to look up.  One per line.</h4>
		<br/>
		<textarea id='pages-list' type='text' rows='10' cols='70'></textarea>
		<button id='pages-go' disabled='disabled'>process</button><br/>
		<br/>
		<div id='pages-result'>
		</div>
		</form>";
	}
}
