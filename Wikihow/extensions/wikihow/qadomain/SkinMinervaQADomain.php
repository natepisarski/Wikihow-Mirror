<?
/*
 * Extension of the SkinMinerva skin for wikiHowAnswers customization.
 */

class SkinMinervaQADomain extends SkinMinerva {
	public $template = 'MinervaTemplateQADomain';

	/*
	 * Return values for <html> element..overriden for AMP support
	 * @return array of associative name-to-value elements for <html> element
	 */
	public function getHtmlElementAttributes() {
		$attr = parent::getHtmlElementAttributes();
		$out = $this->getOutput();
		if(GoogleAmp::isAmpMode($out)) {
			$attr['amp'] = '';
		}
		return $attr;
	}

	private function prepareAmpTemplate() {
		global $wgAppleTouchIcon;
		global $wgWellFormedXml;
		$wgWellFormedXml = true;
		wfProfileIn( __METHOD__ );
		$out = $this->getOutput();
		// add head items
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
			if ( strstr( $val, "<script" ) !== FALSE ) {
				$out->addHeadItem( $key, '' );
			}
		}

		QADomain::addHeadItems( $out );

		$html = ExtMobileFrontend::DOMParse( $out );

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

		$tpl->set('amp', true);

		$tpl->set( 'headelement', $out->headElement( $this ) );

		// for amp no scripts
		$tpl->set( 'bottomscripts', '' );
		$tpl->set( 'bodytext', $html );

		return $tpl;
	}

	/*
	 * Override parent method to add a few more things to the html head element
	 */
	protected function prepareQuickTemplate() {

		$out = $this->getOutput();
		global $wgEnableAPI, $wgAdvertisedFeedTypes;
		$wgEnableAPI = false; //removing stuff from the <head> that we don't want on this domain
		$wgAdvertisedFeedTypes = [];

			// Setting different viewport
			$out->addHeadItem('viewport',
				Html::element(
					'meta', array(
						'name' => 'viewport',
						'content' => 'width=device-width',
					)
				)
			);

		$out->addMeta('google-site-verification', QADomain::getGoogleSiteverification());
		if( !GoogleAmp::isAmpMode( $out ) ) {
			$tmpl = parent::prepareQuickTemplate();
		} else {
			$tmpl = $this->prepareAmpTemplate();
		}

		return $tmpl;
	}

	protected function getSkinStyles() {
		return [];
	}

	public function getPageClasses( $title ) {
		$className = parent::getPageClasses( $title );
		$className .= QADomain::getPageClass();
		return $className;
	}

}
