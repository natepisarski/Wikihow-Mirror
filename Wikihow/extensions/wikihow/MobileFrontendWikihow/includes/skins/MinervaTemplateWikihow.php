<?php

/*
 * Specialized version of the MinervaTemplate with wikiHow customizations
 */
class MinervaTemplateWikihow extends MinervaTemplate {
	/**
	 * @var Boolean
	 */

	protected $isMainPage;
	protected $isArticlePage;
	protected $isSearchPage;
	protected $breadCrumb = '';

	public function execute() {
		$this->isMainPage = $this->getSkin()->getTitle()->isMainPage();
		$title = $this->getSkin()->getTitle();
		$action = $this->getSkin()->getRequest()->getVal('action', 'view');
		$this->isArticlePage = $title && !$this->isMainPage && $title->inNamespace(NS_MAIN) && $action == 'view';
		$this->isSearchPage = false;
		if (preg_match('@/wikiHowTo@',  $_SERVER['REQUEST_URI'])) {
			$this->isSearchPage = true;
		}
		$this->breadCrumb = $this->setBreadcrumbHtml();
		parent::execute();
	}

	public function getWikihowTools() {
		return $this->data['wikihow_urls'];
	}

	protected function setBreadcrumbHtml(): string {
		if (Misc::isAltDomain()) return '';

		$context = RequestContext::getMain();

		$parenttree = CategoryHelper::getCurrentParentCategoryTree();
		$fullCategoryTree = CategoryHelper::cleanCurrentParentCategoryTree($parenttree);

		$catLinksTop = WikihowHeaderBuilder::getCategoryLinks(true, $context, $fullCategoryTree);
		Hooks::run('getBreadCrumbs', array(&$catLinksTop));

		return $catLinksTop;
	}

	protected function renderPreContent( $data ) {
		global $wgLanguageCode, $wgUser;

		//Scott - use this hook to tweak display title
		Hooks::run( 'MobilePreRenderPreContent', array( &$data ) );

		$internalBanner = $data[ 'internalBanner' ];
		$isSpecialPage = $this->isSpecialPage;
		$preBodyText = isset( $data['prebodytext'] ) ? $data['prebodytext'] : '';

		if ( $internalBanner || $preBodyText ) {
		?>
		<?php
		//XXCHANGED: BEBETH 2/3/2015 to put in unnabbed alert
			$skin = $this->getSkin();
			$title = $skin->getTitle();
			if ($wgLanguageCode == "en" && $title->inNamespace(NS_MAIN) && !NewArticleBoost::isNABbedNoDb($title->getArticleID())) {
				/* Show element if showdemoted option is enabled */
				$style = ($wgUser->getOption('showdemoted') == '1') ? "style='display:block'" : '';
				echo "<div class='unnabbed_alert_top' $style>" . wfMessage('nab_warning_top')->parse() . "</div>";
			}
		?>
		<div class="pre-content">
			<?php
				// FIXME: Temporary solution until we have design
				if ( isset( $data['_old_revision_warning'] ) ) {
					echo $data['_old_revision_warning'];
					//XX CHANGED: BEBETH
				} elseif ( !$isSpecialPage && !$this->isMainPage ){
					$this->renderPageActions( $data );
				}
				//XXCHANGED: BEBETH
				echo $preBodyText;
				echo $internalBanner;
				?>
		</div>
		<?php
		}
	}

	protected function renderContentWrapperAmp( $data ) {
		if ( class_exists('MobileAppCTA') ) {
			$cta = new MobileAppCTA();
			if ($cta->isTargetPage()) {
				echo $cta->getHtml();
			}
		}
		$this->renderPreContent( $data );
		print $this->getContentHtml($data);
	}

	protected function renderContentWrapper( $data ) {
		if ( $data['amp'] == true ) {
			return $this->renderContentWrapperAmp( $data );
		}
		?>
			<script>
				if (typeof mw != 'undefined') { mw.mobileFrontend.emit( 'header-loaded' ); }
			</script>
		<?php
		if ( class_exists('MobileAppCTA') ) {
			$cta = new MobileAppCTA();
			if ($cta->isTargetPage()) {
				echo $cta->getHtml();
			}
		}
		$this->renderPreContent( $data );
		print $this->getContentHtml($data);
		//was: $this->renderContent( $data );
		// NOTE: we don't call parent::render() because it adds the
		// header before the content, which we've already added. We
		// might need to consult that function, which is in the file
		// /prod/skins/MinervaNeue/includes/skins/SkinMinerva.php
		// in case there are things missing from our mobile page that
		// would be displayed in vanilla Mediawiki MobileFrontend.
	}

