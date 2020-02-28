<?php
/**
 * Special handling for category description pages
 * Modelled after ImagePage.php
 *
 */

class WikihowUserPage extends Article {

	const DEFAULT_DISPLAY_LIMIT = 5;

	var $featuredArticles;
	var $user;
	var $isPageOwner;

	private static $hideTabs = null;

	public function __construct() {
		global $wgHooks;
		$wgHooks['ShowArticleTabs'][] = [$this, 'onShowArticleTabs'];
	}

	public static function onArticleFromTitle($title, &$page) {
		$isMobileMode = Misc::isMobileMode();

		if ($title &&
			(  $title->inNamespace(NS_USER) ||
			  ($title->inNamespace(NS_USER_KUDOS) && $isMobileMode) ||
			  ($title->inNamespace(NS_USER_TALK) && $isMobileMode) )
		) {
				$page = new WikihowUserPage($title);
		}

		return true;
	}

	public function view($u = null) {
		$ctx = $this->getContext();
		$out = $ctx->getOutput();
		$user = $ctx->getUser();
		$req = $ctx->getRequest();
		$lang = $ctx->getLanguage();

		$diff = $req->getVal( 'diff' );
		$rcid = $req->getVal( 'rcid' );
		$title = $this->getTitle();
		$this->user = ( $u ? $u : User::newFromName($title->getDBKey(), false) );

		$affectedNamespaces = array(NS_USER, NS_USER_KUDOS, NS_USER_TALK);
		if ((!$u && !in_array( $title->getNamespace(), $affectedNamespaces ) )
			|| !$this->user
			|| $title->isSubpage()
			|| isset( $diff )
			|| isset( $rcid )
		) {
			return Article::view();
		}

		if ($this->user->isAnon() && !$this->mTitle->inNamespace(NS_USER_TALK)) {
			header('HTTP/1.1 404 Not Found');
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchuser-error', 'Noarticletext_user' );
			$out->setPageTitle( $this->user->getName() );
			$out->addModuleStyles('mobile.wikihow.nosuchpage.styles');
			return;
		}

		if (! $user->isAnon()) {
			$this->isPageOwner = $user->getID() == $this->user->getID();
		} else {
			$this->isPageOwner = $user->getName() == $this->user->getName();
		}
		if ( $this->user->isBlocked() && $this->isPageOwner ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		//hack in the <title> because it disappears for some reason [sc]
		$out->setHTMLTitle( wfMessage( 'pagetitle', $lang->getNsText($title->getNamespace()) . ': ' . $this->user->getName() )->text() );
		$skin = $this->getContext()->getSkin();

		//desktop or mobile?
		$ctx = MobileContext::singleton();
		if ($ctx->shouldDisplayMobileView()) {
			$this->getMobileView(); // fix for WelcomeWagon
		} else {
			$this->getDesktopView(); // fix for WelcomeWagon
		}
	}

	private function getDesktopView() {
		$out = $this->getContext()->getOutput();
		$title = $this->getContext()->getTitle();

		$skin = $out->getSkin();

		// add profile box javascript and css
		$out->addModuleStyles('ext.wikihow.profile_box_styles');
		$out->addModules('ext.wikihow.profile_box');

		//user settings
		$checkStats = ($this->user->getOption('profilebox_stats') == 1);
		$checkStartedEdited = ($this->user->getOption('profilebox_startedEdited') == 1);
		$checkQuestionsAnswered = ($this->user->getOption('profilebox_questions_answered',1) == 1);

		$profileStats = new ProfileStats($this->user);

		$badgeData = $profileStats->getBadges();
		$out->addHTML(ProfileBox::getDisplayBadge($badgeData));

		if ($this->getContext()->getUser()->isLoggedIn()) {
			$skin->addWidget($this->getRCUserWidget()); // fix for WelcomeWagon
			$out->addModules('ext.wikihow.rcwidget');
			$out->addModuleStyles('ext.wikihow.rcwidget_styles');
		}

		if ($checkStats || $checkStartedEdited) {
			$stats = ProfileBox::fetchStats("User:" . $this->user->getName());

			if ($checkStats) {
				$out->addHTML($this->statsHTML($stats));
			}

			//articles created
			if ($checkStartedEdited) {
				$createdData = $profileStats->fetchCreatedData(self::DEFAULT_DISPLAY_LIMIT);
				$this->flagDeindexedArticles($createdData);
				$out->addHTML($this->createdHTML($createdData, $stats['created'], self::DEFAULT_DISPLAY_LIMIT));

				//thumbed up edits
				$thumbsData = $profileStats->fetchThumbsData(self::DEFAULT_DISPLAY_LIMIT);
				$this->flagDeindexedArticles($thumbsData);
				$out->addHTML($this->thumbedHTML($thumbsData, $stats['thumbs_received'], self::DEFAULT_DISPLAY_LIMIT));
			}

			if ($checkQuestionsAnswered) {
				$answeredData = $profileStats->fetchAnsweredData(self::DEFAULT_DISPLAY_LIMIT);
				$out->addHTML($this->answeredHTML($answeredData, $stats['qa_answered'], self::DEFAULT_DISPLAY_LIMIT));

			}
		}

		// should be? $this->mTitle = Title::newFromText('User:' . $this->user->getName());
		$this->mTitle = $title;
		$wp = new WikiPage($this->mTitle);
		$popts = $out->parserOptions();
		$popts->setTidy(false);
		$content = $wp->getContent();
		if ($content) {
			$parserOutput = $content->getParserOutput($this->mTitle, null, $popts, false)->getText();
			$html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_USER));
			$out->addHTML($html);
		}

