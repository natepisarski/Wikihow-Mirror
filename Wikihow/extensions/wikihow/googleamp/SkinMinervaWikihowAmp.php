<?php
/*
 * Extension of the SkinMinerva skin for wikiHow customization. Used for mobile and tablet devices
 */

class SkinMinervaWikihowAmp extends SkinMinervaWikihow {

	/*
	 * Return values for <html> element..overriden for AMP support
	 * @return array of associative name-to-value elements for <html> element
	 */
	public function getHtmlElementAttributes() {
		$lang = $this->getLanguage();
		return array(
			'lang' => $lang->getHtmlCode(),
			'dir' => $lang->getDir(),
			'class' => 'client-nojs',
			'amp'=>'',
		);
	}

	private function prepareAmpTemplate() {
		global $wgAppleTouchIcon, $wgMFNoindexPages;

		$out = $this->getOutput();
		// add head items
		if ( $wgAppleTouchIcon !== false ) {
			$out->addHeadItem( 'touchicon',
				Html::element( 'link', array( 'rel' => 'apple-touch-icon', 'href' => $wgAppleTouchIcon ) )

			);
		}
		$out->addHeadItem( 'viewport',
			Html::element(
				'meta', array(
					'name' => 'viewport',
					'content' => 'width=device-width,minimum-scale=1,initial-scale=1'
				)
			)
		);
		// hide chrome on bookmarked sites
		$out->addHeadItem( 'apple-mobile-web-app-capable',
			Html::element( 'meta', array( 'name' => 'apple-mobile-web-app-capable', 'content' => 'yes' ) )
		);
		$out->addHeadItem( 'apple-mobile-web-app-status-bar-style',
			Html::element(
				'meta', array(
					'name' => 'apple-mobile-web-app-status-bar-style',
					'content' => 'black',
				)
			)
		);
		$headItems = $out->getHeadItemsArray();
		foreach ( $headItems as $key => $val ) {
			if ( substr( $key, 0, 4 ) === "amp-" ) {
				continue;
			}
			if ( strstr( $val, "<script" ) !== FALSE ) {
				$out->addHeadItem( $key, '' );
			}
		}

		GoogleAmp::addHeadItems( $out );

		if ( $wgMFNoindexPages ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
		}

		if ( $this->isMobileMode ) {
			// @FIXME: This needs to occur before prepareQuickTemplate which wraps the body text in an
			// element with id mw-content-text
			// Otherwise we end up with an unnecessary div.
			$html = ExtMobileFrontend::DOMParse( $out );
		}
		// Generate template after doing the above...

		// search through the head items and remove any disallowed style in there
		foreach( $out->getHeadItemsArray() as $key=>$headItem ) {
			if ( !in_array( $key, [ 'topcss', 'ampboilerplate' ] ) && strstr( $headItem, "<style" ) !== false ) {
				// clear out this unwanted item
				$out->addHeadItem( $key, "");
			}
		}
		// no module styles are allowed in amp
		$out->mModuleStyles = array();

		$tpl = SkinTemplate::prepareQuickTemplate();

		// set amp to true for use in rendering later
		$tpl->set( 'amp', true);
		//$out->setProperty( 'disableSearchAndFooter', true );

		$tpl->set( 'headelement', $out->headElement( $this ) );
		$tpl->set( 'unstyledContent', $out->getProperty( 'unstyledContent' ) );

		$this->prepareHeaderAndFooter( $tpl );
		$this->prepareBanners( $tpl );
		$this->prepareSiteLinks( $tpl );
		$this->prepareWarnings( $tpl );
		$this->preparePageActions( $tpl );
		$this->prepareDiscoveryTools( $tpl );

		// for amp no scripts
		$tpl->set( 'bottomscripts', '' );

		if ( $this->isMobileMode ) {
			$tpl->set( 'bodytext', $html );
			$this->prepareMobileFooterLinks( $tpl );
		}
		return $tpl;
	}

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

		// Add canonical link if it doesn't exist already (it will for Samples)
		if (!$out->mCanonicalUrl) {
			$canonicalUrl = WikihowMobileTools::getNonMobileSite() . '/' . $this->getSkin()->getTitle()->getPrefixedURL();
			$out->setCanonicalUrl($canonicalUrl);
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
			$articleName = $this->getSkin()->getTitle()->getText();
			$isMainPage = $articleName == wfMessage('mainpage')->text();
			$htmlTitle = WikihowSkinHelper::getHTMLTitle($out->getHTMLTitle(), $isMainPage);

			$out->setHTMLTitle($htmlTitle);
		}

		global $wgUniversalEditButton;
		$wgUniversalEditButton = false;

		$tmpl = $this->prepareAmpTemplate();

		$this->prepareWikihowTools($tmpl);

		return $tmpl;
	}

	protected function getLogInOutLink() {
		$loginLogoutLink = parent::getLogInOutLink();

		// this won't show in amp and the logged in user has inline style so let's just return here
		return $loginLogoutLink;
	}

}
