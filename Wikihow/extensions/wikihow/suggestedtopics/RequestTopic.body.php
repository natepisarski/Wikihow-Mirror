<?php

class RequestTopic extends SpecialPage {

	public function __construct() {
		parent::__construct('RequestTopic');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		if ($this->getLanguage()->getCode() != 'en') {
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			$out->setRobotPolicy('noindex,nofollow');
			return;
		}

		$out->setPageTitle(wfMessage('suggest_header')->text());

		$mustacheEngine = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
		]);

		$fancyCaptcha = new FancyCaptcha();
		$passCaptcha = !$req->wasPosted() || $fancyCaptcha->passCaptcha();

		if ($req->wasPosted() && $passCaptcha) {
			$dbr = wfGetDB(DB_REPLICA);

			$titleTxt = GuidedEditorHelper::formatTitle($req->getVal('suggest_topic'));
			$title = Title::newFromText($titleTxt);
			if (!$title) {
				$out->addHTML("There was an error instantiating this title.");
				return;
			}

			// Does the request exist as an article?
			if ($title->getArticleID()) {
				$vars = [ 'title' => $title->getText(), 'url' => $title->getFullURL() ];
				$html = $mustacheEngine->render('request_topic_article_exists.mustache', $vars);
				$out->addHTML($html);
				return;
			}

			// Does the request exist in the list of suggested titles?
			$email = $req->getVal('suggest_email');
			if (!$req->getCheck('suggest_email_me_check')) {
				$email = '';
			}

			$count = $dbr->selectField('suggested_titles', 'count(*)', ['st_title' => $title->getDBKey()]);
			$dbw = wfGetDB(DB_MASTER);
			if ($count == 0) {
				$dbw->insert('suggested_titles', [
					'st_title'		=> $title->getDBKey(),
					'st_user'		=> $user->getID(),
					'st_user_text'	=> $user->getName(),
					'st_isrequest'	=> 1,
					'st_category'	=> $req->getVal('suggest_category'),
					'st_suggested'	=> wfTimestampNow(),
					'st_notify'		=> $email,
					'st_source'		=> 'req',
					'st_key'		=> TitleSearch::generateSearchKey($titleTxt),
					'st_group'		=> rand(0, 4)
				]);
			} elseif ($email) {
				// request exists lets add the user's email to the list of notifications
				$existing = $dbr->selectField('suggested_titles', 'st_notify', ['st_title' => $title->getDBKey()]);
				if ($existing) {
					$email = "$existing, $email";
				}
				$dbw->update('suggested_titles',
					['st_notify' => $email],
					['st_title' => $title->getDBKey()]
				);
			}
			$out->addModules(['ext.wikihow.SuggestedTopics']);
			$vars = [ 'title' => $title->getText(), 'url' => $title->getFullURL() ];
			$html = $mustacheEngine->render('request_topic_confirmation.mustache', $vars);
			$out->addHTML($html);
			return;
		}

		$out->setHTMLTitle('Requested Topics - wikiHow');
		$out->setRobotPolicy('noindex,nofollow');

		$out->addModules(['ext.wikihow.SuggestedTopics']);

		$out->addHTML($mustacheEngine->render('request_topic_form.mustache', [
			'cats' => self::getCategoryOptions(),
			'catpcha_form' => $fancyCaptcha->getForm(),
			'user_email' => $user->getEmail(),
			'msg' => [
				'captcha_error' => $passCaptcha ? '' : wfMessage('suggest_captcha_failed')->text(),
				'subheader' => wfMessage('request_topic_subheader')->text(),
				'howto_label' => wfMessage('request_topic_howto_label')->text(),
				'category_label' => wfMessage('request_topic_category_label')->text(),
				'category_please' => wfMessage('request_topic_category_please')->text(),
				'notification_label' => wfMessage('request_topic_notification_label')->text(),
				'email_me' => wfMessage('request_topic_email_me')->text(),
				'view_current_list' => wfMessage('request_topic_view_current_list')->text(),
			]
		]));
	}

	private static function getCategoryOptions() {
		// Only do this for logged in users
		$title = Title::newFromDBKey("WikiHow:" . wfMessage('requestcategories')->text() );
		$revision = Revision::newFromTitle($title);
		if (!$revision) {
			return '';
		}

		$categs = explode("\n", ContentHandler::getContentText( $revision->getContent() ));
		$opts = '';
		foreach ($categs as $line) {
			$line = trim($line);
			if ($line == '' || strpos($line, '[[') === 0) {
				continue;
			}
			$tokens = explode(':', $line);
			$val = '';
			$val = trim($tokens[sizeof($tokens) - 1]);
			$opts[] = [ 'value' => $val, 'text' => $line ];
		}
		return $opts;
	}

}
