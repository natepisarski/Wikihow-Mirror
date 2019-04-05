<?php
/**
 * Special:AnswerResponse
 * -----------------------
 * Landing page for links in an email we send
 * to users who asked a question that has been answered
 *
 * Email sent via QAPatrol
 *
 * Spec: https://docs.google.com/document/d/1DtqnoVQfDNtVyQ-joGJ41bpSImW0Yh-kC43sMtHVLgM/
 */

class AnswerResponse extends UnlistedSpecialPage {

	var $helpful = ''; //1 = helpful; 0 = not helpful

	public function __construct() {
		parent::__construct('AnswerResponse');
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		if ($request->getVal('a') == 'submit') {
			$out->setArticleBodyOnly(true);
			$this->writeOnTalkPage($request->getValues());
			return;
		}

		//set helpful
		$helpful = $request->getVal('helpful');
		if ($helpful == '') return;
		$this->helpful = $helpful;

		// allow redirections to mobile domain
		Misc::setHeaderMobileFriendly();

		$out->setHtmlTitle(wfMessage('qaar_title')->text());

		$out->addModuleStyles('wikihow.answer_response');
		$out->addModules('wikihow.scripts.answer_response');

		$out->addHTML($this->getHTML());
	}

	private function getHTML() {
		$loader = new Mustache_Loader_CascadingLoader([ new Mustache_Loader_FilesystemLoader(__DIR__) ]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$vars = $this->getVars();

		if ($this->helpful)
			$template = $vars['answer_tool_link'] == '' ? 'answer_response' : 'answer_response_generic';
		else
			$template = 'answer_response_not';

		$html = $m->render($template, $vars);
		return $html;
	}

	private function getVars() {
		$vars = [];
		$req = $this->getRequest();
		$qa_id = $req->getVal('qa_id','');

		if (!empty($qa_id)) {

			//base variables
			$vars = [
				'mobile_class' 				=> Misc::isMobileMode() ? 'qaar_mobile' : '',
				'hdr_txt' 						=> wfMessage('qaar_hdr_'.$this->helpful)->text(),
				'submittedTimestamp' 	=> $req->getVal('st'),
				'emailTimestamp' 			=> $req->getVal('et'),
				'qa_id' 							=> $qa_id
			];

			//get other variables based on helpfulness
			if ($this->helpful) {
				//HELPFUL
				$user = $this->getUser();
				$qadb = QADB::newInstance();
				$aq = $qadb->getArticleQuestionByArticleQuestionId($qa_id);
				if (!$aq) return [];

				if ($aq->getSubmitterUserId()) {
					$vars2 = [
						'question_prompt' => wfMessage('qaar_paragraph_1')->text(),
						'username'				=> $user->getRealName() ?: $user->getName(),
						'ip'							=> IP::sanitizeIP( $req->getIP() ),
						'head_end'				=> wfMessage('qaar_head_end')->text(),
						'example' 				=> $this->getPostText($aq),
						'textarea_ph' 		=> wfMessage('qaar_taph_1')->text(),
						'anon_checkbox' 	=> wfMessage('qaar_anon_toggle')->text(),
						'is_anon'					=> $user->isAnon() ? 1 : 0,
						'footnote' 				=> wfMessage('qaar_footnote')->text(),
						'footnote_anon'		=> wfMessage('qaar_footnote_anon')->text()
					];
				}
				else {
					//helpful, but no answerer to credit
					$vars2 = [
						'question_prompt' 	=> wfMessage('qaar_prompt_generic')->text(),
						'btn_txt' 					=> wfMessage('qaar_btn_generic')->text(),
						'answer_tool_link'	=> '/Special:AnswerQuestions'
					];
				}
			}
			else {
				//NOT HELPFUL
				$vars2 = [
					'prompt' 					=> wfMessage('qaar_prompt_0')->text(),
					'question_prompt' => wfMessage('qaar_question_prompt')->text(),
					'textarea_ph' 		=> wfMessage('qaar_taph')->text()
				];
			}

			//merge 'em
			$vars = array_merge($vars, $vars2);
		}

		return $vars;
	}

	private function getPostText($aq, $html = true) {
		//get article info
		$t = Title::newFromId($aq->getArticleId());
		$article_title = $t ? $t->getText() : '';
		if (empty($article_title)) return;

		//format article link
		$article_title = wfMessage('howto',$article_title)->text();
		if ($html) {
			$article_link = '<a href="'.$t->getLocalURL().'" target="_blank">'.$article_title.'</a>';
		}
		else {
			$article_link = '[['.$t->getPartialURL().'|'.$article_title.']]';
		}

		//the comment
		$question_text = $aq->getCuratedQuestion()->getText();
		$post_text = wfMessage('qaar_talkpage_thanks', $article_link, $question_text)->text();

		return $post_text;
	}

	/**
	 * writeOnTalkPage()
	 * post a Thank You message on an answerer's talk page (via RC Patrol)
	 *
	 * @param $post_values = all the POST data
	 */
	private function writeOnTalkPage($post_values) {
		$comment = Sanitizer::removeHTMLtags($post_values['comment']);
		$qa_id = $post_values['qa_id'];
		$from_user = $post_values['is_anon'] ? User::newFromId(0) : $this->getUser();
		if (empty($qa_id) || empty($from_user)) return;

		//load up the article question
		$qadb = QADB::newInstance();
		$aq = $qadb->getArticleQuestionByArticleQuestionId($qa_id);
		if (!$aq || empty($aq->getSubmitterUserId())) return;

		//get the TO user's talk page
		$to_user = User::newFromId($aq->getSubmitterUserId());
		$talkPage = $to_user ? $to_user->getTalkPage() : '';
		if (empty($talkPage)) return;

		$post_text = $this->getPostText($aq, false);
		$formattedComment = TalkPageFormatter::createComment( $from_user, $post_text."\n\n".$comment );

		if ($talkPage->getArticleId() > 0) {
			$rev = Revision::newFromTitle($talkPage);
			$wikitext = ContentHandler::getContentText( $rev->getContent() );
		}
		$wikitext .= "\n\n$formattedComment\n\n";

		//do it
		$page = WikiPage::factory( $talkPage );
		$content = ContentHandler::makeContent($wikitext, $page->getTitle());
		$page->doEditContent($content, wfMessage('qaar_edit_summary')->text(), 0, false, $from_user);
	}
}
