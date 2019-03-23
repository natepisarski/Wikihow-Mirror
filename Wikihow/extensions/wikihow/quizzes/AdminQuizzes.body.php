<?php

class AdminQuizzes extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminQuizzes');
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist($pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$urls = array();
		$dbr = wfGetDB(DB_REPLICA);
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$quiz = preg_replace('@http://www.wikihow.com/Quiz/@','',$url);
				$err = self::validateQuiz($quiz,$dbr);
				$urls[] = array('url' => $url, 'quiz' => $quiz, 'err' => $err);
			}
		}
		return $urls;
	}

	private static function validateQuiz($quiz,$db) {
		//is it in the main db table?
		$res = $db->select('quizzes','*',array('quiz_name' => $quiz), __METHOD__);
		if (!$res->fetchObject()) return 'invalid quiz';

		//make sure it isn't linked from any articles
		$res = $db->select('quiz_links','*',array('ql_name' => $quiz), __METHOD__);
		if ($res->fetchObject()) return 'Still linked from articles';

		//still here?
		return '';
	}

	private static function fourOhFourQuizzes($urls) {
		$quiz_array = array();

		//gather up the articles
		foreach ($urls as $url) {
			if (!$url['err']) {
				$quiz_array[] = $url['quiz'];
			}
		}

		//do the deletes
		if ($quiz_array) {
			$dbw = wfGetDB(DB_MASTER);
			$result = '';

			//ready it for the db
			$the_quizzes = implode("','",$quiz_array);
			$the_quizzes = "('".$the_quizzes."')";

			//remove the quiz from quizzes
			$res = $dbw->delete('quizzes', array('quiz_name' => $quiz_array), __METHOD__);
			if ($res) $result .= '<p>Quizzes removed.</p>';
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
		if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotPolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($_SERVER['HTTP_HOST'] != 'parsnip.wikiknowhow.com') {
			$wgOut->redirect('https://parsnip.wikiknowhow.com/Special:AdminQuizzes');
		}

		if ($wgRequest->wasPosted()) {
			// this may take a while...
			set_time_limit(0);

			$wgOut->setArticleBodyOnly(true);
			$action = $wgRequest->getVal('action');

			if ($action == 'process') {
				$maintDir = getcwd() . '/extensions/wikihow/quizzes';
				system("cd $maintDir");
				system("php QuizImport.php");
				$result = array('result' => 'done');
			} else {
				//NOT READY YET
				return;
				$pageList = $wgRequest->getVal('pages-list', '');

				$urls = self::parseURLlist($pageList);
				if (empty($urls)) {
					$result = array('result' => '<i>ERROR: no URLs given</i>');
					print json_encode($result);
					return;
				}

				$res = self::fourOhFourQuizzes($urls);

				$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
				$html .= $res.'<table class="tres"><tr><th width="400px">URL</th><th>Error</th></tr>';
				foreach ($urls as $row) {
					$html .= "<tr><td><a href='{$row['url']}'>{$row['quiz']}</a></td><td>{$row['err']}</td></tr>";
				}
				$html .= '</table>';

				$result = array('result' => $html);
			}
			print json_encode($result);

			return;
		}

		$wgOut->setHTMLTitle('Admin - Quizzes - wikiHow');
		$userEmail = $wgUser->getEmail();

$tmpl = <<<EOHTML
<form id="images-resize" method="post" action="/Special:AdminQuizzes">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Admin Quizzes
</div>

<h3>Import Quizzes</h3>
<p style='margin-bottom: 15px;'><input type='button' value='Import now!' id='import_quizzes' style='padding: 5px;' /> <span id='import_result'></span></p>

<h3>404 Quizzes</h3>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	<p>Enter a list of URLs to process.</p>
	<p>Example: <code style="font-weight: bold;">http://www.wikihow.com/Quiz/Tie-a-Bow-Tie</code></p>
	<p>One per line.</p>
</div>
<input type='hidden' name='action' value='remove' />
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
				$.post('/Special:AdminQuizzes',
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

		$('#import_quizzes').click(function () {
			$('#import_quizzes').attr('disabled', 'disabled');
			$.post('/Special:AdminQuizzes',
				{ action: "process" },
				function(data) {
					$('#import_result').html('<i>import done</i>');
					 $('#import_quizzes').removeAttr('disabled');
				},
				'json');
			return false;
		});

	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}
