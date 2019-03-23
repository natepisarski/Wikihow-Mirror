<?php
/*
 * Extension of the SkinMinerva skin for wikiHow customization. Used for mobile and tablet devices
 */

class SkinMinervaWikihow extends SkinMinerva {
	public $skinname = 'minervawh';
	public $template = 'MinervaTemplateWikihow';

	/*
	 * Override parent method to add a few more things to the html head element
	 */
	protected function prepareQuickTemplate() {
		global $wgNamespaceRobotPolicies, $wgLanguageCode;

		$out = $this->getOutput();

		// Set robots policy based on article viewed. We don't want to override
		// anything set by our own RobotPolicy class though by accident, so
		// we intentionally exclude NS_MAIN.
		//
		// Note: Discussing with Jordan, we should refactor this code and our
		// Desktop code so that we no longer use $wgNamespaceRobotPolicies. We
		// should use our own class exclusively and put this equivalent
		// functionality into our own RobotPolicy class for better readability
		// and reasoning about the code. - Reuben
		$context = $this->getContext();
		$namespace = $context->getTitle()->getNamespace();
		if ($namespace != NS_MAIN) {
			// We have a special case where we don't want user pages to be
			// indexable only on mobile. We agreed that no indexation of
			// User pages on mobile makes sense because we really only
			// care about indexation of these pages on desktop, and users
			// will still be able to find them through their desktop URLs.
			$policy = '';
			if ($namespace == NS_USER) {
				$policy = 'noindex,follow';
			} elseif ( isset($wgNamespaceRobotPolicies[$namespace]) ) {
				$policy = $wgNamespaceRobotPolicies[$namespace];
			}
			if ($policy) {
				$out->setRobotPolicy( $policy );
			}
		}

		// Setting different viewport
		$out->addHeadItem( 'viewport',
			Html::element(
				'meta', array(
					'name' => 'viewport',
					'content' => 'width=device-width',
				)
			)
		);

		// Google Site Verification Code
		$out->addMeta('google-site-verification','Jb3uMWyKPQ3B9lzp5hZvJjITDKG8xI8mnEpWifGXUb0');

		ArticleMetaInfo::addFacebookMetaProperties($out->getPageTitle());
		ArticleMetaInfo::addTwitterMetaProperties();

		// Add canonical link if it doesn't exist already (it will for Samples)
		if (!$out->mCanonicalUrl) {
			$canonicalUrl = WikihowMobileTools::getNonMobileSite() . '/' . $this->getSkin()->getTitle()->getPrefixedURL();
			$out->setCanonicalUrl($canonicalUrl);
		}

		$articleName = $this->getSkin()->getTitle()->getText();
		$isMainPage = $articleName == wfMessage('mainpage')->text();

		if ( $out->getTitle()->inNamespace( NS_MAIN ) && !$isMainPage ) {
			GoogleAmp::addAmpHtmlLink( $out, $wgLanguageCode );
		}

		// Meta Description
		$description = ArticleMetaInfo::getCurrentTitleMetaDescription();
		if ($description) {
			$out->addMeta('description', $description);
		}

		// Hreflang links
		$this->addHreflangs();

		// HTML title
		if (class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest()) {
			$out->setHTMLTitle($this->getTitle()->getText());
		} else {
			$htmlTitle = WikihowSkinHelper::getHTMLTitle($out->getHTMLTitle(), $isMainPage);

			$out->setHTMLTitle($htmlTitle);
		}

		$tmpl = parent::prepareQuickTemplate();
		$this->prepareWikihowTools($tmpl);

		return $tmpl;
	}

	/**
	 * Prepares a list of links that are specific to wikihow in the main navigation
	 * menu
	 */
	protected function prepareWikihowTools( QuickTemplate $tpl ) {
		global $wgLanguageCode;

		$dashboardPage = $wgLanguageCode == 'en' ? Title::makeTitle(NS_SPECIAL, "CommunityDashboard") : Title::makeTitle(NS_PROJECT, wfMessage("community")->text());
		$items = array(
			'dashboard' => array(
				'text' => 'Dashboard',
				'href' => $dashboardPage->getLocalURL(),
				'id' => 'menu-dashboard',
			),
			'edit' => array(
				'text' => 'Edit article',
				'href' => '/edit',
				'id' => 'menu-edit',
			),
			'notifications' => array(
				'text' => 'Notifications',
				'href' => '/notifications',
				'id' => 'menu-notifications',
			),
			'help' => array (
				'text' => 'Help Us',
				'href' => '/help',
				'id' => 'menu-help',
			),
			'log' => array (
				'text' => 'Log Out',
				'href' => '/log',
				'id' => 'menu-logout',
			),
		);

		$tpl->set( 'wikihow_urls', $items );
	}

