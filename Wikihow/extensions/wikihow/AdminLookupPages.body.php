<?php

class AdminLookupNab extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminLookupNab');
	}
	
	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		//$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() /*|| !in_array('staff', $userGroups)*/) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		

		if ($wgRequest->wasPosted()) {
			$dbr = wfGetDB(DB_SLAVE);

			$pageList = $wgRequest->getVal('pages-list', '');
			$wgOut->setArticleBodyOnly(true);
			$pageList = preg_split('@[\r\n]+@', $pageList);
			foreach ($pageList as $url) {
				$url = trim($url);
				$pagename = self::getTitleFromURL($url);
				if (!empty($pagename)) {
					$t = Title::newFromURL($pagename);
					if (!empty($t)) {
						$is_nabbed = Newarticleboost::isNABbed($dbr,$t->getArticleId());
						if ($is_nabbed) {
							$nabbed = 'yes';
						}
						else {
							$nabbed = 'no';
							$nabbed .= '<br />[<a href="./Special:Newarticleboost/'.$pagename.'" target="_blank">boost it</a>]';
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
		
		
		$wgOut->setHTMLTitle('Admin - Lookup NAB - wikiHow');
		$wgOut->addStyle('../extensions/wikihow/adminlookup.css?rev='. WH_SITEREV);
		
		$tmpl = AdminLookupPages::getGuts('AdminLookupNab');

		$wgOut->addHTML($tmpl);
	}
	
	
	/**
	 * Given a URL, give back the page title
	 */
	public static function getTitleFromURL($url) {
		global $wgCanonicalServer;
		$count = preg_match('@^' . $wgCanonicalServer . '/@', $url);
		if (!$count) return null;

		$partialUrl = preg_replace('@^' . $wgCanonicalServer. '/@', '', $url);
		return $partialUrl;
	}
}

class AdminLookupPages extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminLookupPages');
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		//$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() /*|| !in_array('staff', $userGroups)*/) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$dbr = wfGetDB(DB_SLAVE);

			$pageList = $wgRequest->getVal('pages-list', '');
			$wgOut->setArticleBodyOnly(true);
			$pageList = preg_split('@[\r\n]+@', $pageList);
			foreach ($pageList as $url) {
				$url = trim($url);
				if (!empty($url)) {
					$id = WikiPhoto::getArticleID($url);
					$images = '';
					if (!empty($id)) {
						$hasNoImages = WikiPhoto::articleBodyHasNoImages($dbr, $id);
						$images = $hasNoImages ? 'no' : 'yes';
					}
					$urls[] = array('url' => $url, 'id' => $id, 'images' => $images);
				}
			}

			$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
			$html .= '<table class="tres"><tr><th width="450px">URL</th><th>ID</th><th>Has steps images?</th></tr>';
			foreach ($urls as $row) {
				$html .= "<tr><td><a href='{$row['url']}'>{$row['url']}</a></td><td>{$row['id']}</td><td>{$row['images']}</td></tr>";
			}
			$html .= '</table>';

			$result = array('result' => $html);
			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle('Admin - Lookup Pages - wikiHow');

		$tmpl = self::getGuts('AdminLookupPages');

		$wgOut->addHTML($tmpl);
	}
		
	function getGuts($action) {			
		return "<form method='post' action='/Special:$action'>
		<h4>Enter a list of URLs such as <code>http://www.wikihow.com/Lose-Weight-Fast</code> to look up.  One per line.</h4>
		<br/>
		<textarea id='pages-list' type='text' rows='10' cols='70'></textarea>
		<button id='pages-go' disabled='disabled'>process</button><br/>
		<br/>
		<div id='pages-result'>
		</div>
		</form>

		<script>
		(function($) {
			$(document).ready(function() {
				$('#pages-go')
					.prop('disabled', false)
					.click(function () {
						$('#pages-result').html('loading ...');
						$.post('/Special:$action',
							{ 'pages-list': $('#pages-list').val() },
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
		</script>";
	}
}
