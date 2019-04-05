<?php

if ( !defined('MEDIAWIKI') ) die();

class GuidedEditor extends EditPage {

	var $whow = null;

	public function __construct( $article = null ) {
		if ($article) {
			parent::__construct( $article );
			$this->mGuided = true;
		}
	}

	public static function onCustomEdit($page, $user) {
		$ctx = $page->getContext();
		$req = $ctx->getRequest();
		return self::handleEditHooks($req, $page->mTitle, $page, 'edit', $user);
	}

	public static function onMediaWikiPerformAction( $output, $article, $title, $user, $request, $wiki ) {
		$action = $request->getVal('action');
		if ($action != 'submit2') {
			return true;
		}

		if ( session_id() == '' ) {
			// send a cookie so anons get talk message notifications
			wfSetupSession();
		}

		return self::handleEditHooks($request, $title, $article, $action, $user);
	}

	public static function possibleInGuidedEditor($request, $title, $article) {
		$newArticle = false;
		// The article can't have parts/methods, and must be parsed with the
		// parser in WikihowArticleEditor, to be editable in Guided Editor.
		$isSimpleWikihowArticle = false;
		if ($title->inNamespace(NS_MAIN)) {
			if ($request->getVal('title') == '' || $title->getArticleID() == 0) {
				$newArticle = true;
			}
			if (!$newArticle) {
				$isSimpleWikihowArticle = WikihowArticleEditor::useWrapperForEdit($article);
			}
		}
		return $newArticle || $isSimpleWikihowArticle;
	}

	private static function handleEditHooks($request, $title, $article, $action, $user) {
		$editor = $user->getOption('defaulteditor', '');
		if (!$editor) {
			$editor = $user->getOption('useadvanced', false) ? 'advanced' : 'visual';
		}

		if ($request->getVal('advanced') != 'true'
			&& ($editor != 'advanced' || $request->getVal('override') == 'yes')
			&& !$request->getVal('section')
			&& !$request->getVal('wpSection')
		) {
			// use the wrapper if it's a new article or
			// if it's an existing wikiHow article
			$possibleInGuided = self::possibleInGuidedEditor($request, $title, $article);

			// use advanced if they have already set a title and have
			// the default preference setting, so do nothing here
			if ($action != 'submit'
				&& ($action == 'submit2' || $possibleInGuided)
			) {
				$editor = new GuidedEditor( $article );
				$editor->edit();
				return false;
			}
		}

		return true;
	}

	// This method must be 'public' because it overloads the base method
	// EditPage::edit(), which is public
	public function edit() {
		$req = $this->getArticle()->getContext()->getRequest();
		$this->importFormData($req);

		if ($req->getVal("wpSave")
			&& $req->getVal("overwrite") == "yes"
			&& NewArticleBoost::isOverwriteAllowed($this->mTitle)
		) {
			// it's a rewrite. let us start anew
			$page = WikiPage::factory($this->mTitle);
			$reason = wfMessage('ac-overwrite-reason')->text();
			$status = $page->doDeleteArticleReal($reason);
			if (!$status->isGood()) {
				return;
			}
			$overwrite = true;
		} else {
			$overwrite = false;
		}

		parent::edit();

		if ($overwrite) {
			ChangeTags::addTags('article rewrite', null, $this->mArticle->getLatest());
		}
	}

	protected function importContentFormData( &$request ) {
		if ( $request->wasPosted() && !$request->getVal('wpTextbox1')) {
			$whow = WikihowArticleEditor::newFromRequest($request);
			$whow->mIsNew = false;
			$this->whow = $whow;
			$content = $this->whow->formatWikiText();
			return $content;
		} else {
			return parent::importContentFormData($request);
		}
	}

	// This method must be 'public' because it overloads the base method
	// EditPage::importFormData(), which is public
	public function importFormData( &$request ) {
		// These fields need to be checked for encoding.
		// Also remove trailing whitespace, but don't remove _initial_
		// whitespace from the text boxes. This may be significant formatting.
		parent::importFormData($request);
	}

	// Since there is only one text field on the edit form,
	// pressing <enter> will cause the form to be submitted, but
	// the submit button value won't appear in the query, so we
	// Fake it here before going back to edit().  This is kind of
	// ugly, but it helps some old URLs to still work.
	public function submit2() {
		if ( !$this->preview ) $this->save = true;
		$this->easy();
	}