	protected function preparePageContent( QuickTemplate $tpl ) {
		// We don't want anything that the MinervaSkin does here, so override
	}

	/*
	 * Check if the count is greater than 0 if there is a notification before displaying in UI
	 */
	protected function prepareUserButton( BaseTemplate $tpl ) {
		parent::prepareUserButton ( $tpl );
		$button = $tpl->get('secondaryButton');
		if (!empty($button)) {

			$user = $this->getUser();
			$cachedNotifs = !$user->isAnon();
			$count = MWEchoNotifUser::newFromUser( $user )->getNotificationCount( $cachedNotifs );

			if (is_int($count) && $count == 0) {
				$tpl->set('secondaryButton', '');
			}

		}
	}

	/*
	 * Remove the watchlist from the personal_urls items
	 */
	protected function preparePersonalTools( QuickTemplate $tpl ) {
		parent::preparePersonalTools( $tpl );
		$items = $tpl->get('personal_urls');
		unset($items['watchlist']);
		unset($items['uploads']);
		unset($items['settings']);
		unset($items['preferences']);
		Hooks::run( 'WikihowMobileSkinAfterPreparePersonalTools', array( &$items ) );
		$tpl->set('personal_urls', $items);
	}

	/*
	 * Add a few more menu items to the main menu for wikiHow-specific tools
	 */
	protected function prepareDiscoveryTools( QuickTemplate $tpl ) {
		global $wgLanguageCode;

		parent::prepareDiscoveryTools( $tpl );
		$items = $tpl->get('discovery_urls');

		$user = $this->getUser();
		if ($user) {

			$items['categories'] = array(
				'text' => wfMessage( 'menu-categories' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'CategoryListing' )->getFullUrl(),
				'class' => 'icon-categories',
				'id' => 'icon-categories',
			);

			if ( class_exists('EchoEvent') && $user->hasCookies() ) {
				//add notifications
				$items['notifications'] = array(
					'text' => wfMessage( 'menu-notifications' )->escaped(),
					'href' => SpecialPage::getTitleFor( 'Notifications' )->getFullUrl(),
					'class' => 'icon-notification',
					'id' => 'icon-notification',
				);
			}
		}

		if (WikihowNamespacePages::showMobileAboutWikihow()) {
			$items['aboutwikihow'] = array(
				'text' => wfMessage( 'menu-aboutwikihow' )->escaped(),
				'href' => Title::newFromText( wfMessage('about-page')->text(), NS_PROJECT )->getFullUrl(),
				'class' => 'icon-aboutwikihow',
				'id' => 'icon-aboutwikihow',
			);
		}

		$title = $this->getSkin()->getTitle();
		if ($title) {
			//add page help header
			$items['header3'] =  array(
				'text' => wfMessage('menu-help-page')->text(),
				'class' => 'side_header',
				'id' => 'header3',
			);
			$help_page_added = false;

			$isMainPage = $title->getText() == wfMessage('mainpage')->text();
			if ($title->inNamespace(NS_MAIN) && !$isMainPage) {
				if (class_exists('TipsAndWarnings') && TipsAndWarnings::isActivePage() && TipsAndWarnings::isValidTitle($title)) {
					$items['addtip'] = array(
						'text' => wfMessage( 'mobile-wikihow-addtip-link' )->escaped(),
						'href' => '#',
						'class' => 'icon-addtip',
						'id' => 'icon-addtip',
					);
					$help_page_added = true;
				}
			}
			if (class_exists("UserCompletedImages") && UserCompletedImages::onWhitelist($title)) {
				$out = &$this->getOutput();
				$items['adduci'] = array(
					'text' => wfMessage( 'mobile-wikihow-adduci-link')->escaped(),
					'href' => '#',
					'class' => 'icon-adduci',
					'id' => 'icon-adduci',
				);
				$help_page_added = true;
			}

			//didn't add anything? maybe we should remove that header then...
			if (!$help_page_added) unset($items['header3']);
		}

		//add discovery header
		$items['header2'] =  array(
			'text' => wfMessage('menu-help-us')->text(),
			'class' => 'side_header',
			'id' => 'header2',
		);

		$hasCommunityTools = false;

		if ($wgLanguageCode == "en" && class_exists('SortQuestions')) {
			$items['sortquestions'] = array(
				'text' => wfMessage('menu-sortquestions')->text(),
				'href' => SpecialPage::getTitleFor('sortquestions')->getFullUrl(),
				'class' => 'icon-sortquestions',
				'id' => 'icon-sortquestions',
			);
			$hasCommunityTools = true;
		}

		if ($wgLanguageCode == "en" && class_exists('SpecialTechFeedback')) {
			$items['techfeedback'] = array(
				'text' => wfMessage( 'menu-techfeedback' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'SpecialTechFeedback' )->getFullUrl(),
				'class' => 'icon-techfeedback',
				'id' => 'icon-techfeedback',
			);
			$hasCommunityTools = true;
		}

		// if (class_exists('TipsGuardian')) {
			// $items['tipsguardian'] = array(
				// 'text' => wfMessage('menu-tipsguardian')->text(),
				// 'href' => SpecialPage::getTitleFor('TipsGuardian')->getFullUrl(),
				// 'class' => 'icon-tipsguardian',
				// 'id' => 'icon-tipsguardian',
			// );

			// $hasCommunityTools = true;
		// }

		// // Mobile RC Patrol
		// if ($wgLanguageCode == "en" && class_exists('RCLite')) {
			// $items['rclite'] = array(
				// 'text' => wfMessage( 'menu-rclite' )->escaped(),
				// 'href' => SpecialPage::getTitleFor( 'RCLite' )->getFullUrl(),
				// 'class' => 'icon-rclite',
				// 'id' => 'icon-rclite',
			// );
			// $hasCommunityTools = true;
		// }

		/*if (class_exists('CategoryGuardian')) {
			$items['categoryguardian'] = array(
				'text' => wfMessage('menu-categoryguardian')->escaped(),
				'href' => SpecialPage::getTitleFor('CategoryGuardian')->getFullUrl(),
				'class' => 'icon-categoryguardian',
				'id' => 'icon-categoryguardian',
			);
			$hasCommunityTools = true;
		}*/

		if ($wgLanguageCode == "en" && class_exists('Spellchecker')) {
			$items['spellchecker'] = array(
				'text' => wfMessage( 'menu-spellchecker' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'Spellchecker' )->getFullUrl(),
				'class' => 'icon-spellchecker',
				'id' => 'icon-spellchecker',
			);
			$hasCommunityTools = true;
		}

		if ($wgLanguageCode == "en" && class_exists('QuizYourself')) {
			$items['quizyourself'] = array(
				'text' => wfMessage( 'menu-quizyourself' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'QuizYourself' )->getFullUrl(),
				'class' => 'icon-quizyourself',
				'id' => 'icon-quizyourself',
			);
			$hasCommunityTools = true;
		}

		/*if (class_exists('DuplicateTitles')) {
			$items['duplicatetitles'] = array(
				'text' => wfMessage('menu-duplicatetitles')->escaped(),
				'href' => SpecialPage::getTitleFor('DuplicateTitles')->getFullUrl(),
				'class' => 'icon-duplicatetitles',
				'id' => 'icon-duplicatetitles',
			);
			$hasCommunityTools = true;
		}*/

		// if ($wgLanguageCode == "en" && class_exists('UnitGuardian')) {
			// $items['unitguardian'] = array(
				// 'text' => wfMessage('menu-unitguardian')->text(),
				// 'href' => SpecialPage::getTitleFor('UnitGuardian')->getFullUrl(),
				// 'class' => 'icon-unitguardian',
				// 'id' => 'icon-unitguardian',
			// );
			// $hasCommunityTools = true;
		// }

		// if (class_exists('PicturePatrol')) {
			// $items['picturepatrol'] = array(
				// 'text' => wfMessage( 'menu-picturepatrol' )->escaped(),
				// 'href' => SpecialPage::getTitleFor( 'PicturePatrol' )->getFullUrl(),
				// 'class' => 'icon-picturepatrol',
				// 'id' => 'icon-picturepatrol',
			// );

			// $hasCommunityTools = true;
		// }

		if ($hasCommunityTools) {
			$items['morethings'] = array(
				'text' => wfMessage('menu-morethings')->text(),
				'href' => SpecialPage::getTitleFor('CommunityDashboard')->getFullUrl(),
				'class' => 'icon-morethings',
				'id' => 'icon-morethings',
			);
		} else {
			unset($items['header2']);
		}

		$title = $this->getTitle();
		if ( $title == "Special:UserLogin" ) {
			$query = $this->getSkin()->getRequest()->getQueryValues();
			$useformat = $query['useformat'];
			if ( $query['useformat'] )  {
				foreach ( $items as $itemKey => $itemVal ) {
					foreach ( $itemVal as $key => $val ) {
						if ( $key == 'href' ) {
							$items[$itemKey][$key] = $val . "?useformat=$useformat";
						}
					}

				}
			}
		}

		$tpl->set('historyLink', null);
		unset($items['nearby']);
		$items['random']['href'] = SpecialPage::getTitleFor( 'Randomizer' )->getLocalUrl();
		Hooks::run( 'WikihowMobileSkinAfterPrepareDiscoveryTools', array( &$items ) );
		$tpl->set('discovery_urls', $items);
	}

