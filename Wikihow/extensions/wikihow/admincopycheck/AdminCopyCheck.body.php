<?php

global $IP;
require_once "$IP/extensions/wikihow/common/copyscape_functions.php";

class AdminCopyCheck extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminCopyCheck');
	}

	private static function processURLlist($pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$goodCount = 0;
		$urls = array();
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$article = preg_replace('@https?://www.wikihow.com/@','',$url);
				$article = urldecode($article);
				$err = self::checkArticle($article);
				if ($err != '') {
					$urls[] = array('url' => $url, 'article' => $article, 'err' => $err);
				}
				else {
					$goodCount++;
				}
			}
		}
		return array($urls, $goodCount);
	}

	private static function checkArticle($article) {
		if (!$article) return 'No such title';

		$t = Title::newFromText($article);
		if (!$t) return 'No such title';

		$err = self::copyCheck($t);
		if ($err) return $err;

		//still here?
		return '';
	}

	/**
	 * check for plagiarism with copyscape
	 * return true if there's an issue
	 */
	private static function copyCheck($t) {
		$threshold = 0.05;
		$result = '';
		$r = Revision::newFromTitle($t);
		if (!$r) return 'No such article';

		$text = Wikitext::flatten(ContentHandler::getContentText( $r->getContent() ));
		$text = Wikitext::stripLinkUrls($text);

		$start = microtime(true);
		$res = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);
		$time = sprintf('%.4f', microtime(true) - $start);
		$title = $t->getText();
		$logline = "Took $time seconds checking article $title";
		self::logit($logline);

		if ($res['count']) {
			$words = $res['querywords'];
			foreach($res['result'] as $r) {
				if (!preg_match("@^http://[a-z0-9]*.(wikihow|whstatic|youtube).com@i", $r['url'])) {
					//if ($r['minwordsmatched'] / $words > $threshold) {
						//we got one!
						$result .= '<b>Plagiarized:</b> <a href="'.$r['url'].'">'.$r['url'].'</a><br />';
					//}
				}
			}
		}
		else {
			$result = '';
		}

		return $result;
	}

	private static function logit($line) {
		$date = date('r');
		error_log("$date: $line\n", 3, '/var/log/wikihow/copyscape.log');
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgIsDevServer, $wgServer;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || (!in_array('staff', $userGroups) && !in_array('staff_widget', $userGroups))) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if (!$wgIsDevServer && $wgServer != 'https://parsnip.wikiknowhow.com') {
			$out->redirect('https://parsnip.wikiknowhow.com/Special:AdminCopyCheck');
		}

		if ($req->wasPosted()) {
			// this may take a while...
			set_time_limit(0);

			$out->setArticleBodyOnly(true);
			$action = $req->getVal('action');

			$pageList = $req->getVal('pages-list', '');

			list($urls, $goodCount) = self::processURLlist($pageList);

			$html = '<p><b>Originals:</b> '.(int)$goodCount.'</p>';

			if (!empty($urls)) {
				$html .= '<style>.tres tr:nth-child(even) {background: #ccc;} .tres td { padding: 5px; }</style>'.
						'<table class="tres"><tr><th>URL</th><th>Error</th></tr>';

				foreach ($urls as $row) {
					$html .= "<tr><td><a href='{$row['url']}'>{$row['article']}</a></td><td>{$row['err']}</td></tr>";
				}
				$html .= '</table>';
			}

			$result = array('result' => $html);

			print json_encode($result);

			return;
		}

		$out->setHTMLTitle('Admin - Copy Check - wikiHow');
		$userEmail = $user->getEmail();

		$out->addModules( ['ext.wikihow.admincopycheck'] );

		$options = [ 'loader' => new Mustache_Loader_FilesystemLoader(__DIR__) ];
		$mustache = new Mustache_Engine($options);
		$vars = [];
		$out->addHTML( $mustache->render('admincopycheck.mustache', $vars) );
	}
}