	// Overload showEditForm method. Make most of conflict handling, etc of
	// Editpage::showEditForm() but use our own display
	public function showEditForm( $formCallback = null ) {
		$ctx = $this->getArticle()->getContext();
		$req = $ctx->getRequest();
		$out = $ctx->getOutput();
		$lang = $ctx->getLanguage();
		$globalTitle = $ctx->getTitle();
		$user = $ctx->getUser();

		$mustache = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)]);

		// conflict resolution
		if (!$req->wasPosted()) {
			parent::showEditForm();
		}

		$out->clearHTML();
		$out->addModules( ['jquery.ui.dialog', 'jquery.ui.autocomplete'] );
		$out->addModules( ['ext.wikihow.editor_script', 'ext.wikihow.guided_editor']);
		$out->addModules( ['ext.wikihow.popbox', 'ext.wikihow.createpage'] );

		Hooks::run( 'EditPage::showEditForm:initial', [ &$this, $out ] ) ;

		// are we called with just action=edit and no title?
		$create_article = false;
		if ( (!$req->getVal( 'title' ) || !$globalTitle->getArticleID())
			&& !$this->preview
		) {
			$create_article = true;
		}

		if (!$this->mTitle->getArticleID() && !$this->preview) { // new article
			$out->addHTML( wfMessage('newarticletext')->text() );
		}

		$nab_overwrite = ( $req->getVal('overwrite') == 'yes' && NewArticleBoost::isOverwriteAllowed($this->mTitle) );
		if ($nab_overwrite) {
			$out->addHTML("<div class='minor_section' style='background-color:#f7f7bc;'>" . wfMessage("nab_overwrite")->text() . "</div>");
		}

		// do we have a new article? if so, (re-)format the title if it's English
		$new_article_param = $req->getVal("new_article");
		if ($new_article_param && $lang->getCode() == "en") {
			$local_title = $this->mTitle->getText();
			$formatted_title = GuidedEditorHelper::formatTitle($local_title);
			$new_title_obj = Title::newFromText($formatted_title);
			$this->mTitle = $new_title_obj;
			$this->mArticle = new Article($new_title_obj);
		}

		$is_new_article = ($create_article || $new_article_param);

		$conflictWikiHow = null;
		$conflictTitle = false;
		if ( $this->isConflict ) {
			$pageHeading = wfMessage( "editconflict", $this->mTitle->getPrefixedText() );
			$out->setPageTitle( $pageHeading );
			if ($new_article_param) {
				$out->addHTML("<b><font color='red'>" . wfMessage('page-name-exists') . "</b></font><br/><br/>");
				$conflictTitle = true;
			} else {
				$this->edittime = $this->mArticle->getTimestamp();
			    $out->addHTML( wfMessage('explainconflict') );
				// let the advanced editor handle the situation
				if ($this->isConflict) {
					EditPage::showEditForm();
					return;
				}
			}

			$this->textbox2 = $this->textbox1;
			$conflictWikiHow = WikihowArticleEditor::newFromText($this->textbox1);
			$this->textbox1 = ContentHandler::getContentText( $this->mArticle->getPage()->getContent() );
			$this->edittime = $this->mArticle->getTimestamp();
		} else {
			$quotedTitle = '"' . wfMessage('howto', $this->mTitle->getPrefixedText()) . '"';
			if ($this->mTitle->getArticleID() == 0) {
				$pageHeading = wfMessage('creating', $quotedTitle);
			} else {
				$pageHeading = wfMessage('editing', $quotedTitle);
			}
			if ( $this->section ) {
				if ( $this->section == "new" ) {
					$pageHeading .= wfMessage("commentedit");
				} else {
					$pageHeading .= wfMessage("sectionedit");
				}
				if (!$this->preview) {
					$sectitle = preg_match("/^=+(.*?)=+/mi",
						$this->textbox1,
						$matches);
					if ( !empty( $matches[1] ) ) {
						$this->summary = "/* " . trim($matches[1]) . " */ ";
					}
				}
			}
			$out->setPageTitle( $pageHeading );
			if ( $this->oldid ) {
				$rev = $this->mArticle->getRevisionFetched();
				if ($rev) {
					$this->mArticle->setOldSubtitle($this->oldid);
				}
			}
		}

		if ( wfReadOnly() ) {
			$out->addHTML( "<strong>" .  wfMessage('readonlywarning') .  "</strong>" );
		}

		if ( !$create_article && $this->mTitle->isProtected( 'edit' ) ) {
			if ( $this->mTitle->isSemiProtected() ) {
				$notice = wfMessage('semiprotectedpagewarning');
				if ( wfMessage('semiprotectedpagewarning')->inContentLanguage()->isBlank() || wfMessage('semiprotectedpagewarning')->inContentLanguage()->text() === '-') {
					$notice = '';
				}
			} else {
				$notice = wfMessage('protectedpagewarning');
			}
			$out->addHTML( "<div class='minor_section'>\n " );
			$out->addWikiText( $notice );
			$out->addHTML( "</div>\n" );
		}

		$query_string = 'action=submit2&override=yes';
		if ($nab_overwrite) {
			$query_string .= '&overwrite=yes';
		}
		$action = htmlspecialchars( $this->mTitle->getLocalURL( $query_string ) );
		if ($create_article) {
			$mainpage = str_replace(' ', '-', wfMessage('mainpage'));
			$action = str_replace("&title=$mainpage", '', $action);
		}

		$copyright_link = Linker::link( wfMessage("copyrightpage") );
		$copyright_warning = wfMessage( "copyrightwarning", $copyright_link  )->plain();

		$tabindex = 14;
		$buttons = $this->getEditButtons( $tabindex );

		$buttons['preview'] = "<span id='gatGuidedPreview'>{$buttons['preview']}</span>";
		$save_button = $buttons['save'];
		$preview_button = $buttons['preview'];
		$saveBtn = str_replace('accesskey="s"', '', $buttons['save']);
		$buttons['save'] = "<span id='gatGuidedSave'>{$saveBtn}</span>";

		$all_buttons = implode( $buttons, "\n" );

		// If this is a comment, show a subject line at the top, which is also the edit summary.
		// Otherwise, show a summary field at the bottom
		$summarytext = $lang->recodeForEdit( $this->summary );
		if ($req->getVal('suggestion')) {
			$summarytext .= ($summarytext ? ', ' : '') . wfMessage('suggestion_edit_summary');
		}
		$show_edit_summary = ($this->section != 'new');
		if ($show_edit_summary) {
			if ($globalTitle->getArticleID() == 0 && $globalTitle->inNamespace(NS_MAIN) && $summarytext == '') {
				$summarytext = wfMessage('creating_new_article');
			}
		}

		// Create the wikiHow object
		if ($conflictWikiHow == null) {
			if ($this->textbox1) {
				$whow = WikihowArticleEditor::newFromText($this->textbox1);
			} else {
				$whow = WikihowArticleEditor::newFromTitle($this->mArticle->getTitle());
			}
		} else {
			$whow = $conflictWikiHow;
		}

		$suggested_title = '';
		$requested = $req->getVal('requested', '');
		if ($requested) {
			$requested_title = Title::makeTitleSafe(NS_MAIN, $requested);
			$suggested_title = $requested_title->getText();
		}

		$show_heading = ($req->getVal('title') == null || $conflictTitle || $suggested_title);

		$intro_section = $lang->recodeForEdit($whow->getSummary());
		$steps_section = $lang->recodeForEdit( $whow->getSteps(true) );
		$video_section = $lang->recodeForEdit( $whow->getSection(wfMessage('video')) );
		$tips_section = $lang->recodeForEdit( $whow->getSection(wfMessage('tips')) );
		$warnings_section = $lang->recodeForEdit( $whow->getSection(wfMessage('warnings')) );
		$ingredients_section = '';

		// Default starting sections in a new article
		if ($create_article || $whow->mIsNew) {
			if ($steps_section == '') $steps_section = "#  ";
			if ($tips_section == '') $tips_section = "*  ";
			if ($warnings_section == '') $warnings_section = "*  ";
			if ($ingredients_section == '') $ingredients_section = "*  ";
		}

		// Build categorizer option form
		$cat_string = $whow->getCategoryString();
		$category_options_form = CategoryHelper::getCategoryOptionsForm($cat_string, $whow->mCategories);

		// Display 'Switch to Advanced Editing' link
		$advanced = '';
		if (!$create_article && !$whow->mIsNew && !$conflictTitle) {
			$oldparameters = '';
			$oldid = $req->getInt('oldid');
			if ($oldid) {
				$oldparameters = "&oldid=" . $oldid;
			}
			if (!$this->preview) {
				$advanced = "<a class='' href='/index.php?title=" . $globalTitle->getPrefixedURL() . "&action=edit&advanced=true$oldparameters'>" . wfMessage('advanced-editing') . "</a>";
			}
		} elseif ($create_article && $req->getVal('title')) {
			$advanced = "<a class='button secondary' style='float:left' href='/index.php?title=" . $globalTitle->getPrefixedURL() . "&action=edit&advanced=true'>" . wfMessage('advanced-editing') . "</a>";
		}

		// Related wikiHows (maybe hidden)
		$related_vis = "hide";
		$relatedwikihows_checked = '';
		$relateds_titles = [];
		$section = $whow->getSection(wfMessage('relatedwikihows'));
		if ($section) {
			$related_vis = "show";
			$relateds_wikitext = str_replace("*", '', $section);
			$relateds_wikitext = str_replace("[[", '', $relateds_wikitext);
			$relateds_wikitext = str_replace("]]", '', $relateds_wikitext);
			$lines = explode("\n", $relateds_wikitext);
			foreach ($lines as $line) {
				$xx = strpos($line, "|");
				if ($xx !== false) {
					$line = substr($line, 0, $xx);
				}
				// Google+ hack.  We don't normally allow + but will for the Goog
				if (false === stripos($line, 'Google+')) {
					$line = trim(urldecode($line));
				}
				if ($line == '') continue;
				$relateds_titles[] = $line;
			}
			$relatedwikihows_checked = " checked='checked' ";
		}

		// Video section
		$vidpreview_vis = "hide";
		$vidbtn_vis = "show";
		$vidpreview = "<img src='" . wfGetPad('/extensions/wikihow/rotate.gif') . "'/>";
		$videoMsg = wfMessage('video');
		if ($whow->getSection($videoMsg)) {
			$vidpreview_vis = "show";
			$vidbtn_vis = "hide";
			try {
				$vidtext = $whow->getSection($videoMsg);
				$vidpreview = $out->parse($vidtext);
			} catch (Exception $e) {
				$vidpreview = "Sorry, preview is currently not available.";
			}
		}  else {
			$vidpreview = wfMessage('video_novideoyet');
		}
		$show_video_button = $user->isLoggedIn();
		$video_disabled = ($show_video_button ? '' : 'disabled');

		// Things You'll Need section (maybe hidden)
		$things_vis = "hide";
		$thingsyoullneed_section = "*  ";
		$thingsyoullneed_checked = '';
		$section = $whow->getSection(wfMessage("thingsyoullneed"));
		if ($section) {
			$things_vis = "show";
			$thingsyoullneed_section = $section;
			$thingsyoullneed_checked = " checked='checked' ";
		}

		// Ingredients section (maybe hidden)
		$ingredients_vis = "hide";
		$section = $whow->getSection(wfMessage("ingredients"));
		$ingredients_checked = '';
		if ($section) {
			$ingredients_vis = "show";
			$ingredients_section = $section;
			$ingredients_checked = " checked='checked' ";
		}

		// Sources and Citations section (maybe hidden)
		$sources_vis = "hide";
		$sources_section = "*  ";
		$sources_checked = '';
		$section = $whow->getSection(wfMessage("sources"));
		$section = str_replace('<div class="references-small"><references/></div>', '', $section);
		if ($section) {
			$sources_vis = "show";
			$sources_checked = " checked='checked' ";
			$sources_section = $section;
		}

		// references section (maybe hidden)
		$references_vis = "hide";
		$references_section = "*  ";
		$references_checked = '';

		$references = wfMessage("references");
		$section = $whow->getSection( $references );
		$section = str_replace('<div class="references-small"><references/></div>', '', $section);
		if ($section) {
			$references_vis = "show";
			$references_checked = " checked='checked' ";
			$references_section = $section;
		}
		//decho('not found', $section); exit;

		$lang_links = htmlspecialchars($whow->getLangLinks());

		$show_weave = false;
		if ( 'preview' == $this->formtype ) {
			$previewOutput = $this->getPreviewText();
			$this->showPreview( $previewOutput );
			$show_weave = true;
		} else {
			$out->addHTML( '<div id="wikiPreview"></div>' );
		}

		if ( 'diff' == $this->formtype ) {
			$out->addModules('ext.wikihow.diff_styles');
			$this->showDiff();
			$show_weave = true;
		}

		$weave_links = '';
		if ($show_weave) {
			$relBtn = $lang->getCode() == 'en' ? PopBox::getGuidedEditorButton() : '';
			$weave_links .= PopBox::getPopBoxJSGuided() . PopBox::getPopBoxDiv();
			$weave_links .= '<div class="wh_block editpage_sublinks">' . $relBtn . '</div>';
		}

		$undo_id = $req->getInt('undo', 0);
		if ($undo_id <= 0) {
			$undo_id = false;
		}

		if ( is_callable( $formCallback ) ) {
			call_user_func_array( $formCallback, [ &$out ] );
		}

		$vars = [
			'action' => $action,
			'is_english' => $lang->getCode() == 'en',
			'is_new_article' => $is_new_article,
			'nab_overwrite' => $nab_overwrite,
			'show_hidden_cats' => !$user->isLoggedIn(),
			'show_category_edit' => $user->isLoggedIn(),
			'categories_str' => $cat_string,
			'start_time' => $this->starttime,
			'edit_time' => $this->edittime,
			'requested' => $requested,
			'lang_links' => $lang_links,

			'show_heading' => $show_heading,
			'weave_links' => $weave_links,
			'maybe_hide_errors' => ($this->hookError === '' ? ' style="display:none"' : ''),
			'hook_error' => $out->parse($this->hookError),

			'intro_section' => $intro_section,
			'ingredients_section' => $ingredients_section,
			'steps_section' => $steps_section,
			'video_section' => $video_section,
			'tips_section' => $tips_section,
			'warnings_section' => $warnings_section,
			'thingsyoullneed_section' => $thingsyoullneed_section,
			'sources_section' => $sources_section,
			'references_section' => $references_section,

			'relatedHTML' => $relatedHTML,
			'all_buttons' => $all_buttons,

			'ingredients_vis' => $ingredients_vis,
			'vidbtn_vis' => $vidbtn_vis,
			'things_vis' => $things_vis,
			'related_vis' => $related_vis,
			'sources_vis' => $sources_vis,
			'references_vis' => $references_vis,
			'vidpreview_vis' => $vidpreview_vis,
			'video_disabled' => $video_disabled,
			'vidpreview' => $vidpreview,
			'section_number' => $this->section,
			'undo_id' => $undo_id,
			'suggested_title' => $suggested_title,
			'category_options_form' => $category_options_form,
			'relateds_titles' => $relateds_titles,

			'show_edit_summary' => $show_edit_summary,
			'summarytext' => $summarytext,
			'show_minor_edit_html' => $user->isAllowed('minoredit'),
			'show_watch_html' => $user->isLoggedIn(),
			'show_video_button' => $show_video_button,

			'edit_token' => ($user->isLoggedIn() ? $user->getEditToken() : EDIT_TOKEN_SUFFIX),
			'edit_token_track' => md5($user->getName() . $this->mTitle->getArticleID() . time()),
			'copyright_warning' => $copyright_warning,

			'txtarea_steps_text' => $req->getInt('txtarea_steps_text', 12),
			'txtarea_tips_text' => $req->getInt('txtarea_tips_text', 12),
			'txtarea_warnings_text' => $req->getInt('txtarea_warnings_text', 4),

			'writers_guide_url' => wfMessage('writers-guide-url'),
			'introduction_url' => wfMessage('introduction-url'),

			'introduction_msg' => wfMessage('introduction'),
			'ingredients_msg' => wfMessage('ingredients'),
			'steps_msg' => wfMessage('steps'),
			'video_msg' => wfMessage('video'),
			'tips_msg' => wfMessage('tips'),
			'warnings_msg' => wfMessage('warnings'),
			'thingsyoullneed_msg' => wfMessage('thingsyoullneed'),
			'relatedarticlestext_msg' => wfMessage('relatedarticlestext'),
			'sources_msg' => wfMessage('sources'),
			'references_msg' => wfMessage('references'),
			'relatedwikihows_msg' => wfMessage('relatedwikihows'),
			'subject_msg' => wfMessage('subject'),

			'add_image_ingredients_msg' => wfMessage('eiu-add-image-to-ingredients'),
			'add_image_steps_msg' => wfMessage('eiu-add-image-to-steps'),
			'add_image_tips_msg' => wfMessage('eiu-add-image-to-tips'),
			'add_image_warnings_msg' => wfMessage('eiu-add-image-to-warnings'),
			'add_image_thingsyoullneed_msg' => wfMessage('eiu-add-image-to-thingsyoullneed'),
			'add_categories_msg' => wfMessage('add-optional-categories'),
			'more_info_categorization_msg' => wfMessage('more-info-categorization'),
			'video_change_msg' => wfMessage('video_change'),

			'summaryinfo_msg' => wfMessage('summaryinfo'),
			'moreinfo_msg' => wfMessage('moreinfo'),
			'ingredients_tooltip_msg' => wfMessage('ingredients_tooltip'),
			'stepsinfo_msg' => wfMessage('stepsinfo'),
			'video_loggedin_msg' => wfMessage('video_loggedin')->text(),
			'videoinfo_msg' => wfMessage('videoinfo'),
			'show_preview_msg' => wfMessage('show_preview'),
			'ep_hide_preview_msg' => wfMessage('ep_hide_preview'),
			'listhints_msg' => wfMessage('listhints'),
			'optionallist_msg' => wfMessage('optionallist'),
			'items_msg' => wfMessage('items'),
			'relatedlist_msg' => wfMessage('relatedlist'),
			'relatedwikihows_url_msg' => wfMessage('related-wikihows-url'),
			// TODO update this when we fix the destination link page (writers guide)
			'sources_url_msg' => wfMessage('sources-links-url'),
			'references_url_msg' => wfMessage('sources-links-url'),
			'epw_move_up_msg' => wfMessage('epw_move_up'),
			'epw_move_down_msg' => wfMessage('epw_move_down'),
			'epw_remove_msg' => wfMessage('epw_remove'),
			'addtitle_msg' => wfMessage('addtitle'),
			'epw_add_msg' => wfMessage('epw_add'),
			'linkstosites_msg' => wfMessage('linkstosites'),
			'optional_options_msg' => wfMessage('optional_options'),
			'optionalsections_msg' => wfMessage('optionalsections'),
			'ingredients_checkbox_msg' => wfMessage('ingredients_checkbox'),
			'editdetails_msg' => wfMessage('editdetails'),
			'summaryedit_msg' => wfMessage('summaryedit'),
			'summary_msg' => wfMessage('summary'),
			'cancel_msg' => wfMessage('cancel'),
			'accesskey_minoredit_msg' => wfMessage('accesskey-minoredit'),
			'accesskey_watch_msg' => wfMessage('accesskey-watch'),
			'tooltip_minoredit_msg' => wfMessage('tooltip-minoredit'),
			'tooltip_watch_msg' => wfMessage('tooltip-watch'),
			'minoredit_msg' => wfMessage('minoredit'),
			'watchthis_msg' => wfMessage('watchthis'),
			'title_msg' => wfMessage('title'),
			'howto_msg' => wfMessage('howto', ''),
			'editcategory_msg' => wfMessage('editcategory'),

			'thingsyoullneed_checked' => $thingsyoullneed_checked,
			'relatedwikihows_checked' => $relatedwikihows_checked,
			'sources_checked' => $sources_checked,
			'references_checked' => $references_checked,
			'ingredients_checked' => $ingredients_checked,
			'minor_edit_checked' => ($this->minoredit ? " checked='checked'" : ''),
			'watchthis_checked' => ($this->watchthis ? " checked='checked'" : ''),
		];

		$html = $mustache->render('guidededitor.mustache', $vars);
		$out->addHTML($html);

		if ( $this->isConflict ) {
			$out->addModules('ext.wikihow.diff_styles');
			$out->addHTML( "<h2>" . wfMessage('yourdiff') . "</h2>\n" );
			DifferenceEngine::showDiff( $this->textbox2, $this->textbox1,
				wfMessage('yourtext')->text(), wfMessage('storedversion')->text() );
		}

		if ($user->getOption('hidepersistantsavebar', 0) == 0) {
			$vars = [
				'show_edit_summary' => $show_edit_summary,
				'summarytext' => $summarytext,
				'editsummary_msg' => wfMessage('editsummary'),
				'save_button' => $save_button,
				'preview_button' => $preview_button,
			];
			$html = $mustache->render('persistent_save_bar.mustache', $vars);
			$out->addHTML($html);
		}

		$out->addHTML( "</form></div>\n" );
	}

	public function getEditButtons( &$tabindex ) {
		$buttons = [];

		$attrs = [
			'id'        => 'wpSave',
			'name'      => 'wpSave',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMessage('savearticle'),
			'accesskey' => wfMessage('accesskey-save'),
			'title'     => wfMessage('tooltip-save') . ' [' . wfMessage('accesskey-save') . ']',
			'class'     => 'button primary submit_button wpSave',
		];
		$buttons['save'] = XML::element('input', $attrs, '');

		$attrs = [
			'id'        => 'wpPreview',
			'name'      => 'wpPreview',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMessage('showpreview'),
			'accesskey' => wfMessage('accesskey-preview'),
			'title'     => wfMessage('tooltip-preview') . ' [' . wfMessage('accesskey-preview') . ']',
			'class'     => 'button secondary submit_button',
		];
		$buttons['preview'] = XML::element('input', $attrs, '');
		$buttons['live'] = '';

		$attrs = [
			'id'        => 'wpDiff',
			'name'      => 'wpDiff',
			'type'      => 'submit',
			'tabindex'  => ++$tabindex,
			'value'     => wfMessage('showdiff'),
			'accesskey' => wfMessage('accesskey-diff'),
			'title'     => wfMessage('tooltip-diff') . ' [' . wfMessage('accesskey-diff') . ']',
			'class'     => 'button secondary submit_button',
		];
		$buttons['diff'] = XML::element('input', $attrs, '');

		Hooks::run( 'EditPageBeforeEditButtons', [ &$this, &$buttons, &$tabindex ] );

		return $buttons;
	}

	// keep the advanced editor chosen on preview and show changes
	public static function addHiddenFormInputs($that, $out, &$tabindex) {
		$ctx = RequestContext::getMain();
		$adv = $ctx->getRequest()->getVal('advanced') ? 'true' : '';
		$attrs = [
			'type' => 'hidden',
			'name' => 'advanced',
			'value' => $adv
		];
		$input = XML::element('input', $attrs);
		$out->addHTML($input);
		return true;
	}

	public static function getEditPageSideBar() {
		$ctx = RequestContext::getMain();
		$is_adv = $ctx->getRequest()->getVal('guidededitor') != '1';

		$html = '';

		// bug #1562: Show Mylinks also if a user has them
		$userLinks = WikihowSkinHelper::getUserLinks();
		if ( $userLinks ) {
			$html = Html::openElement( 'div', [ 'class' => 'sidebox' ] ) . $userLinks . Html::closeElement( 'div' );
		}

		$part_link = $ctx->getTitle()->getPrefixedURL() . "?action=edit&advanced=true";
		$vars = [
			'is_adv' => $is_adv,
			'epqt_hdr_msg' => wfMessage('epqt_hdr')->text(),
			'epqt_part_msg' => wfMessage('epqt_part')->text(),
			'epqt_step_msg' => wfMessage('epqt_step')->text(),
			'epqt_bullets_msg' => wfMessage('epqt_bullets')->text(),
			'epqt_tips_msg' => wfMessage('epqt_tips')->text(),
			'epqt_part_link_msg' => wfMessage('epqt_part_link', $part_link)->text(),
		];

		$mustache = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)]);
		$html .= $mustache->render('guidededitor_quicktips.mustache', $vars);

		return $html;
	}
}
