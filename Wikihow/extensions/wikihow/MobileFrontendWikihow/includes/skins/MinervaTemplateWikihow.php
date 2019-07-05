<?php
/*
 * Specialized version of the MinervaTemplate with wikiHow customizations
 */
class MinervaTemplateWikihow extends MinervaTemplate {
	/**
	 * @var Boolean
	 */

	protected  $isMainPage;
	protected  $isArticlePage;
	protected  $isSearchPage;

	public function execute() {
		$this->isMainPage = $this->getSkin()->getTitle()->isMainPage();
		$title = $this->getSkin()->getTitle();
		$action = $this->getSkin()->getRequest()->getVal('action', 'view');
		$this->isArticlePage = $title && !$this->isMainPage && $title->inNamespace(NS_MAIN) && $action == 'view';
		$this->isSearchPage = false;
		if (preg_match('@/wikiHowTo@',  $_SERVER['REQUEST_URI'])) {
			$this->isSearchPage = true;
		}
		parent::execute();
	}

	public function getWikihowTools() {
		return $this->data['wikihow_urls'];
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
		$this->renderContent( $data );

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
		$this->renderContent( $data );
	}

	protected function renderMainMenu( $data ) {
		?>
		<ul>
		<?php
		foreach( $this->getDiscoveryTools() as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?>
		</ul>
		<ul>
		<?php
		foreach( $this->getPersonalTools() as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?>
		</ul>
		<?php
		/*
		?>
		<ul class="hlist">
		<?php
		foreach( $this->getSiteLinks() as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?>
		</ul>*/
		?>
		<?php
	}

	protected function renderFooter( $data ) {
		global $wgLanguageCode;

		$footerPlaceholder = wfMessage('footer-search-placeholder')->text();

		Hooks::run( 'MobileTemplateBeforeRenderFooter', array( &$footerPlaceholder ) );

		if ( !$data['disableSearchAndFooter'] ) {
			//get random random icon
			$creature = self::getFooterCreatureArray()[rand(0,count(self::getFooterCreatureArray())-1)];

			// TODO: Get creature to work in Arabic
			if (!in_array($wgLanguageCode, array('ar'))) {
				$creatureTextCurved = '
		<text class="creature_text">
			<textPath xlink:href="#textPath" startoffset="22%">'.wfMessage('surprise-me-footer')->plain().'</textPath>
		</text>';
				$creature = str_replace('[[creature_text_curved]]',$creatureTextCurved,$creature);
				$creature = str_replace('[[creature_text_flat]]','',$creature);
			} else {
				$creatureTextFlat = '
		<div class="creature_text_flat">
		'.wfMessage('surprise-me-footer')->plain().'
		</div>';
				$creature = str_replace('[[creature_text_curved]]','',$creature);
				$creature = str_replace('[[creature_text_flat]]',$creatureTextFlat,$creature);
			}
		?>

		<div id="fb-root" ></div>
		<? if ( !@$data['disableFooter'] ) { ?>
		<div id="footer" role="navigation">
			<div id="footer_random_button" role="button">
				<!--<?=wfMessage('surprise-me-footer')->escaped()?>-->
				<a href="/Special:Randomizer"><?=$creature?></a>
			</div>
			<div id="footer_bar">
				<?php
					global $IP;
					EasyTemplate::set_path( $IP.'/extensions/wikihow/mobile/' );
					echo EasyTemplate::html('search-box.tmpl.php',array('id' => 'search_footer', 'placeholder' => $footerPlaceholder, 'class' => '', 'lang' => $wgLanguageCode, 'form_id' => 'cse-search-box-bottom'));

					if (class_exists('SocialFooter')) echo SocialFooter::getSocialFooter();
				?>
			</div>
				<?php
					echo wikihowAds::getMobileAdAnchor();
				?>
		</div>
		<? } ?>
		<?php
		}
		?>
		<?  if (class_exists("MobileSlideshow")):
				echo MobileSlideshow::getHtml();
			endif;
		?>
<?php

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
		?><ul id="page-actions" class="hlist"><?php
		foreach( $this->getPageActions() as $key => $val ):
			echo $this->makeListItem( $key, $val );
		endforeach;
		?></ul><?php
	}


	// this function exists in this class instead of the google amp
	// helper class because it calls some protected functions on the class
	private function renderAmpSidebar() {
			$items = '';
			foreach( $this->getDiscoveryTools() as $key => $val ) {
				if ( $key == "header3" || $key == 'addtip' ) {
					continue;
				}
				if ( $key == "header3" || $key == 'notifications' ) {
					continue;
				}
				if ( $key == "header3" || $key == 'adduci' ) {
					continue;
				}
				$items .= $this->makeListItem( $key, $val );
			}
			echo GoogleAmp::getAmpSidebar( $items );
	}

	private function getRightRailHtml( $data ) {
		global $wgTitle;;

		// for some reason putting this on special pages makes their css no work well
		// so restricting it for now
		if ( $wgTitle && !$wgTitle->inNamespace( NS_MAIN ) ) {
			return;
		}
		if ( $data['amp'] ) {
			return;
		}
		$context = RequestContext::getMain();
		$html = '';

		$sp = new SocialProofStats($context, $fullCategoryTree);
		$socialProofSidebar = $sp->getDesktopSidebarHtml();
		$html .= $socialProofSidebar;

		$relatedWikihows = new RelatedWikihows( $context, $context->getUser());
		$relatedOutput = $relatedWikihows->getSideData();
		$attr = ['id' => 'side_related_articles', 'class' => 'sidebox related_articles'];
		$html .= Html::rawElement( 'div', $attr, $relatedOutput );

		$html .= RatingArticle::getDesktopSideForm( 0, '' );

		// blanked out for now
		$html = '';

		return $html;
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

		// begin rendering
		echo $data[ 'headelement' ];
		if ( $data['amp'] ) {
			$this->renderAmpSidebar();
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
			if ( $data['secondaryButton'] ) {
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
		echo $data['secondaryButton'];
		?>
		</div>
		<?
		// JRS 06/23/14 Add a hook to add classes to the top-level viewport object
		// to make it easier to customize css based on classes
		$classes = array();
		Hooks::run('MinervaViewportClasses', array(&$classes));
		$classes = empty($classes) ? '' : implode(" ", $classes);
		$pageCenterClasses = wikihowAds::getMobilePageCenterClass();
		?>
		<div id="mw-mf-viewport" class="<?=$classes?>">
			<?php
			$this->renderPageLeft( $data );
			?>
			<div id='mw-mf-page-center' class="<?=$pageCenterClasses?>">
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
					<div id="content_inner">
						<?php
						$this->renderContentWrapper( $data );
						?>
					</div>

					<div id="sidebar">
					<?php
						$html = $this->getRightRailHtml( $data );
						echo $html;
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

				if ( $data['amp'] ) {
					GoogleAmp::renderFooter( $data );
				}  else {
					$this->renderFooter( $data );
				}
		   ?>
		</div>
		<div id='servedtime'><?= Misc::reportTimeMS(); ?></div>
		<?php
		echo MWDebug::getDebugHTML( $this->getSkin()->getContext() );
		echo wfReportTime();
		echo $data['bottomscripts'];

		// Reuben: using this hook to post-load the ResourceLoader startup
		Hooks::run( 'MobileEndOfPage', array( $data ) );
		?>
		</body>
		</html>
	<?php
	}

	const FOOTER_CREATURE_ARRAY = array(
		// //triangle
		// '<svg class="creature" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			 // width="204.365px" height="211.012px" viewBox="0 0 204.365 211.012" enable-background="new 0 0 204.365 211.012"
			 // xml:space="preserve">
		// <path class="creature_path" d="M118.059,111.658c-1.881,0-3.41,1.53-3.41,3.411s1.529,3.411,3.41,3.411s3.411-1.53,3.411-3.411
			// S119.94,111.658,118.059,111.658z"/>
		// <path class="creature_path" d="M101.104,127.993c-6.68,0-12.114,6.646-12.114,14.813c0,8.168,5.435,14.814,12.114,14.814
			// c6.681,0,12.115-6.646,12.115-14.814C113.219,134.638,107.785,127.993,101.104,127.993z"/>
		// <path class="creature_path" d="M105.552,58.521c-2.566-4.444-6.765-4.444-9.331,0L25.572,180.89c-2.566,4.444-0.467,8.081,4.665,8.081
			// h141.3c5.132,0,7.231-3.636,4.665-8.081L105.552,58.521z M77.007,115.07c0-4.178,3.399-7.576,7.577-7.576s7.576,3.398,7.576,7.576
			// s-3.398,7.576-7.576,7.576S77.007,119.247,77.007,115.07z M101.104,161.785c-8.977,0-16.28-8.514-16.28-18.979
			// s7.304-18.979,16.28-18.979c8.978,0,16.281,8.514,16.281,18.979S110.082,161.785,101.104,161.785z M118.059,122.646
			// c-4.178,0-7.576-3.398-7.576-7.576s3.398-7.576,7.576-7.576s7.577,3.398,7.577,7.576S122.237,122.646,118.059,122.646z"/>
		// <path class="creature_path" d="M84.585,111.658c-1.881,0-3.411,1.53-3.411,3.411s1.53,3.411,3.411,3.411s3.41-1.53,3.41-3.411
			// S86.465,111.658,84.585,111.658z"/>
		// <defs>
			// <path id="textPath" d="M11.131,125.069c0-50.339,40.808-91.147,91.147-91.147s91.147,40.808,91.147,91.147"/>
		// </defs>
		// [[creature_text_curved]]
		// </svg>
		// [[creature_text_flat]]',
		//star
		'<svg class="creature" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			 width="204.365px" height="211.012px" viewBox="0 0 204.365 211.012" enable-background="new 0 0 204.365 211.012"
			 xml:space="preserve">
		<path class="creature_path" d="M118.797,125.069c-1.883,0-3.415,1.532-3.415,3.414s1.532,3.414,3.415,3.414
			c1.882,0,3.414-1.532,3.414-3.414S120.678,125.069,118.797,125.069z"/>
		<path class="creature_path" d="M85.291,125.069c-1.883,0-3.415,1.532-3.415,3.414s1.532,3.414,3.415,3.414c1.882,0,3.414-1.532,3.414-3.414
			S87.173,125.069,85.291,125.069z"/>
		<path class="creature_path" d="M102.548,138.593c-5.247,0-9.517,5.08-9.517,11.324s4.27,11.324,9.517,11.324s9.517-5.08,9.517-11.324
			S107.795,138.593,102.548,138.593z"/>
		<path class="creature_path" d="M168.304,109.11l-42.257-6.14L107.15,64.679c-2.254-4.568-5.944-4.568-8.198,0L80.054,102.97l-42.257,6.14
			c-5.041,0.733-6.181,4.241-2.533,7.797l30.577,29.805l-7.218,42.086c-0.861,5.021,2.124,7.189,6.632,4.819l37.796-19.87
			l37.796,19.87c4.509,2.37,7.493,0.202,6.632-4.819l-7.218-42.086l30.577-29.805C174.485,113.351,173.345,109.843,168.304,109.11z
			 M77.708,128.483c0-4.181,3.401-7.583,7.583-7.583s7.583,3.402,7.583,7.583s-3.401,7.583-7.583,7.583S77.708,132.664,77.708,128.483
			z M102.548,165.41c-7.546,0-13.685-6.95-13.685-15.493s6.139-15.493,13.685-15.493s13.685,6.95,13.685,15.493
			S110.093,165.41,102.548,165.41z M118.797,136.066c-4.182,0-7.583-3.402-7.583-7.583s3.401-7.583,7.583-7.583
			c4.181,0,7.582,3.402,7.582,7.583S122.977,136.066,118.797,136.066z"/>
		<defs>
			<path id="textPath" d="M11.035,125.069c0-50.339,40.808-91.147,91.147-91.147s91.147,40.808,91.147,91.147"/>
		</defs>
		[[creature_text_curved]]
		</svg>
		[[creature_text_flat]]',
		//oval
		'<svg class="creature" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			 width="204.365px" height="211.012px" viewBox="0 0 204.365 211.012" enable-background="new 0 0 204.365 211.012"
			 xml:space="preserve">
		<path class="creature_path" d="M101.54,60.162c-31.357,0-56.777,33.769-56.777,75.425c0,41.656,25.42,75.425,56.777,75.425
			c31.357,0,56.777-33.769,56.777-75.425C158.317,93.931,132.897,60.162,101.54,60.162z M80.169,112.797
			c0-3.672,2.987-6.66,6.659-6.66s6.659,2.988,6.659,6.66s-2.987,6.659-6.659,6.659S80.169,116.469,80.169,112.797z M101.54,161.107
			c-8.004,0-14.516-9.155-14.516-20.409s6.512-20.409,14.516-20.409s14.516,9.156,14.516,20.409S109.544,161.107,101.54,161.107z
			 M116.252,119.456c-3.672,0-6.659-2.987-6.659-6.659s2.987-6.66,6.659-6.66s6.659,2.988,6.659,6.66S119.923,119.456,116.252,119.456
			z"/>
		<path class="creature_path" d="M101.54,124.361c-5.661,0-10.443,7.481-10.443,16.337s4.782,16.336,10.443,16.336
			s10.443-7.481,10.443-16.336S107.201,124.361,101.54,124.361z"/>
		<path class="creature_path" d="M116.252,109.798c-1.653,0-2.999,1.345-2.999,2.999s1.346,2.998,2.999,2.998s2.999-1.345,2.999-2.998
			S117.905,109.798,116.252,109.798z"/>
		<path class="creature_path" d="M86.828,109.798c-1.653,0-2.999,1.345-2.999,2.999s1.346,2.998,2.999,2.998s2.999-1.345,2.999-2.998
			S88.481,109.798,86.828,109.798z"/>
		<defs>
			<path id="textPath" d="M11.035,125.069c0-50.339,40.808-91.147,91.147-91.147s91.147,40.808,91.147,91.147"/>
		</defs>
		[[creature_text_curved]]
		</svg>
		[[creature_text_flat]]',
		// //hexagon
		// '<svg class="creature" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			 // width="204.365px" height="216.216px" viewBox="0 0 204.365 216.216" enable-background="new 0 0 204.365 216.216"
			 // xml:space="preserve">
		// <g>
			// <path class="creature_path" d="M119.891,104.631c-2.062,0-3.74,1.678-3.74,3.74s1.678,3.74,3.74,3.74s3.74-1.678,3.74-3.74
				// S121.954,104.631,119.891,104.631z"/>
			// <path class="creature_path" d="M101.54,121.276c-5.557,0-10.077,6.42-10.077,14.311s4.521,14.312,10.077,14.312s10.077-6.42,10.077-14.312
				// S107.096,121.276,101.54,121.276z"/>
			// <path class="creature_path" d="M83.188,104.631c-2.062,0-3.74,1.678-3.74,3.74s1.678,3.74,3.74,3.74s3.74-1.678,3.74-3.74
				// S85.251,104.631,83.188,104.631z"/>
			// <path class="creature_path" d="M158.894,88.338l-51.659-29.825c-3.132-1.808-8.258-1.808-11.39,0L44.186,88.338
				// c-3.132,1.808-5.695,6.247-5.695,9.864v59.651c0,3.617,2.563,8.056,5.695,9.864l51.659,29.825c3.132,1.808,8.258,1.808,11.39,0
				// l51.659-29.825c3.132-1.808,5.695-6.247,5.695-9.864V98.203C164.589,94.586,162.026,90.147,158.894,88.338z M74.881,108.371
				// c0-4.581,3.727-8.307,8.307-8.307s8.307,3.726,8.307,8.307s-3.727,8.307-8.307,8.307S74.881,112.951,74.881,108.371z
				 // M101.54,154.465c-8.074,0-14.644-8.468-14.644-18.878c0-10.409,6.569-18.877,14.644-18.877s14.644,8.468,14.644,18.877
				// C116.183,145.996,109.614,154.465,101.54,154.465z M119.891,116.677c-4.58,0-8.307-3.726-8.307-8.307s3.727-8.307,8.307-8.307
				// s8.307,3.726,8.307,8.307S124.471,116.677,119.891,116.677z"/>
		// </g>
		// <defs>
			// <path id="textPath" d="M10.392,125.069c0-50.339,40.808-91.147,91.147-91.147 s91.147,40.808,91.147,91.147"/>
		// </defs>
		// [[creature_text_curved]]
		// </svg>
		// [[creature_text_flat]]',
		// //diamond
		// '<svg class="creature" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			 // width="204.365px" height="211.012px" viewBox="0 0 204.365 211.012" enable-background="new 0 0 204.365 211.012"
			 // xml:space="preserve">
		// <path class="creature_path" d="M117.664,114.124c-1.812,0-3.286,1.474-3.286,3.286s1.475,3.286,3.286,3.286
			// c1.812,0,3.287-1.474,3.287-3.286S119.476,114.124,117.664,114.124z"/>
		// <path class="creature_path" d="M106.989,59.222c-2.997-2.997-7.901-2.997-10.898,0l-66.831,66.831c-2.997,2.997-2.997,7.901,0,10.898
			// l66.831,66.831c2.997,2.997,7.901,2.997,10.898,0l66.831-66.831c2.997-2.997,2.997-7.901,0-10.898L106.989,59.222z M78.118,117.409
			// c0-4.024,3.273-7.298,7.298-7.298s7.298,3.274,7.298,7.298s-3.273,7.299-7.298,7.299S78.118,121.434,78.118,117.409z
			 // M101.54,163.788c-8.648,0-15.684-8.116-15.684-18.091s7.035-18.09,15.684-18.09s15.684,8.115,15.684,18.09
			// S110.188,163.788,101.54,163.788z M117.664,124.708c-4.024,0-7.298-3.274-7.298-7.299s3.273-7.298,7.298-7.298
			// s7.299,3.274,7.299,7.298S121.688,124.708,117.664,124.708z"/>
		// <path class="creature_path" d="M85.416,114.124c-1.812,0-3.286,1.474-3.286,3.286s1.475,3.286,3.286,3.286s3.286-1.474,3.286-3.286
			// S87.227,114.124,85.416,114.124z"/>
		// <path class="creature_path" d="M101.54,131.619c-6.436,0-11.672,6.315-11.672,14.078s5.236,14.078,11.672,14.078
			// s11.672-6.315,11.672-14.078S107.975,131.619,101.54,131.619z"/>
		// <defs>
			// <path id="textPath" d="M11.035,125.069c0-50.339,40.808-91.147,91.147-91.147s91.147,40.808,91.147,91.147"/>
		// </defs>
		// [[creature_text_curved]]
		// </svg>
		// [[creature_text_flat]]',
		// //crown
		// '<svg class="creature" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			 // width="204.365px" height="211.012px" viewBox="0 0 204.365 211.012" enable-background="new 0 0 204.365 211.012"
			 // xml:space="preserve">
		// <path class="creature_path" d="M101.54,134.1c-5.926,0-10.746,5.895-10.746,13.141s4.82,13.141,10.746,13.141s10.747-5.895,10.747-13.141
			// S107.465,134.1,101.54,134.1z"/>
		// <path class="creature_path" d="M154.722,79.977l-25.628,20.963c-2.265,1.853-5.506,1.418-7.203-0.966l-17.266-24.265
			// c-1.697-2.384-4.47-2.382-6.163,0.004L81.383,99.788c-1.693,2.387-4.941,2.835-7.217,0.996L48.379,79.952
			// c-2.276-1.839-4.139-0.949-4.139,1.977v108.034c0,2.926,2.394,5.32,5.321,5.32H153.52c2.926,0,5.32-2.394,5.32-5.32V81.929
			// C158.84,79.003,156.987,78.125,154.722,79.977z M79.971,122.636c0-3.706,3.016-6.72,6.722-6.72s6.721,3.015,6.721,6.72
			// s-3.015,6.72-6.721,6.72S79.971,126.342,79.971,122.636z M101.54,164.077c-7.963,0-14.441-7.552-14.441-16.835
			// s6.479-16.835,14.441-16.835c7.964,0,14.442,7.552,14.442,16.835S109.504,164.077,101.54,164.077z M116.387,129.357
			// c-3.706,0-6.721-3.015-6.721-6.72s3.015-6.72,6.721-6.72s6.721,3.015,6.721,6.72S120.093,129.357,116.387,129.357z"/>
		// <path class="creature_path" d="M116.387,119.611c-1.668,0-3.025,1.357-3.025,3.026s1.357,3.026,3.025,3.026s3.025-1.357,3.025-3.026
			// S118.055,119.611,116.387,119.611z"/>
		// <path class="creature_path" d="M86.693,119.611c-1.669,0-3.026,1.357-3.026,3.026s1.357,3.026,3.026,3.026c1.668,0,3.025-1.357,3.025-3.026
			// S88.361,119.611,86.693,119.611z"/>
		// <defs>
			// <path id="textPath" d="M11.035,125.069c0-50.339,40.808-91.147,91.147-91.147s91.147,40.808,91.147,91.147"/>
		// </defs>
		// [[creature_text_curved]]
		// </svg>
		// [[creature_text_flat]]'
	);
	public static function getFooterCreatureArray() {
		return self::FOOTER_CREATURE_ARRAY;

	}
}
