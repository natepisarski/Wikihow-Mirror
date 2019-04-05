<?php
/**
 * Special handling for category description pages
 * Modelled after ImagePage.php
 *
 */

class WikihowUserPage extends Article {

	var $featuredArticles;
	var $user;
	var $isPageOwner;

	public static function onArticleFromTitle($title, &$page) {
		$ctx = MobileContext::singleton();
		if ($title &&
			($title->inNamespace(NS_USER) ||
			($title->inNamespace(NS_USER_KUDOS) && $ctx->shouldDisplayMobileView()) ||
			($title->inNamespace(NS_USER_TALK) && $ctx->shouldDisplayMobileView()))) {
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
		}

		if ($checkStats || $checkStartedEdited) {
			$stats = ProfileBox::fetchStats("User:" . $this->user->getName());

			if ($checkStats) {
				$out->addHTML($this->statsHTML($stats));
			}

			//articles created
			if ($checkStartedEdited) {
				$max = 5;
				$createdData = $profileStats->fetchCreatedData($max);
				$this->flagDeindexedArticles($createdData);
				$out->addHTML($this->createdHTML($createdData, $stats['created'], $max));

				//thumbed up edits
				$max = 5;
				$thumbsData = $profileStats->fetchThumbsData($max);
				$this->flagDeindexedArticles($thumbsData);
				$out->addHTML($this->thumbedHTML($thumbsData, $stats['thumbs_received'], $max));
			}

			if ($checkQuestionsAnswered) {
				$max = 5;
				$answeredData = $profileStats->fetchAnsweredData($max);
				$out->addHTML($this->answeredHTML($answeredData, $stats['qa_answered'], $max));

			}
		}

		// should be? $this->mTitle = Title::newFromText('User:' . $this->user->getName());
		$this->mTitle = $title;
		$wp = new WikiPage($this->mTitle);
		$popts = $out->parserOptions();
		$popts->setTidy(true);
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
		$lang = $this->getContext()->getLanguage();

		$out->addModuleStyles('mobile.wikihow.user');
		$out->addModules('mobile.wikihow.userscript');

		//head
		if ($this->mTitle->inNamespace(NS_USER) || $this->mTitle->inNamespace(NS_USER_TALK)) {
			$avatar = ($lang->getCode() == 'en') ? Avatar::getAvatarURL($this->user->getName()) : "";
			$tabs = ($this->user->isAnon()) ? '' : $this->getUserHeadTabs($this->user->getName());
			$header = '<div id="user_head">
						<img src="'.$avatar.'" style="width:60px" class="avatar" width="60" height="60" />
						<p>'.$this->user->getName().'</p>
						'.$tabs.'
						</div>';
			$out->addHTML($header);
		}
		else {
			//kudos
			$out->setPageTitle(wfMessage('user-kudos')->plain().$this->user->getName());
		}

		if ($this->mTitle->inNamespace(NS_USER)) {
			//USER
			$this->getMobileUserPage();
		}
		else {
			//USER_TALK OR USER_KUDOS
			$this->getMobileUserTalkPage();
		}
	}

	private function getMobileUserPage() {
		$out = $this->getContext()->getOutput();

		//general info
		$out->addHTML(ProfileBox::getPageTop($this->user,true));

		//user settings
		$profileStats = new ProfileStats($this->user);
		$checkStats = ($this->user->getOption('profilebox_stats') == 1);

		//badges
		$badgeData = $profileStats->getBadges();
		$out->addHTML(ProfileBox::getDisplayBadgeMobile($badgeData));

		//stats
		if ($checkStats) {
			$stats = ProfileBox::fetchStats("User:" . $this->user->getName());
			$out->addHTML($this->statsHTML($stats, true));
		}
	}