	protected function getLogInOutLink() {
		$loginLogoutLink = parent::getLogInOutLink();

		if ( array_key_exists( 'href', $loginLogoutLink ) ) {
			$loginLogoutLink['href'] = $loginLogoutLink['href'] . '&useformat=mobile';
		}

		// Below is mainly taken from SkinMinervaBeta::getLoginOutLink
		$user = $this->getUser();
		if ( $user->isLoggedIn() ) {
			$loginLogoutLink['class'] = 'icon-secondary icon-secondary-logout';
			$name = $user->getName();
			$avatarUrl = Avatar::getAvatarURL($name);
			$style = "background-image: url($avatarUrl);";
			$loginLogoutLink = array(
				'links' => array(
					array(
						'text' => $name,
						// JRS 06/17/14 commenting out beta behavior and linking to normal user page
						/*	'href' => SpecialPage::getTitleFor( 'UserProfile', $name )->getLocalUrl(),*/
						'href' =>  $user->getUserPage()->getLocalURL(),
						'class' => 'icon-profile truncated-text',
						'style' => $style
					),
					$loginLogoutLink
				),
			);
			$loginLogoutLink['class'] = 'icon-user';
		} else {
			$loginLogoutLink['class'] = 'icon-anon';
			$loginLogoutLink['text'] = wfMessage('menu-anon-login')->text();
		}
		return $loginLogoutLink;
	}