	protected function renderMainMenu( $data ) {
		?>
		<ul>
		<?php
		foreach( $this->get('discovery_urls') as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?>
		</ul>
		<ul>
		<?php
		foreach( $this->get('personal_urls') as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?>
		</ul>
		<?php
	}

	protected function renderFooter( $data ) {
		global $IP;

		if ($this->isArticlePage && $data['titletext'])
			$footerPlaceholder = wfMessage('howto', $data['titletext'])->text();
		else
			$footerPlaceholder = wfMessage('footer-search-placeholder')->text();

		Hooks::run( 'MobileTemplateBeforeRenderFooter', array( &$footerPlaceholder ) );

		EasyTemplate::set_path( $IP.'/extensions/wikihow/mobile/' );

		if ($data['amp']) {
			$search_box = GoogleAmp::getSearchBar( "footer_search", $footerPlaceholder );
		}
		else {
			$search_box = EasyTemplate::html(
			'search-box.tmpl.php', [
				'id' => 'search_footer',
				'placeholder' => $footerPlaceholder,
				'class' => '',
				'lang' => RequestContext::getMain()->getLanguage()->getCode(),
				'form_id' => 'cse-search-box-bottom'
			]);
		}

		if ( !$data['disableSearchAndFooter'] ) {
			$vars = [
				'disableFooter' => @$data['disableFooter'],
				'mainLink' => Title::newMainPage()->getLocalURL(),
				'logoImage' => '/skins/owl/images/wikihow_logo_intl.png',
				'imageAlt' => 'wikiHow',
				'crumbs' => $this->breadCrumb,
				'searchBox' => $search_box,
				'links' => $this->footerLinks(),
				'socialFooter' => class_exists('SocialFooter') ? SocialFooter::getSocialFooter() : '',
				'amp' => $data['amp']
			];

			echo $this->footerHtml($vars);

			if (class_exists("MobileSlideshow")) echo MobileSlideshow::getHtml();
		}
	}

	protected function footerHtml(array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/../../templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render('footer.mustache', $vars);
	}

	protected function footerLinks(): array {
		$links = [
			['link' => wfMessage('footer_home')->parse()]
		];

		if (!wfMessage('footer_about_wh')->isBlank())
			$links[] = ['link' => wfMessage('footer_about_wh')->parse()];

		if (RequestContext::getMain()->getLanguage()->getCode() == 'en' && !$isAlternateDomain)
			$links[] = ['link' => wfMessage('footer_jobs')->parse()];

		$links[] = ['link' => wfMessage('footer_site_map')->parse()];

		// $links[] = ['link' => wfMessage('footer_experts')->parse()];
		$links[] = ['link' => wfMessage('footer_terms')->parse()];
		// $links[] = ['link' => wfMessage('footer_site_map')->parse()];

		return $links;
	}

	protected function renderMetaSections() {
		//NO META SECTIONS FOR YOU!
	}

	private function generateTwitterLink() {
		global $wgCanonicalServer;

		$title = $this->getSkin()->getContext()->getTitle();

		$howto = $wgLanguageCode != 'de' ? wfMessage('howto', htmlspecialchars($title->getText()))->text() : htmlspecialchars($title->getText());
		$twitterlink = "https://www.twitter.com/intent/tweet?";
		$twitterlink .= "&text=" . urlencode($howto);
		$twitterlink .= "&via=wikihow";
		$twitterlink .= "&url=" . urlencode($wgCanonicalServer . '/' . $title->getPartialURL());
		$twitterlink .= "&related=" . urlencode("JackH:Founder of wikiHow");
		$twitterlink .="&target=_blank";
		return $twitterlink;
	}

	protected function renderPageActions( $data ) {
		Hooks::run('BeforeRenderPageActionsMobile', array(&$data));
/* // Disabling the pencil icon from loading at all during upgrade -> responsive time window
		?><ul id="page-actions" class="hlist"><?php
		foreach( $this->getPageActions() as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?></ul><?php
*/
	}


	// this function exists in this class instead of the google amp
	// helper class because it calls some protected functions on the class
	private function renderAmpSidebar() {
		$items = '';
		foreach( $this->get('discovery_urls') as $key => $val ) {
			$items .= $this->makeListItem( $key, $val );
		}
		echo GoogleAmp::getAmpSidebar( $items );
	}

	private function getTopContentJS( $data ) {
		if ( $data['amp'] ) {
			return;
		}

		$rightRail = $data['rightrail'];
		$adsJs = $rightRail->mAds->getJavascriptFile();
		$html = '';
		if ( $adsJs ) {
			$html = Html::inlineScript( Misc::getEmbedFiles( 'js', [$adsJs] ) );
		}
		return $html;
	}


	private function getRightRailHtml( $data ) {
		if ( $data['amp'] ) {
			return;
		}

		$rightRail = $data['rightrail'];
		$rightRailHtml = $rightRail->getRightRailHtml();

		return $rightRailHtml;
	}

	private function renderPageLeft( $data ) {
		// in amp mode we have to add the header as a direct decendent of <body>
		// so the sidebar is added in a different place
		if ( $data['amp'] ) {
			return;
		}

		// Don't show desktop link to anons if the page is noindex
		$desktopLink = WikihowSkinHelper::shouldShowMetaInfo($this->getSkin()->getOutput())
			? $this->data['mobile-switcher'] : '';

		?>
		<div id="mw-mf-page-left">
		<?php
			$this->renderMainMenu( $data );
			print $desktopLink;
		?>
		</div>
		<?php
	}

	protected function render( $data ) { // FIXME: replace with template engines
		global $wgLanguageCode;
		Hooks::run( "MinvervaTemplateBeforeRender", array( &$data ) );

		$rightRailHtml = $this->getRightRailHtml( $data );

		// begin rendering
		echo $data[ 'headelement' ];
		if ( $data['amp'] ) {
			$this->renderAmpSidebar();
		} else {
			echo $data['rightrail']->mAds->getGPTDefine();
		}
		?>
		<? /* BEBETH: Moving header to the top to deal with links in static header */ ?>
		<div class="header" role="navigation">
		<?php
		$this->html( 'menuButton' );
		if ( $data['amp'] ) {
			echo GoogleAmp::getHeaderSidebarButton();
		}

		$headerClass = '';
		Hooks::run( 'MinervaTemplateWikihowBeforeCreateHeaderLogo', array( &$headerClass ) );
		?>
		<a href="<?= Title::newMainPage()->getLocalURL() ?>" id="header_logo" class="<?= $headerClass ?>"></a>
		<?php
		if ( !( Misc::isAltDomain() ) ) {
			?>
			<a href="/Hello" id="noscript_header_logo" class="hide <?= $headerClass ?>"></a>
			<?php
		}
		if ( $data['disableSearchAndFooter'] ) {
			echo $data['specialPageHeader'];
		} else {
			$query = $this->isSearchPage ? $this->getSkin()->getRequest()->getVal( 'search', '' ) : '';
			$expand = $data['amp'] ? 'on="tap:hs.toggleClass(class=\'hs_active\',force=true)"' : '';
			$collapse = $data['amp'] ? 'on="tap:hs.toggleClass(class=\'hs_active\',force=false)"' : '';
			$classes = [];
			if ( $this->isSearchPage ) {
				$classes[] = 'hs_active';
			}
			if ( $data['secondaryButtonData'] ) {
				$classes[] = 'hs_notif';
			}
			?>
			<div id="hs" class="<?= implode( $classes, ' ' ) ?>">
				<form action="/wikiHowTo" class="search" target="_top">
					<input type="text" id="hs_query" role="textbox" tabindex="0" <?= $expand ?> name="search" value="<?= $query ?>" required placeholder="<?= wfMessage( 'header-search-placeholder' )->text() ?>" <?= !$data['amp'] ? 'x-webkit-speech' : '' ?> aria-label="<?= wfMessage('aria_search')->showIfExists() ?>" />
					<button type="submit" id="hs_submit"></button>
					<div id="hs_close" role="button" tabindex="0" <?= $collapse ?> ></div>
				</form>
			</div>
			<?php
		}
		?>
		<?php
		echo $data['secondaryButtonData'];
		?>
		</div>
		<?
		// JRS 06/23/14 Add a hook to add classes to the top-level viewport object
		// to make it easier to customize css based on classes
		$classes = array();
		Hooks::run('MinervaViewportClasses', array(&$classes));
		$classes = empty($classes) ? '' : implode(" ", $classes);
		?>
		<div id="mw-mf-viewport" class="<?=$classes?>">
			<?php
			$this->renderPageLeft( $data );
			?>
			<div id='mw-mf-page-center'>
				<? if ( class_exists( 'GDPR' ) && !$data['amp'] ) {
					if ( $this->isMainPage || $this->isArticlePage || $this->isSearchPage ) {
						echo GDPR::getHTML();
						echo GDPR::getInitJs();
					}
				} ?>
				<?php
				foreach( $this->data['banners'] as $banner ):
					echo $banner;
				endforeach;
				?>

				<div id="content_wrapper" role="main">
					<?php
						$html = $this->getTopContentJS( $data );
						echo $html;
					?>
					<div id="content_inner">
						<?php
						$this->renderContentWrapper( $data );
						?>
					</div>

					<div id="sidebar">
					<?php
						echo $rightRailHtml;
					?>
					</div>
				</div>
				<br class="clearall" />

				<? $schema = SchemaMarkup::getSchema( $this->getSkin()->getOutput() );
				if ( $schema ) {
					echo $schema;
				}?>

				<?php

				if ( isset( $data['tableofcontents'] ) ) {
					echo $data['tableofcontents'];
				}

				$this->renderFooter( $data );
		?>
		</div>
		<div id='servedtime'><?= Misc::reportTimeMS(); ?></div>
		<?php
		echo MWDebug::getDebugHTML( $this->getSkin()->getContext() );
		if ( !$data['amp'] ) {
			echo wfReportTime();
		}
		echo $data['bottomscripts'];

		// Reuben: using this hook to post-load the ResourceLoader startup
		Hooks::run( 'MobileEndOfPage', array( $data ) );
		?>
		</body>
		</html>
	<?php
	}
}
