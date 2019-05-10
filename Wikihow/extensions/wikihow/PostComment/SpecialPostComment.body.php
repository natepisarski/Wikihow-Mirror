<?php

class PostComment extends UnlistedSpecialPage {

	var $revId = null;

	public function __construct() {
		parent::__construct( 'PostComment' );
	}

	/*
	 * returns string for desktop
	 * returns array for mobile
	*/
	public function getForm($new_window = false, $title = null, $return_result = false, $isMobile = false) {
		$postbtn = " class= 'button primary' ";
		$prevbtn = " class= 'button secondary' ";

		if ($title == null)
			$title = $this->getTitle();

		if (!$title->userCan('edit', $this->getUser())) {
			return;
		}

		if ( !$this->getUser()->isAllowed('edit') ) {
			return;
		}

		$action = $this->getRequest()->getVal('action');

		// Only allow this extension on talk pages
		if (!$title->isTalkPage() || $action || $this->getRequest()->getVal('diff'))
			return;

		if ($title->inNamespace(NS_TALK) && !$this->getUser()->isLoggedIn()) {
			return;
		}

		if (!$title->userCan('edit')) {
			print  wfMessage('postcomment_discussionprotected');
			return;
		}

		$user_str = "";
		if ($this->getUser()->getID() == 0) {
			$user_str = wfMessage('postcomment_notloggedin')->text();
		} else {
			$link = Linker::link($this->getUser()->getUserPage(), $this->getUser()->getName());
			$user_str = wfMessage('postcomment_youareloggedinas', $link)->text();
		}

		$msg = wfMessage('postcomment_addcommentdiscussionpage');
		$previewPage = Title::makeTitle(NS_SPECIAL, "PostCommentPreview");
		$captchaPage = Title::makeTitle(NS_SPECIAL, "PostCommentCaptcha");
		$me = Title::makeTitle(NS_SPECIAL, "PostComment");

		$pc = Title::newFromText("PostComment", NS_SPECIAL);
		if ($title->inNamespace(NS_USER_TALK)) {
			$msg = wfMessage('postcomment_leaveamessagefor', $title->getText())->escaped();
		}

		$id = rand(0, 10000);
		$newpage = $title->getArticleId() == 0 ? "true" : "false";

		$fc = null;
		$pass_captcha = true;
		if ($this->getUser()->getID()== 0) {
			 $fc = new FancyCaptcha();
		}
		$future_comment = "<div id='postcomment_newmsg_$id'></div>";
		$preview_place = "<div id='postcomment_preview_$id' class='postcomment_preview'></div>";
		$result = "
			<script type='text/javascript'>
				var gPreviewText = \"" . wfMessage('postcomment_generatingpreview') . "\";
				var gPreviewURL = \"{$previewPage->getFullURL()}\";
				var gPostURL = \"{$me->getFullURL()}\";
				var gCaptchaURL = \"{$captchaPage->getFullURL()}\";
				var gPreviewMsg = \"" . wfMessage('postcomment_previewmessage') . "\";
				var gNewpage = {$newpage};
			</script>
			<script type='text/javascript' src='" . wfGetPad('/extensions/min/f/extensions/wikihow/PostComment/postcomment.js?') . WH_SITEREV . "'></script>
			<div id='postcomment_progress_$id' style='display: none;'><center><img src='" . wfGetPad('/skins/owl/images/wh_loading70x70.gif') . "' style='width:auto; height:auto' /></center></div>
			";

		// Include google analytics tracking (gat)
		if ( $this->getTitle()->inNamespace(NS_TALK) ) {
			$result .= "<form id=\"gatDiscussionPost\" name=\"postcommentForm_$id\" method=\"post\" action=\"{$pc->getFullURL()}\" " . ($new_window ? "target='_blank'" :"") ." onsubmit='return postcommentPublish(\"postcomment_newmsg_$id\", document.postcommentForm_$id);' class='postcomment_form'>" ;
		} elseif ( $this->getTitle()->inNamespace(NS_USER_TALK) ) {
			$result .= "<form id=\"gatTalkPost\" name=\"postcommentForm_$id\" method=\"post\" action=\"{$pc->getFullURL()}\" " . ($new_window ? "target='_blank'" :"") ." onsubmit='return postcommentPublish(\"postcomment_newmsg_$id\", document.postcommentForm_$id);' class='postcomment_form'>" ;
		} else {
			$result .= "<form name=\"postcommentForm_$id\" method=\"post\" action=\"{$pc->getFullURL()}\" " . ($new_window ? "target='_blank'" :"") ." onsubmit='return postcommentPublish(\"postcomment_newmsg_$id\", document.postcommentForm_$id);' class='postcomment_form'>" ;
		}

		$user_icon = '';
		$mobile_avatar = '';
		$avatar = Avatar::getAvatarURL($this->getUser()->getName());
		if ($avatar) {
			if ($isMobile) {
				$mobile_avatar = "<div class=\"postcomment_usericon\"><img src=\"".$avatar."\" /></div>";
			}
			else {
				$user_icon = 'background-image: url('.$avatar.')';
			}
		}

		$result .= "
			<input name=\"target\" type=\"hidden\" value=\"" . htmlspecialchars($title->getPrefixedDBkey()) . "\"/>
			<a name=\"postcomment\"></a>
			<a name=\"post\"></a>
			".$mobile_avatar."
			<textarea class=\"postcommentForm_textarea\" tabindex='3' rows='15' cols='100' name=\"comment_text_$id\" id=\"comment_text_$id\" placeholder=\"$msg\" style=\"$user_icon\"></textarea>
			<div class=\"postcommentForm_buttons\">
				<input tabindex='4' type='button' onclick='postcommentPreview(\"$id\");' value=\"".wfMessage('postcomment_preview')."\" {$prevbtn} />
				<input tabindex='5' type='submit' value=\"".wfMessage('postcomment_post')."\" id='postcommentbutton_{$id}' {$postbtn} />
			</div>
			<div class=\"postcommentForm_details\">
				$user_str
				"  . ($pass_captcha ? "" : "<br><br/><font color='red'>Sorry, that phrase was incorrect, try again.</font><br/><br/>") . "
				" . ($fc == null ? "" : $fc->getForm('') ) . "
			</div>
			</form>
			";

		//add anchor link
		$result = '<a name="leave-a-message" id="leave-a-message"></a>'.$result;

		if ($isMobile) {
			//need to be able to put that future comment space
			//in JUST the right place
			$result = array($result,$future_comment,$preview_place);
		} else {
			if ($title->inNamespace(NS_TALK)) {
				$articleTitle = Title::newFromText($title->getText());
				$cta = "";
				if ($articleTitle && $articleTitle->exists()) {
					$link = Linker::link(
						$articleTitle,
						wfMessage('postcomment_discussion_cta_link_text')->text(),
						array(),
						array('action' => 'edit')
					);
					$cta = wfMessage('postcomment_discussion_cta', $link)->text();
				}

				$result = $cta . $result;
			}

			$error_box = "<div class='captcha-warning hidden' id='error-box'></div>";
			$result = $future_comment . $result . $error_box . $preview_place;
		}

		if ($return_result) {
			return $result;
		} else {
			print $result;
		}
	}

