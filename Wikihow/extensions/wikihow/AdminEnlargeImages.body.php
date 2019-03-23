<?php

class AdminEnlargeImages extends UnlistedSpecialPage {

	const DEFAULT_CENTER_PIXELS = 550;
	const DEFAULT_ENLARGE_PIXELS = 300;

	public function __construct() {
		parent::__construct('AdminEnlargeImages');
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist($pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$urls = array();
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$title = WikiPhoto::getArticleTitleNoCheck($url);
				$urls[] = array('url' => $url, 'title' => $title);
			}
		}
		return $urls;
	}

	/**
	 * Resize the steps images in a list of Title objects.
	 */
	private static function enlargeImagesUrls(&$urls, $recenter, $px, $introPx) {
		foreach ($urls as &$url) {
			if (!$url['title']) {
				$err = 'Unable to load article';
				$numImages = 0;
			} else {
				list($err, $numImages) = Wikitext::enlargeImages($url['title'], $recenter, $px, $introPx);
			}
			$url['err'] = $err;
			$url['images'] = $numImages;
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userName = $user->getName();
		$allowedUsers = array(
			'Goldenzebra', // Zareen
			'Emazing', // Emma
			'Thomscher', // Thom
			'Chloechen', // Chloe
			'Wikiphoto', // Requested by Thom
			'Gersh', // For coding changes
			'Argutier', // For coding changes
		);
		$userGroups = $user->getGroups();
		if ($user->isBlocked()
			|| (!in_array($userName, $allowedUsers)
				&& !in_array('staff', $userGroups)))
		{
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$dbr = wfGetDB(DB_REPLICA);

			$center = $req->getVal('pages-resize', '') == 'enlarge-center';
			$px = $req->getVal('pages-pixels', '0');
			$px = intval($px);

			$introPx = $req->getVal('pages-intro-pixels', 0);
			$introPx = intval($introPx);

			$pageList = $req->getVal('pages-list', '');

			if ($px < 50 || $px > 1000 ||
				($introPx && ($introPx < 50 || $introPx > 1000)))
			{
				$result = array('result' => '<i>ERROR: bad pixel value</i>');
				print json_encode($result);
				return;
			}

			$urls = self::parseURLlist($pageList);
			if (empty($urls)) {
				$result = array('result' => '<i>ERROR: no URLs given</i>');
				print json_encode($result);
				return;
			}

			self::enlargeImagesUrls($urls, $center, $px, $introPx);

			$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
			$html .= '<table class="tres"><tr><th width="400px">URL</th><th>Images changed</th><th>Error</th></tr>';
			foreach ($urls as $row) {
				$html .= "<tr><td><a href='{$row['url']}'>{$row['url']}</a></td><td>{$row['images']}</td><td>{$row['err']}</td></tr>";
			}
			$html .= '</table>';

			$result = array('result' => $html);
			print json_encode($result);
			return;
		}

		$out->setHTMLTitle('Admin - Enlarge Images - wikiHow');

		$defaultCenterPixels = self::DEFAULT_CENTER_PIXELS;
		$defaultEnlargePixels = self::DEFAULT_ENLARGE_PIXELS;
$tmpl = <<<EOHTML
<form id="images-resize" method="post" action="/Special:AdminEnlargeImages">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Enlarge Article Images
</div>
<div style="font-size: 13px; margin-bottom: 10px; border: 1px solid #dddddd; padding: 10px;">
	<div>
	<span style="margin-right: 10px;">
		<input id="rd-enlarge" type="radio" name="pages-resize" value="enlarge" checked="checked"> Enlarge </input>
	</span>
	<input id="rd-center" type="radio" name="pages-resize" value="enlarge-center"> Enlarge &amp; Center </input>
	<span style="margin-left: 50px;">
		New width in <i>Steps</i>: <input id="pixels" type="text" size="4" name="pages-pixels" value="{$defaultEnlargePixels}" /> (in pixels)
	</span>
	</div>
	<div style="padding: 5px 0 0 0;">
		<input id="rd-intro" class="intro-check" type="checkbox" name="pages-intro" checked="checked" /> <label class="intro-check" for="rd-intro">Increase intro image size to:</label>
		<input id="intro-pixels" type="text" size="4" name="pages-intro-pixels" value="{$defaultEnlargePixels}" /> (in pixels)
	</div>
</div>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of URLs such as <code style="font-weight: bold;">http://www.wikihow.com/Lose-Weight-Fast</code> to look up.  One per line.
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
			.prop('disabled', false)
			.click(function () {
				var form = $('#images-resize').serializeArray();
				$('#pages-result').html('loading ...');
				$.post('/Special:AdminEnlargeImages',
					form,
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json')
					.error(function() {});
				return false;
			});

		$('#pages-list')
			.focus();

		$('#rd-enlarge').click(function () {
			$('#pixels')
				.prop('disabled', false)
				.focus()
				.val('{$defaultEnlargePixels}');
		});

		$('#rd-center').click(function () {
			$('#pixels')
				.prop('disabled', false)
				.val('{$defaultCenterPixels}');
		});

		$('.intro-check').click(function () {
			if ($('#rd-intro:checked').length) {
				$('#intro-pixels')
					.prop('disabled', false)
					.focus();
			} else {
				$('#intro-pixels').prop('disabled', true);
			}
		});
	});
})(jQuery);
</script>
EOHTML;

		$out->addHTML($tmpl);
	}
}