	protected function prepareHeaderAndFooter( BaseTemplate $tpl ) {
		global $wgLanguageCode;

		parent::prepareHeaderAndFooter( $tpl );
		$title = $this->getTitle();
		$out = $this->getOutput();

		$pageHeading = '';
		if (!$title->isMainPage()) {
			$pageHeading = $out->getPageTitle();
		}

		if ( $title->isSpecialPage() ) {
			$tpl->set('specialPageHeader', '');

			$isTool = false;
			Hooks::run( 'getMobileToolStatus', array( &$isTool ) );

			if ($pageHeading && !$isTool) {
				$preBodyText = Html::rawElement( 'h1', array( 'id' => 'section_0', 'class' => 'special_title'), $pageHeading );
				$tpl->set( 'prebodytext', $preBodyText );
			}

			$tpl->set( 'disableSearchAndFooter', false );
			$tpl->set( 'disableFooter', $isTool);
		} else {
			if ( $pageHeading ) {
				if ($title->inNamespace(NS_MAIN)) {
					//standard; add "How to"
					$titleMsg = $wgLanguageCode == 'ja' ? 'howto_article_heading' : 'howto';
					$titleTxt = wfMessage($titleMsg, $pageHeading)->text();
				} else {
					$titleTxt = $pageHeading;
				}
				$class = $this->getTitleClass($title->getText());
				$preBodyText = Html::rawElement( 'h1', ['id' => 'section_0', 'class' => $class], $titleTxt );
			} else {
				$preBodyText = '';
			}
			$tpl->set( 'prebodytext', $preBodyText );
		}

		if ( $this->isMobileMode ) {
			$tpl->set( 'footerlinks', array(
				'info' => array(
					'mobile-switcher',
					/*'mobile-license',*/
					'edit',
					'random',
				),
			) );
		}

	}
	protected function prepareMobileFooterLinks( $tpl ) {
		parent::prepareMobileFooterLinks( $tpl );
		$title = $this->getTitle();

		$editHtml = '<a href="'.$title->getEditURL().'">'.wfMessage('mobile-frontend-footer-edit-wh')->text().'</a>';
		$tpl->set( 'edit', $editHtml);

		// Use the wikiHow random link message
		$randomLink = '<a href="/Special:Randomizer" >' . wfMessage('randompage')->text() . '</a>';
		$tpl->set('random', $randomLink);
		$req = $this->getRequest();
		$url = $this->mobileContext->getDesktopUrl( wfExpandUrl(
			$req->appendQuery( 'mobileaction=toggle_view_desktop' )
		) );
		$fullSiteText = wfMessage( 'mobile-frontend-view-desktop-wh' )->escaped();
		$switcherHtml = self::getMobileMenuFullSiteLink( $fullSiteText, $url );
		$tpl->set( 'mobile-switcher', $switcherHtml );
	}

