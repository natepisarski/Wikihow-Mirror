<?php

/*
 * A visual tool to create new wikiHow articles
 */
class ArticleCreator extends SpecialPage {
	// You can set this to false for debugging purposes
	// but it should be set true in production
	var $onlyEditNewArticles = true;

	public function __construct() {
		global $wgHooks;
		parent::__construct('ArticleCreator');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		$context = $this->getContext();
		$request = $context->getRequest();
		$out = $context->getOutput();

		if ($request->getVal('ac_created_dialog', 0)) {
			$out->setArticleBodyOnly(true);
			$out->addHtml($this->getCreatedDialogHtml());
			return;
		}

		$out->addModules( 'ext.wikihow.articlecreator_css' ); // css for the tool
		$out->addModules( 'ext.wikihow.articlecreator' ); // module to enable mw messages in javascript
		$out->addModules( 'ext.guidedTour' );  // used for showing validation responses

		if ( is_null( $request->getVal( 't' ) ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->addWikitext( 'You must specify a title to create.' );
			return;
		}

		$t = Title::newFromText($request->getVal('t'));
		if (!$t) {
			$out->addWikitext( 'Bad title specified.' );
			return;
		}
		$out->setHTMLTitle(wfMessage('ac-html-title', $t->getText()));

		$overwriteAllowed = NewArticleBoost::isOverwriteAllowed($t);

		if ($request->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$response = array();
			$token = $request->getVal('ac_token');
			if ( ! $context->getUser()->matchEditToken( $token ) ) {
				$response['error'] = wfMessage('ac-invalid-edit-token');
			} elseif ($this->onlyEditNewArticles && $t->exists() && !$overwriteAllowed) {
				$response['error'] = wfMessage('ac-title-exists', $t->getEditUrl());
			} elseif (!$t->userCan( 'create', $context->getUser(), false)) {
				$response['error'] = wfMessage('ac-cannot-create', $t->getEditUrl());
			} else {
				$response = $this->saveArticle($t, $request, $response);
			}

			$out->addHtml(json_encode($response));
		} else {
			$out->setRobotPolicy( 'noindex,nofollow' );
			if ( $this->onlyEditNewArticles && $t->exists() && !$overwriteAllowed) {
				$out->redirect($t->getEditURL());
			} else {
				$this->outputStartupHtml();
			}
		}
	}

	private function outputStartupHtml() {
		$out = $this->getContext()->getOutput();
		$request = $this->getContext()->getRequest();

		if ( is_null ( $request->getVal( 't' ) ) ) {
			$out->addWikitext( 'You must specifiy a title to create.' );
			return;
		}

		$t = Title::newFromText($request->getVal('t'));
		$advancedEditLink = Linker::linkKnown( $t, wfMessage('advanced_editing_link')->text(), array(), array('action' => 'edit', 'advanced' => 'true'), array('ac_advanced_link', 'known', 'noclasses') );

		$out->addHtml($this->getTemplatesHtml($t));

		$sections = array(
			array('name' => wfMessage('ac-section-intro-name')->text(),
					'token' => $this->getContext()->getUser()->getEditToken(),
					'advancedEditLink' => $advancedEditLink,
					'pageTitle' => $t->getText(),
					'desc' => wfMessage('ac-section-intro-desc')->text(),
					'buttonTxt' => wfMessage('ac-section-intro-button-txt')->text(),
					'placeholder' => wfMessage('ac-section-intro-placeholder')->text(),
			),
			array('name' => wfMessage('ac-section-steps-name')->text(),
					'pageTitle' => $t->getText(),
					'methodSelectorText' => wfMessage('ac-method-selector-txt')->text(),
					'addMethodButtonTxt' => wfMessage('ac-section-steps-add-method-button-txt')->text(),
			),
			array('name' => wfMessage('ac-section-tips-name')->text(),
					'desc' => wfMessage('ac-section-tips-desc')->text(),
					'buttonTxt' => wfMessage('ac-section-tips-button-txt')->text(),
					'placeholder' => wfMessage('ac-section-tips-placeholder')->text(),

			),
			array('name' => wfMessage('ac-section-warnings-name')->text(),
					'desc' => wfMessage('ac-section-warnings-desc')->text(),
					'buttonTxt' => wfMessage('ac-section-warnings-button-txt')->text(),
					'placeholder' => wfMessage('ac-section-warnings-placeholder')->text(),
			),
			array('name' => wfMessage('ac-section-references-name')->text(),
					'desc' => wfMessage('ac-section-references-desc')->text(),
					'buttonTxt' => wfMessage('ac-section-references-button-txt')->text(),
					'placeholder' => wfMessage('ac-section-references-placeholder')->text(),
			),
		);

		foreach ($sections as $section) {
			$section['idname'] = preg_replace('@\ @', '', strtolower($section['name']));
			switch ($section['name']) {
				case 'Steps':
					$out->addHTML($this->getStepsSectionHtml($section));
					break;
				case 'Introduction':
					$out->addHTML($this->getIntroSectionHtml($section));
					break;
				default:
					$out->addHTML($this->getOtherSectionHtml($section));
			}
		}
		$out->addHtml($this->getFooterHtml());
		if (NewArticleBoost::isOverwriteAllowed($t)) {
			$out->addHTML("<input type='hidden' name='overwrite' id='overwrite' value='yes' />");
		}
	}

	private function getFooterHtml() {

		$copywarn = $this->msg( 'copyrightwarning',
						Linker::link( $this->msg( 'copyrightpage' )->text() )
					)->plain();

		$vars = array(
				'copyrightwarning' => $copywarn
				);
		EasyTemplate::set_path(__DIR__.'/');
		return EasyTemplate::html('ac-footer.tmpl.php', $vars);
	}

	private function getTemplatesHtml($t) {
		$vars = array(
			'desc' => wfMessage('ac-section-steps-desc')->text(),
			'pageTitle' => $t->getText(),
			'doneButtonTxt' => wfMessage('ac-section-steps-method-done-button-txt')->text(),
			'addMethodButtonTxt' => wfMessage('ac-section-steps-add-method-button-txt')->text(),
			'buttonTxt' => wfMessage('ac-section-steps-button-txt'),
			'nameMethodPlaceholder' => wfMessage('ac-section-steps-name-method-placeholder')->text(),
			'addStepPlaceholder' => wfMessage('ac-section-steps-addstep-placeholder')->text(),
			'copyWikitextMsg' => wfMessage('ac-copy-wikitext')->text(),
		);
		EasyTemplate::set_path(__DIR__.'/');
		return EasyTemplate::html('ac-html-templates.tmpl.php', $vars);
	}

	/**
	 * Check for spam content, blocked user status and return an error. Otherwise, save article
	 * and return a success status
	 * @param $t Title of article to save
	 * @param $request Request object
	 * @param $response Response to return
	 * @return mixed
	 */
	private function saveArticle($t, $request, $response) {
		$user = $this->getContext()->getUser();
		$text = $request->getVal('wikitext');

		$contentModel = $t->getContentModel();
		$handler = ContentHandler::getForModelID( $contentModel );
		$contentFormat = $handler->getDefaultFormat();
		$content = ContentHandler::makeContent( $text, $t, $contentModel, $contentFormat );
		$status = Status::newGood();
		if (!Hooks::run('EditFilterMergedContent', array($this->getContext(), $content, &$status, '', $user, false))) {
			$response['error'] = wfMessage('ac-error-editfilter')->text();
			return $response;
		}
		if (!$status->isGood()) {
			$errors = $status->getErrorsArray(true);
			foreach ($errors as $error) {
				if (is_array($error)) {
					$error = count($error) ? $error[0] : '';
					$response['error'] = wfMessage($error)->parse();
					return $response;
				}
				if (preg_match('@^spamprotection@', $error)) {
					$response['error'] =  wfMessage('ac-error-spam')->text();
					return $response;
				}
			}

			$response['error'] =  'EditFilterMergedContent returned an error. Cannot save the article';
			return $response;
		}

		if ($user->isBlockedFrom($t)) {
			$response['error'] =  wfMessage('ac-error-blocked')->text();
			return $response;
		}

		if ($request->getVal("overwrite") == "yes") {
			//it's a rewrite. let us start anew
			$page = WikiPage::factory($t);
			$reason = wfMessage('ac-overwrite-reason')->text();
			$status = $page->doDeleteArticleReal($reason);
			if (!$status->isGood()) {
				$response['error'] =  'doDeleteArticleReal returned an error. Cannot delete the old article to overwrite it.';
				return $response;
			}
		}

		$wikiPage = WikiPage::factory($t);
		$wikitext = $request->getVal('wikitext');
		$content = ContentHandler::makeContent($wikitext, $t);
		$wikiPage->doEditContent($content, wfMessage('ac-edit-summary'));
		if ($request->getVal("overwrite") == "yes") {
			//put the article back into nab
			// NewArticleBoost::redoNabStatus($t);
			ChangeTags::addTags('article rewrite', null, $wikiPage->getLatest());
		}
		// Add an author email notification
		$aen = new AuthorEmailNotification();
		$aen->addNotification($t->getText());

		$response['success'] = wfMessage('ac-successful-publish');
		$response['url'] = $t->getFullUrl().'?new=1';
		return $response;
	}

	private function getMethodSelectorHtml() {
		EasyTemplate::set_path(__DIR__.'/');
		return EasyTemplate::html('ac-method-selector.tmpl.php');
	}

	private function getStepsSectionHtml(&$section) {
		EasyTemplate::set_path(__DIR__.'/');
		return EasyTemplate::html('ac-steps-section.tmpl.php', $section);
	}

	private function getIntroSectionHtml(&$section) {
		EasyTemplate::set_path(__DIR__.'/');

		return EasyTemplate::html('ac-intro.tmpl.php', $section);
	}

	private function getOtherSectionHtml(&$section) {
		EasyTemplate::set_path(__DIR__.'/');
		return EasyTemplate::html('ac-section.tmpl.php', $section);
	}

	private function getCreatedDialogHtml() {
		$user = $this->getUser();
		EasyTemplate::set_path(__DIR__.'/');
		$vars['dialogStyle'] = "<link type='text/css' rel='stylesheet' href='" .
			wfGetPad('/extensions/wikihow/articlecreator/ac_modal.css?rev=' . WH_SITEREV) . "' />\n" .
			"<link type='text/css' rel='stylesheet' href='/extensions/wikihow/common/font-awesome-4.2.0/css/font-awesome.min.css?rev='".WH_SITEREV."' />\n";
		$vars['anon'] = $user->isAnon();
		$vars['email'] = $user->getEmail();
		$vars['on_off'] = !$vars['anon'] && !$vars['email'] ? 'off' : 'on';
		return EasyTemplate::html('ac-created-dialog.tmpl.php', $vars);
	}

	public static function printArticleCreatedScript($t) {
		$aid = $t->getArticleId();

		// deprecated cookie?
		// setcookie('aen_dialog_check', $aid, time()+3600);
		 echo '
			<script type="text/javascript">
			var whNewLoadFunc = function() {
				var url = "/extensions/wikihow/common/jquery.simplemodal.1.4.4.min.js";
				$.getScript(url, function() {
					$.get("/Special:ArticleCreator?ac_created_dialog=1", function(data) {
						$.modal(data, {
							zIndex: 100000007,
							maxWidth: 400,
							minWidth: 400,
							minHeight: 600,
							overlayCss: { "background-color": "#000" },
							escClose: false,
							overlayClose: false
						});
						$.getScript("/extensions/wikihow/articlecreator/ac_modal.js");
					});
				});
			};

			$(window).load(whNewLoadFunc);

			</script>
		';
	}

	public static function onEditFormPreloadText( &$text, &$title ) {
		$req = RequestContext::getMain()->getRequest();
		$wikitext = $req->getVal('ac_wikitext');
		if ($wikitext) {
			$text = $wikitext;
		}
		return true;
	}
}
