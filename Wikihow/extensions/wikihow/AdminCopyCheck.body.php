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
				$article = preg_replace('@http://www.wikihow.com/@','',$url);
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
		
		$text = Wikitext::flatten($r->getText());
		$text = Wikitext::stripLinkUrls($text);
		
		$res = copyscape_api_text_search_internet($text, 'ISO-8859-1', 2);
		
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

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() || (!in_array('staff', $userGroups) && !in_array('staff_widget', $userGroups))) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($_SERVER['HTTP_HOST'] != 'parsnip.wikiknowhow.com') {
			$wgOut->redirect('https://parsnip.wikiknowhow.com/Special:AdminCopyCheck');
		}

		if ($wgRequest->wasPosted()) {
			// this may take a while...
			set_time_limit(0);

			$wgOut->setArticleBodyOnly(true);
			$action = $wgRequest->getVal('action');
		
			$pageList = $wgRequest->getVal('pages-list', '');

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

		$wgOut->setHTMLTitle('Admin - Copy Check - wikiHow');
		$userEmail = $wgUser->getEmail();

$tmpl = <<<EOHTML
<form id="images-resize" method="post" action="/Special:AdminCopyCheck">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Admin Copy Check
</div>

<h3>Copy check articles</h3>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of URLs such as <code style="font-weight: bold;">http://www.wikihow.com/Be-a-Ninja</code> to process.<br />
	One per line.
</div>
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea>
<button id="pages-go" disabled="disabled" style="padding: 5px;">process</button><br/>
<br/>
<div id="pages-result">
</div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#pages-go')
			.removeAttr('disabled')
			.click(function () {
				var form = $('#images-resize').serializeArray();
				$('#pages-result').html('loading ...');
				$.post('/Special:AdminCopyCheck',
					form,
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});

		$('#pages-list')
			.focus();

	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}
