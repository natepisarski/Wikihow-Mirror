<?php

class AdminMassEdit extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		$this->specialPage = 'AdminMassEdit';
		parent::__construct("AdminMassEdit");
	}

	private function getDefaultSummary() {
		return wfMessage("mass-edit-summary");
	}

	private function getStub() {
		$date= date('Y-m-d');
		return "{{stub|date=".$date."}}";
	}

	private function getUndoStubText() {
		return "{{stub}}";
	}

	private function isAllowedText($input) {
		if ($input == $this->getStub()) {
			return true;
		}

		return false;
	}

	private function isAllowedUndoText($input) {
		if ($input == $this->getUndoStubText()) {
			return true;
		}

		return false;
	}

	private function getTitles($input) {
		if ($input == "") {
			return [];
		}

		$input = preg_split('@[\r\n]+@', $input);

		$titles = [];
		$bad = [];
		foreach ($input as $line) {
			$line = trim($line);
			$title = Misc::getTitleFromText($line);
			if ($title) {
				$titles[$title->getText()] = $title->getArticleID();
			} else {
				$title = Title::newFromText($line);
				if (!$title) {
					$bad[] = "no title for: $line.  will not process";
					continue;
				}
				$titles[$title->getText()] = $title->getArticleID();
			}
		}

		$result = new stdClass();
		$result->bad = $bad;
		$result->titles = array_unique($titles);
		return $result;
	}

	public static function getBotUser() {
		$user = User::newFromName("Stub Bot");
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	public function removeTemplateIfExists($toAdd, $summary, $titles) {
		global $wgLanguageCode;
		$user = $this->getBotUser();
		$results = [];

		foreach($titles as $titleText=>$pageId) {
			if ($pageId < 1) {
				$results[] = "will not edit $titleText because it does not exist";
				continue;
			}

			$title = Title::newFromID($pageId);
			if ( $title->isRedirect() ) {
				$results[] = "will not edit $title because it is a redirect";
				continue;
			}
			if (!$title->inNamespace(NS_MAIN)) {
				$results[] = "will not edit $title because it is not in main namespace";
				continue;
			}

			$revision = Revision::newFromTitle($title);
			if (!$revision || $revision->getId() <= 0) {
				$results[] = "will not edit $title because it has no previous revisions";
				continue;
			}

			$text = ContentHandler::getContentText( $revision->getContent() );
			// check if the template already exists
			if (strpos($text, "{{stub") === FALSE) {
				$results[] = "will not edit $title because it does not have the template we are removing";
				continue;
			}

			$text = preg_replace('/{{stub.*?}}/', '', $text);

			$content = ContentHandler::makeContent($text, $title);
			$page = WikiPage::factory( $title );
			$result = $page->doEditContent( $content, $summary, 0, false, $user);

			if ($result->value['revision'] !== null) {
				$rev = $result->value['revision'];
				$results[] = "Text removed from" . Misc::getLangBaseURL($wgLanguageCode) . "/index.php?curid=$pageId " . Misc::getLangBaseURL($wgLanguageCode) . "/$title.  New revision is: ".$rev->getId();
			} else {
				$results[] = "No text removed.  No revision made on $title";
			}
		}
		return $results;
	}

	public function addToBeginning($toAdd, $summary, $titles) {
		global $wgLanguageCode;
		$user = $this->getBotUser();
		$results = [];

		foreach($titles as $titleText=>$pageId) {
			if ($pageId < 1) {
				$results[] = "will not edit $titleText because it does not exist";
				continue;
			}

			$title = Title::newFromID($pageId);
			if ( $title->isRedirect() ) {
				$results[] = "will not edit $title because it is a redirect";
				continue;
			}
			if (!$title->inNamespace(NS_MAIN)) {
				$results[] = "will not edit $title because it is not in main namespace";
				continue;
			}

			$revision = Revision::newFromTitle($title);
			if (!$revision || $revision->getId() <= 0) {
				$results[] = "will not edit $title because it has no previous revisions";
				continue;
			}

			$text = ContentHandler::getContentText( $revision->getContent() );
			// check if the template already exists
			if (strpos($toAdd, "{{stub") !== false && strpos($text, "{{stub") !== false) {
				$results[] = "will not edit $title because it already has the template we are adding";
				continue;
			}
			$text = $toAdd . $text;

			$content = ContentHandler::makeContent($text, $title);
			$page = WikiPage::factory( $title );
			$result = $page->doEditContent( $content, $summary, 0, false, $user);

			if ($result->value['revision'] !== null) {
				$rev = $result->value['revision'];
				$results[] = "Text added to " . Misc::getLangBaseURL($wgLanguageCode) . "/index.php?curid=$pageId " . Misc::getLangBaseURL($wgLanguageCode) . "/$title.  New revision is: ".$rev->getId();
			} else {
				$results[] = "No text added.  No revision made on $title";
			}
		}
		return $results;
	}

	private function error($cause) {
		$result = array('error' => $cause);
		print json_encode($result);
	}


	public function mailUserEditsDone($user, $results) {
		if ($user->getEmail()) {
			$message = "Here are the results of the mass edit:\n\n";
			$message .= implode("\n\n", $results);
			$headers = "From: Stub Bot <wiki@wikihow.com>";
			mail($user->getEmail(), 'Stub Bot finished editing pages', $message, $headers);
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgLanguageCode, $wgIsDevServer;

		$user = $this->getUser();
		$uname = $user->getName();
		$allLangs = [ 'Chris H', 'ElizabethD' ];
		$intlOnly = [ 'Bridget8', 'AdrianaBaird' ];

		$allowed = in_array($uname, $allLangs) || ( Misc::isIntl() && in_array($uname, $intlOnly) ) || ($wgIsDevServer && $user->hasGroup('staff'));
		if (!$allowed) {
			$this->displayRestrictionError();
			return;
		}

		$request = $this->getRequest();
		$out = $this->getOutput();

		if ($request->wasPosted()) {
			ini_set('memory_limit', '512M');
			set_time_limit(0);
			ignore_user_abort(true);

			$out->setArticleBodyOnly(true);

			$action = $request->getVal('action');
			if ('update' != $action) {
				return $this->error('bad action');
			}

			$text = $request->getVal('text');

			$summary = $request->getVal('summary');
			$undo = $request->getBool('undo');

			// collect list of articles
			$titleInfo = $this->getTitles($request->getVal('articles'));
			$results = $titleInfo->bad;

			if (empty($titleInfo->titles) && empty($results)) {
				return $this->error('no articles to update');
			}

			// do the edits on the text
			if ( $undo ) {
				if (!$this->isAllowedUndoText($text)) {
					return $this->error('error: text must be {{stub}}');
				}
				$results = $results + $this->removeTemplateIfExists($text, $summary, $titleInfo->titles);
			} else {
				if (!$this->isAllowedText($text)) {
					return $this->error('text is not allowed');
				}
				$results = $results + $this->addToBeginning($text, $summary, $titleInfo->titles);
			}

			// email when done (b/c its so slow)

			self::mailUserEditsDone($this->getUser(), $results);

			$result = array("result"=>implode("<br>", $results));

			print json_encode($result);
			return;
		}

		$out->setHTMLTitle(wfMessage('pagetitle', 'Admin - Mass Article Editor'));
		$listConfigs = ConfigStorage::dbListConfigKeys();

		$tmpl = self::getGuts($listConfigs,$style);

		$out->addHTML($tmpl);
	}

	function getGuts() {
		ob_start();
?>
		<style type="text/css">
		#url-list table { width: 100%; }
		#url-list td {
			background-color: #EEE;
			padding: 5px;
		}
		#url-list td.x { text-align: center; }
		</style>
		<form method='post' action='/Special:<?= $this->specialPage ?>'>
		<h4>Add text to beginning of articles.</h4>
		<br/>
		<span>Text:</span>
		<br/>
		<input id='new-text' name='first' type='text' size="28" value='<?=$this->getStub()?>'>
		<br/><br/>
		<span>Summary:</span>
		<br/>
		<input id='summary' type='text' size="90" value='<?=$this->getDefaultSummary()?>'>
		<br/><br/>
		<span>Undo Stubbing (check if you want to remove the stub from the article rather than add it):</span>
		<br/>
		<input id='undo' type='checkbox' name='undo'>
		<br/><br/>
		<span>Articles:</span>
		<br/>
		<textarea id='article-list' type='text' rows='10' cols='70'></textarea>
		<br/>
		<button id='update' style="float:right;padding:5px;">Update</button><br/>
		<br/>
		<div id='admin-result'></div>
		<div id='url-list'></div>
		</form>

		<script>
		//remove a url from the list
		$('body').on('click', 'a.remove_link', function() {
			var rmvid = $(this).attr('id');
			$(this).hide();
			$.post('/Special:<?= $this->specialPage ?>',
				{ 'action': 'remove-line',
				  'config-key': $('#config-key').val(),
				  'id': rmvid },
				function(data) {
					if (data['error'] != '') {
						alert('Error: '+ data['error']);
					}
					$('#url-list').html(data['result']);
				},
				'json');
			return false;
		});

		(function($) {
			$(document).ready(function() {
				$('#update')
					.click(function (e) {
						e.preventDefault();
						$('#admin-result').html('saving ...');
						var undoChecked = $('#undo').is(':checked') ? 1 : 0;
						$.post('/Special:<?= $this->specialPage ?>',
							{ 'action': 'update',
							  'text': $('#new-text').val(),
							  'summary': $('#summary').val(),
							  'undo': undoChecked,
							  'articles': $('#article-list').val()
							},
							function(data) {
								if (data['error']) {
									$('#admin-result').html(data['error']);
									return;
								}
								$('#admin-result').html(data['result']);
							},
							'json');
						return false;
					})
			});
		})(jQuery);
		</script>
<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}