	// annoying but for gdpr we make the section twice and use the one that is appropriate
	public static function getMobileMenuFullSiteLink( $message, $url ) {
		$first = Html::rawElement( 'a', ['class' => 'mw-mf-display-toggle-link gdpr-menu', 'href' => $url], $message );
		$original = Html::rawElement( 'div', ['class' => ['mw-mf-display-toggle', 'gdpr_no_display']], $first );

		$gdprText = wfMessage('gdpr_mobile_menu_bottom')->text();
		$href = Title::newFromText( wfMessage("gdpr_mobile_menu_bottom_link")->text(), NS_PROJECT )->getLinkURL();
		$attr = array(
			'href' => $href,
			'class' => 'gdpr-menu'
		);
		$gdpr .= Html::element( "span", [], " | " );
		$gdpr .= Html::rawElement( 'a', $attr, $gdprText );
		$gdpr = Html::rawElement( 'div', ['class' => ['mw-mf-display-toggle', 'gdpr_only_display']], $first . $gdpr );

		return $original . $gdpr;
	}

	protected function preparePageActions( BaseTemplate $tpl ) {
		parent::preparePageActions( $tpl );
		$menu = $tpl->get('page_actions');
		unset($menu['photo']);
		unset($menu['watch']);
		unset($menu['unwatch']);
		unset($menu['talk']);
		$tpl->set('page_actions', $menu);
	}

	protected function prepareMenuButton( BaseTemplate $tpl ) {
		parent::prepareMenuButton( $tpl );
		//remove the actual link so we don't have people go to the menu as a page
		//JAVASCRIPT OR NOTHING!
		$button = Html::element( 'a', array(
			'href' => '#',
			'title' => wfMessage( 'mobile-frontend-main-menu-button-tooltip' ),
			'id'=> 'mw-mf-main-menu-button',
			) );
		$tpl->set('menuButton',$button);
	}

	public function getDefaultModules() {
		$modules = parent::getDefaultModules();

		unset($modules['toggling']);
		//unset($modules['newusers']);

		// This RL module 'mobile.site' is contains no content, but
		// is requested by our site as a separate HTTP request. Turning
		// it off for now.
		$index = array_search('mobile.site', $modules['mobile']);
		if ($index !== false) {
			// array_splice() rather than unset() is used so that indices
			// are renumbered
			array_splice($modules['mobile'], $index, 1);
		}

		return $modules;
	}

	/*
	 * sets the size of the title based on number of characters
	 * returns a CSS class name
	 */
	private function getTitleClass($text) {
		$count = strlen($text);
		if ($count > 40) {
			$className = 'title_sm';
		}
		elseif ($count > 20) {
			$className = 'title_md';
		}
		else {
			$className = 'title_lg';
		}

		return $className;
	}

	// Do nothing in this method. But method must exist because it's called
	// from our StandingsIndividual class.
	public function addWidget($html) {
	}

	protected function addHreflangs() {
		global $wgLanguageCode, $wgRequest;

		if ( !RobotPolicy::isIndexable($this->getTitle(), $this->getContext()) ) {
			return;
		}

		$out = $this->getOutput();
		$hreflangs = WikihowSkinHelper::getLanguageLinks();
		$params = $wgRequest->getBool('amp') ? '?amp=1' : '';

		if (is_array($hreflangs) && !empty($hreflangs)) {
			// Include self-referencing hreflang
			$href = PROTO_HTTPS . Misc::getCanonicalDomain('', true) . $this->getTitle()->getLocalURL();
			array_unshift($hreflangs, ['code' => $wgLanguageCode, 'href' => $href]);
			foreach ($hreflangs as $item) {
				$lang = $item['code'];
				$href = $item['href'] . $params;
				$out->addHeadItem("hreflang_{$lang}", "\n" . Html::element('link', [
						'rel' => 'alternate', 'hreflang' => $lang, 'href' => $href
					]));
			}
		}
	}

}