	private function getMobileUserTalkPage() {
		$out = $this->getContext()->getOutput();
		$user = $this->getContext()->getUser();

		//get the guts
		$wikitext = ContentHandler::getContentText( $this->getPage()->getContent() );
		$html = $out->parse($wikitext);
		$html = WikihowArticleHTML::processHTML($html);
		$html = Avatar::insertAvatarIntoDiscussion($html);

		//add target link
		$html .= '<div id="at_the_bottom"></div>';

		if ($this->mTitle->inNamespace(NS_USER_KUDOS)) {
			$html = '<div class="user_kudos">'.$html.'</div>';
		}
		else {
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
		$html = '<div id="touchdown"><a href="#">'.$bottom_link_text.'</a></div><div class="clearall"></div>'.$html;

		$out->addHTML($html);
	}

	private function statsHTML($stats, $is_mobile = false) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = '';

		if ($stats['created'] > 0 ||
			$stats['edited'] > 0 ||
			$stats['patrolled'] > 0 ||
			$stats['viewership'] > 0 ||
			$stats['qa_answered'] > 0)
		{

			$created = '';
			if ($stats['created'] > 0) {
				if ($this->getContext()->getUser()->isAnon() || $is_mobile) {
					$created = wfMessage('pb-articlesstarted-stat', $stats['created'])->text();
				}
				else {
					$link = '/'.SpecialPage::getTitleFor( 'Contributions', $this->user->getName() );
					$created = wfMessage('pb-articlesstarted-link', $stats['created'], $link)->text();
				}
			}

			$patrolled = '';
			if ($stats['patrolled'] > 0) {
				if ($this->getContext()->getUser()->isAnon() || $is_mobile) {
					$patrolled = wfMessage('pb-editspatrolled', $stats['patrolled'])->text();
				}
				else {
					$link = '/'.SpecialPage::getTitleFor( 'Log') . '?type=patrol&user=' . $this->user->getName();
					$patrolled = wfMessage('pb-editspatrolled-link', $stats['patrolled'], $link)->text();
				}
			}

			$vars = [
				'mobile'			=> $is_mobile,
				'pb-mystats'	=> wfMessage('pb-mystats')->text(),
				'created' 		=> $created,
				'edited' 			=> $stats['edited'] > 0 ? wfMessage('pb-articleedits', $stats['edited'])->text() : '',
				'patrolled'		=> $patrolled,
				'viewership'	=> $stats['viewership'] > 0 ? wfMessage('pb-articleviews', $stats['viewership'])->text() : '',
				'answered'		=> $stats['qa_answered'] > 0 ? wfMessage('pb-answered-stat', $stats['qa_answered'])->text() : ''
			];

			$template = $is_mobile ? 'stats_section_mobile' : 'stats_section';
			$html = $m->render($template, $vars);
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

	private function getUserHeadTabs($username) {
		$carat = '<span id="uht_carat" class="icon"></span>';
		$off_class = 'uht_off';

		$attributes = array(
			'class' => ($this->mTitle->inNamespace(NS_USER)) ? '' : $off_class,
			'id' => 'uht_profile',

		);
		$profile_link_inner = $carat.'<span class="icon"></span>'.wfMessage('mobile-frontend-profile-title-wh');
		$profile_link = Linker::link(Title::makeTitle(NS_USER, $username), $profile_link_inner, $attributes, '', array('known'));

		$attributes = array(
			'class' => ($this->mTitle->inNamespace(NS_USER_TALK)) ? '' : $off_class,
			'id' => 'uht_talk',

		);
		$talk_link_inner = $carat.'<span class="icon"></span>'.wfMessage('mobile-frontend-talk-overlay-header');
		$talk_link = Linker::link(Title::makeTitle(NS_USER_TALK, $username), $talk_link_inner, $attributes, '', array('known'));

		$html = '<div id="user_head_tabs">'.
				$profile_link.
				$talk_link.
				'</div>';

		return $html;
	}

	private function createdHTML($data, $create_count, $max) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$more = false;
		if (count($data) > $max) {
			array_pop($data);
			$more = true;
		}

		$vars = [
			'created_item'	=> $loader->load('created_item'),
			'items' 				=> $data,
			'article_count'	=> $create_count,
			'rising'				=> wfGetPad('/extensions/wikihow/profilebox/star-green.png'),
			'featured'			=> wfGetPad('/extensions/wikihow/profilebox/star-blue.png'),
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

		$html = $m->render('created_section', $vars);

		return $html;
	}

	private function thumbedHTML($data, $thumb_count, $max) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$more = false;
		if (count($data) > $max) {
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

		$html = $m->render('thumbed_section', $vars);

		return $html;
	}

	private function answeredHTML($data, $answered_count, $max) {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$more = false;
		if (count($data) > $max) {
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

		if (!Misc::isIntl()) {
			$html = $m->render('answered_section', $vars);
		}

		return $html;
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

}