	public function execute($par) {
		$this->writeOutput($par);

		if ($this->getRequest()->getVal('jsonresponse') == 'true') {
			$this->getRequest()->response()->header('Content-type: application/json');
			// NOTE: must use disable() to be able to sent Content-Type response header
			$this->getOutput()->disable();
			print json_encode( array( 'html' => $this->getOutput()->getHTML(),
									'revId' => $this->revId ) );
		}
	}

	private function writeOutput($par) {
		global $wgSitename, $wgWhitelistEdit, $wgParser;

		$this->getOutput()->setRobotPolicy( "noindex,nofollow" );

		$target = !empty($par) ? $par : $this->getRequest()->getVal("target");
		$t = Title::newFromDBKey($target);
		$update = true;

		if (!$t || !$t->userCan('edit')) {
			return;
		}

		if ( !$this->getUser()->isAllowed('edit') ) {
			return;
		}

		$article = new Article($t);

		$user = $this->getUser();

		$comment = $this->getRequest()->getVal("comment_text");
		foreach ($this->getRequest()->getValues() as $key=>$value) {
			if (strpos($key, "comment_text") === 0) {
				$comment = $value;
				break;
			}
		}
		$topic = $this->getRequest()->getVal("topic_name");

		// remove leading space, tends to be a problem with a lot of talk page comments as it breaks the
		// HTML on the page
		$comment = preg_replace('/\n[ ]*/', "\n", trim($comment));

		// Check to see if the user is also getting a thumbs up. If so, append the thumbs message and give a thumbs up
		if ($this->getRequest()->getVal('thumb')) {
			$comment .= "\n\n" . wfMessage('qn_thumbs_up');
			$userName = explode(":", $this->getRequest()->getVal('target'));
			ThumbsUp::quickNoteThumb($this->getRequest()->getVal('revold'), $this->getRequest()->getVal('revnew'), $this->getRequest()->getVal('pageid'), $userName[1]);
		}

		$formattedComment = TalkPageFormatter::createComment( $user, $comment );

		if ($this->getRequest()->getVal('fromajax') == 'true') {
			$this->getOutput()->setArticleBodyOnly(true);
		}
		$text = "";
		$r = Revision::newFromTitle($t);
		if ($r) {
			$text = ContentHandler::getContentText( $r->getContent() );
		}

		$text .= $formattedComment;
		$this->getOutput()->setStatusCode(409);

		$tmp = "";
		if ( !$this->getUser()->getID() && $wgWhitelistEdit ) {
			$this->userNotLoggedInPage();
			return;
		}
		if ( $this->getUser()->isBlocked() ) {
			throw new UserBlockedError( $this->getUser()->getBlock() );
		}
		if ( wfReadOnly() ) {
			$this->getOutput()->readOnlyPage();
			return;
		}

		if ($target == "Spam-Blacklist") {
			$this->getOutput()->readOnlyPage();
			return;
		}

		if ( $this->getUser()->pingLimiter() ) {
			throw new ThrottledError;
		}

		$editPage = new EditPage($article);
		$contentModel = $t->getContentModel();
		$handler = ContentHandler::getForModelID( $contentModel );
		$contentFormat = $handler->getDefaultFormat();
		$content = ContentHandler::makeContent( $text, $t, $contentModel, $contentFormat );
		$status = Status::newGood();
		if (!Hooks::run('EditFilterMergedContent', array($this->getContext(), $content, &$status, '', $user, false))) {
			return;
		}
		if (!$status->isGood()) {
			$errors = $status->getErrorsArray(true);
			foreach ($errors as $error) {
				if (is_array($error)) {
					$error = count($error) ? $error[0] : '';
				}
				if (preg_match('@^spamprotection@', $error)) {
					$message = 'Error: found spam link';
					$this->getOutput()->addHTML($message);
					return;
				}
			}
			$this->getOutput()->addHTML("Sorry, your comment couldn't be posted, please try again later.");
			return;
		}

		$matches = array();
		$preg = "/https?:\/\/[^] \n'\">]*/";
		$mod = str_ireplace('https://www.wikihow.com', '', $comment);
		preg_match_all($preg, $mod, $matches);

		if (sizeof($matches[0] ) > 2) {
			$this->getOutput()->showErrorPage("postcomment", "postcomment_urls_limit");
			return;
		}

		if (trim(strip_tags($comment)) == ""  ) {
			$this->getOutput()->showErrorPage( "postcomment", "postcomment_nopostingtoadd");
			return;
		}

		if ( !$t->userCan('edit')) {
		   $this->getOutput()->showErrorPage( "postcomment", "postcomment_discussionprotected");
		   return;
		}

		$watch = false;
		if ($this->getUser()->getID() > 0) {
		   $watch = $this->getUser()->isWatched($t);
		}

		$fc = new FancyCaptcha();
		$pass_captcha = $fc->passCaptcha();

		if (!$pass_captcha && $this->getUser()->getID() == 0) {
			$this->getOutput()->addHTML("Sorry, please enter the correct word.");
			return;
		}

		$wikiPage = WikiPage::factory($t);
		$content = ContentHandler::makeContent($text, $t);
		$wikiPage->doEditContent($content, "");

		if ($this->getRequest()->getVal('jsonresponse') == 'true') {
			$this->revId = $article->getRevIdFetched();
		}

		// Notify users of usertalk updates
		if ( $t->inNamespace(NS_USER_TALK) ) {
			AuthorEmailNotification::notifyUserTalk($t->getArticleID(), $this->getUser()->getID(), $comment);
		}


		$this->getOutput()->setStatusCode(200);

		// Inject avatar into the comment for output, we've already edited the page
		$avatar = '<img src="' . Avatar::getAvatarURL( $this->getUser()->getName() ) . '">';

		// Context title must be set in order to properly transform some templates
		$this->getContext()->setTitle($t);

		$commentHTML =  $this->getOutput()->parse( "\n" . $wgParser->preSaveTransform( $formattedComment, $t, $this->getUser(), new ParserOptions() ) );
		$newComment = preg_replace( '@de_user\"><a@', 'de_user">' . $avatar . "<a", $commentHTML );

		if ($this->getRequest()->getVal('fromajax') == 'true') {
			$this->getOutput()->redirect('');
			$this->getOutput()->addHTML( $newComment );

			return;
		}
	}

	public function isMobileCapable() {
		return true;
	}
}

class PostcommentPreview extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct( 'PostcommentPreview' );
	}

	public function execute($par) {
		global $wgParser;

		$user = $this->getUser();

		$comment = $this->getRequest()->getVal("comment");
		$comment = preg_replace('/\n[ ]*/', "\n", trim($comment));

		$formattedComment = TalkPageFormatter::createComment( $user, $comment );
		$formattedComment = $wgParser->preSaveTransform($formattedComment, $this->getTitle(), $user, new ParserOptions() );
		$result = $this->getOutput()->parse($formattedComment);
		$this->getOutput()->setArticleBodyOnly(true);
		$this->getOutput()->addHTML($result);
	}

	public function isMobileCapable() {
		return true;
	}
}

/**
 * Return the HTML for a captcha. It is used to provide a new captcha on demand
 * via AJAX when the user requests it.
 */
class PostCommentCaptcha extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct( 'PostCommentCaptcha' );
	}

	public function execute($par) {
		$fc = new FancyCaptcha();
		$this->getOutput()->setArticleBodyOnly(true);
		$this->getOutput()->addHTML($fc->getForm());
	}
}