		//contributions and views
		$contributions = $profileStats->getContributions();
		$views = ProfileBox::getPageViews();
		$out->addHTML(ProfileBox::getFinalStats($contributions, $views));

		$out->addHTML("<div class='clearall'></div>");
	}

	private function getMobileView() {
		$out = $this->getContext()->getOutput();

		$out->addModuleStyles('mobile.wikihow.user');
		$out->addModules([ 'ext.wikihow.profile_box', 'jquery.ui.dialog' ]);

		//head
		if ($this->mTitle->inNamespace(NS_USER) || $this->mTitle->inNamespace(NS_USER_TALK)) {
			$out->addHTML( $this->responsiveHeader() );
			$out->addModules( 'ext.wikihow.avatar' );
			$out->addModuleStyles( 'ext.wikihow.avatar_styles' );
		}
		else {
			//kudos
			$out->setPageTitle(wfMessage('user-kudos')->plain().$this->user->getName());
		}

		if ($this->getContext()->getUser()->isLoggedIn()) {
			$out->addModules('ext.wikihow.rcwidget');
			$out->addModuleStyles('ext.wikihow.rcwidget_styles');
		}

		if ($this->mTitle->inNamespace(NS_USER)) {
			//USER
			$this->getMobileUserPage();

			if ($this->getContext()->getUser()->isLoggedIn()) {
				$out->getSkin()->addWidget($this->getRCUserWidget());
			}
		}
		else {
			//USER_TALK OR USER_KUDOS
			$this->getMobileUserTalkPage();
		}
	}

	private function getMobileUserPage() {
		$html = '';

		$profileStats = new ProfileStats($this->user);
		$checkStats = ($this->user->getOption('profilebox_stats') == 1);
		$checkStartedEdited = ($this->user->getOption('profilebox_startedEdited') == 1);
		$checkQuestionsAnswered = ($this->user->getOption('profilebox_questions_answered',1) == 1);

		if ($checkStats) {
			$stats = ProfileBox::fetchStats("User:" . $this->user->getName());
			$html .= $this->statsHTML($stats);
		}

		if ($checkStartedEdited) {
			$createdData = $profileStats->fetchCreatedData(self::DEFAULT_DISPLAY_LIMIT);
			$this->flagDeindexedArticles($createdData);
			$html .= $this->createdHTML($createdData, $stats['created'], self::DEFAULT_DISPLAY_LIMIT);

			$thumbsData = $profileStats->fetchThumbsData(self::DEFAULT_DISPLAY_LIMIT);
			$this->flagDeindexedArticles($thumbsData);
			$html .= $this->thumbedHTML($thumbsData, $stats['thumbs_received'], self::DEFAULT_DISPLAY_LIMIT);
		}

		if ($checkQuestionsAnswered) {
			$answeredData = $profileStats->fetchAnsweredData(self::DEFAULT_DISPLAY_LIMIT);
			$html .= $this->answeredHTML($answeredData, $stats['qa_answered'], self::DEFAULT_DISPLAY_LIMIT);
		}

		// should be? $this->mTitle = Title::newFromText('User:' . $this->user->getName());
		$this->mTitle = $this->getTitle();
		$wp = new WikiPage($this->mTitle);
		$popts = $this->getContext()->getOutput()->parserOptions();
		$popts->setTidy(false);
		$content = $wp->getContent();
		if ($content) {
			$parserOutput = $content->getParserOutput($this->mTitle, null, $popts, false)->getText();
			$user_article = WikihowMobileTools::processDom( $parserOutput, $this->getContext()->getSkin() );
			$attributes = [ 'class' => 'user_article' ];

			if ($user_article != '') $html .= Html::rawElement('div', $attributes, $user_article);
		}

		//contributions and views
		$contributions = $profileStats->getContributions();
		$views = ProfileBox::getPageViews();
		$html .= ProfileBox::getFinalStats($contributions, $views);

		$this->getContext()->getOutput()->addHTML( $html );
	}

	private function getMobileUserTalkPage() {
		$out = $this->getContext()->getOutput();
		$user = $this->getContext()->getUser();
		$isKudosPage = $this->mTitle->inNamespace(NS_USER_KUDOS);

		//get the guts
		$wikitext = ContentHandler::getContentText( $this->getPage()->getContent() );
		$html = $out->parse($wikitext);
		$html = WikihowArticleHTML::processHTML($html);
		$html = Avatar::insertAvatarIntoDiscussion($html);


		// Remove all links to non-existent user pages
		$doc = phpQuery::newDocument($html);
		$user_msg = wfMessage('User')->text();

		foreach (pq('.de_user a.new[title^="'.$user_msg.'"]') as $a) {
			$pq_a = pq($a);
			// Replace <a class="new" ...> with <span>
			$span = pq('<span></span>')->html( $pq_a->html() );
			$pq_a->replaceWith( $span );
		}

		$html = $doc->htmlOuter();

		//add target link
		$html .= '<div id="at_the_bottom"></div>';

		if ($isKudosPage) {
			$html = '<div class="user_kudos">'.$html.'</div>';
		}
		else {
			$html = '<div class="user_talk">'.$html.'</div>';

			//add the posting form
			if ($this->user != $user->getName() && !$user->isAnon()) {
				$postcomment = new PostComment();
				list($form, $new_comment_space, $preview_space) = $postcomment->getForm(false,$this->mTitle,true,true);
				$html .= $new_comment_space.'<div class="de">'.$form.'</div>'.$preview_space;
				$bottom_link_text = wfMessage('go-to-bottom-link')->plain();
			}
			else {
				$bottom_link_text = wfMessage('go-to-bottom-link-mypage')->plain();
			}
		}

		//add a link to scroll to the bottom
		$attributes = [ 'id' => 'touchdown' ];
		if ($isKudosPage) $attributes['class'] = 'user_kudos';
		$contents = '<a href="#at_the_bottom">'.$bottom_link_text.'</a></div><div class="clearall">';
		$td = Html::rawElement('div', $attributes, $contents);

		$out->addHTML($td . $html);
	}

	private function responsiveHeader(): string {
		$context = $this->getContext();
		$viewUser = $context->getUser();
		$langCode = $context->getLanguage()->getCode();

		$profileUserName = $this->user->getName();
		$isUserPageView = Action::getActionName($context) == 'view' && $context->getTitle()->inNamespace(NS_USER);

		$isThisUser = $profileUserName == $viewUser->getName() && !$this->user->isAnon();
		$showEditability = $isThisUser && $isUserPageView;

		$bioData = ProfileBox::getPageTop($this->user, true);
		$livesIn = Html::rawElement('strong', [], $bioData['pb_live']);
		$startedOn = Html::rawElement('strong', [], $bioData['pb_regdate']);

		$avatar_url = Avatar::getAvatarURL($profileUserName);
		if ($showEditability && stristr($avatar_url, basename(Avatar::getDefaultProfile())) !== false) {
			//default; prompt to upload pic
			$avatar_url = '';
		}

		$showEmail = $isUserPageView &&
			!$viewUser->isAnon() &&
			$viewUser->isEmailConfirmed() &&
			$viewUser->isAllowed('sendemail');

		$vars = [
			'showAvatar' => $langCode == 'en',
			'avatar' => $avatar_url,
			'username' => $profileUserName,
			'showEditability' => $showEditability,
			'badges' => $isUserPageView ? $this->userBadges() : [],
			'isLoggedIn' => $viewUser->isLoggedIn(),
			'showBio' => $bioData['pb_display_show'] && $isUserPageView,
			'bioName' => $bioData['pb_display_name'],
			'location' => $bioData['pb_live'] ? wfMessage('pb-livesin', $livesIn)->text() : '',
			'startDate' => wfMessage('pb-beenonwikihow', $startedOn)->text(),
			'showEmail' => $showEmail,
			'website' => htmlspecialchars($bioData['pb_work']),
			'websiteText' => wfMessage('pb-website')->text(),
			'aboutMe' => $bioData['pb_aboutme'],
			'emailLink' => $bioData['pb_email_url'],
			'emailText' => wfMessage('email')->text().' '.$profileUserName,
			'social' => $isUserPageView ? $bioData['pb_social'] : '',
			'edit' => strtolower(wfMessage('edit')->text()),
			'clearProfile' => wfMessage('clear_profile')->text(),
			'remove' => strtolower(wfMessage('remove')->text()),
			'tabs' => $this->userTabs()
		];

		return $this->renderTemplate('responsive_header.mustache', $vars);
	}

	private function userBadges(): array {
		$badges = [];
		$profileStats = new ProfileStats($this->user);

		foreach ($profileStats->getBadges() as $badge => $state) {
			if (!$state) continue;

			$badges[] = [
				'id' => $badge,
				'name' => wfMessage('pb-badge-'.$badge)->text()
			];
		}

		return $badges;
	}

	private function userTabs(): array {
		global $IP;

		if (self::hideTabsForUserPages()) return [];

		return [
			[
				'link' => $this->user->getUserPage()->getLocalURL(),
				'class' => 'user_page',
				'text' => wfMessage('mobile-frontend-profile-title-wh')->text(),
				'icon' => file_get_contents($IP.'/extensions/wikihow/WikihowUserPage/assets/user_icon.svg'),
				'selected' => $this->getTitle()->inNamespace(NS_USER)
			],
			[
				'link' => $this->user->getTalkPage()->getLocalURL(),
				'class' => 'usertalk_page',
				'text' => wfMessage('mobile-frontend-talk-overlay-header')->text(),
				'icon' => file_get_contents($IP.'/extensions/wikihow/WikihowUserPage/assets/usertalk_icon.svg'),
				'selected' => $this->getTitle()->inNamespace(NS_USER_TALK)
			]
		];
	}

	private static function hideTabsForUserPages(): bool {
		if (!is_null(self::$hideTabs)) return self::$hideTabs;

		$context = RequestContext::getMain();

		self::$hideTabs = $context->getUser()->isAnon() &&
			!in_array( $context->getTitle()->getDBKey(), UserPagePolicy::listUserTalkAnonVisible());

		return self::$hideTabs;
	}

	private function statsHTML($stats) {
		$html = '';

		if ($stats['created'] > 0 ||
			$stats['edited'] > 0 ||
			$stats['patrolled'] > 0 ||
			$stats['viewership'] > 0 ||
			$stats['qa_answered'] > 0)
		{

			$created = '';
			if ($stats['created'] > 0) {
				if ($this->getContext()->getUser()->isAnon()) {
					$created = wfMessage('pb-articlesstarted-stat', $stats['created'])->text();
				}
				else {
					$link = '/'.SpecialPage::getTitleFor( 'Contributions', $this->user->getName() );
					$created = wfMessage('pb-articlesstarted-link', $stats['created'], $link)->text();
				}
			}

			$patrolled = '';
			if ($stats['patrolled'] > 0) {
				if ($this->getContext()->getUser()->isAnon()) {
					$patrolled = wfMessage('pb-editspatrolled', $stats['patrolled'])->text();
				}
				else {
					$link = '/'.SpecialPage::getTitleFor( 'Log') . '?type=patrol&user=' . $this->user->getName();
					$patrolled = wfMessage('pb-editspatrolled-link', $stats['patrolled'], $link)->text();
				}
			}

			$vars = [
				'pb-mystats'	=> wfMessage('pb-mystats')->text(),
				'created' 		=> $created,
				'edited' 			=> $stats['edited'] > 0 ? wfMessage('pb-articleedits', $stats['edited'])->text() : '',
				'patrolled'		=> $patrolled,
				'viewership'	=> $stats['viewership'] > 0 ? wfMessage('pb-articleviews', $stats['viewership'])->text() : '',
				'answered'		=> $stats['qa_answered'] > 0 ? wfMessage('pb-answered-stat', $stats['qa_answered'])->text() : ''
			];

			$html = $this->renderTemplate('stats_section.mustache', $vars);
		}

		return $html;
	}

	private function getRCUserWidget() {
		$html = $this->getUserWidgetData(); // fix for WelcomeWagon

		return $html;
	}

	private function getUserWidgetData() {
		if (!class_exists('RCWidget')) return '';

		$data = RCWidget::pullData($this->user->getID());

		$tmpl = new EasyTemplate( __DIR__ . '/templates/' );
		$tmpl->set_vars(array(
			'elements' => $data
		));
		$html = $tmpl->execute('rcuserwidget.tmpl.php');

		return $html;
	}

	private function createdHTML($data, $create_count, $max) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$m = new Mustache_Engine([ 'loader' => $loader ]);

		$more = false;
		if (is_array($data) && count($data) > $max) {
			array_pop($data);
			$more = true;
		}

		$vars = [
			'created_item'	=> $loader->load('created_item'),
			'items' 				=> $data,
			'article_count'	=> $create_count,
			'view_toggle' 	=> $more,
			'empty_text'		=> $this->isPageOwner ? wfMessage('pb-noarticles')->text() : wfMessage('pb-noarticles-anon')->text(),
			'intl'					=> $this->getContext()->getLanguage()->getCode() == 'en' ? '' : 'intl'
		];

		$msgKeys = [
			'pb-articlesstarted',
			'pb-articlename',
			'pb-rising',
			'pb-featured',
			'pb-views',
			'pb-viewmore'
		];
		$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));

		return $m->render('created_section.mustache', $vars);
	}

	private function thumbedHTML($data, $thumb_count, $max) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$m = new Mustache_Engine([ 'loader' => $loader ]);

		$more = false;
		if (is_array($data) && count($data) > $max) {
			array_pop($data);
			$more = true;
		}

		$vars = [
			'thumbed_item'	=> $loader->load('thumbed_item'),
			'items' 				=> $data,
			'thumb_count'		=> $thumb_count,
			'view_toggle' 	=> $more,
			'empty_text'		=> $this->isPageOwner ? wfMessage('pb-nothumbs')->text() : wfMessage('pb-noarticles-anon')->text()
		];

		$msgKeys = [
			'pb-thumbedupedits',
			'pb-articlename',
			'date',
			'pb-viewmore'
		];
		$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));

		return $m->render('thumbed_section.mustache', $vars);
	}

	private function answeredHTML($data, $answered_count, $max) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$m = new Mustache_Engine([ 'loader' => $loader ]);

		$more = false;
		if (is_array($data) && count($data) > $max) {
			array_pop($data);
			$more = true;
		}

		$vars = [
			'answered_item'				=> $loader->load('answered_item'),
			'items' 							=> $data,
			'answered_count'			=> $answered_count,
			'view_toggle' 				=> $more,
			'empty_text'					=> $this->isPageOwner ? wfMessage('pb-noanswereds')->text() : wfMessage('pb-noarticles-anon')->text()
		];

		$msgKeys = [
			'pb-questionsanswered',
			'pb-category',
			'pb-count',
			'pb-viewmore'
		];
		$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));

		return !Misc::isIntl() ? $m->render('answered_section.mustache', $vars) : '';
	}

	private function getMWMessageVars($keys) {
		$vars = [];
		foreach ($keys as $key) {
			$vars[$key] = wfMessage($key)->text();
		}
		return $vars;
	}

	/**
	 * We don't want anons to see links to deindexed articles.
	 */
	private function flagDeindexedArticles(&$data) {
		if (!$data || $this->getContext()->getUser()->isLoggedIn())
			return;

		foreach ($data as &$article) {
			$title = Title::newFromID($article['page_id']);
			$article['hide_link'] = !RobotPolicy::isTitleIndexable($title);
		}
	}

	private function renderTemplate(String $template = '', Array $vars = []): string {
		if ($template == '') return '';

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$m = new Mustache_Engine([ 'loader' => $loader ]);

		return $m->render($template, $vars);
	}

	public static function onShowArticleTabs( &$showTabs ) {
		$showTabs = !self::hideTabsForUserPages();
	}

}
