<?php
/**
 * SpecialPage to browse and view videos.
 *
 * @class
 */
class SpecialCopyArticleImages extends SpecialPage {

	/* Methods */

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'CopyArticleImages' );
	}

	public function execute( $sub ) {
		global $wgActiveLanguages, $wgLanguageNames, $wgHooks;

		$wgHooks['ShowSideBar'][] = [ $this, 'removeSideBarCallback' ];
		$wgHooks['ShowBreadCrumbs'][] = [ $this, 'removeBreadCrumbsCallback' ];

		$output = $this->getOutput();
		$user = $this->getUser();
		$lang = $this->getLanguage();
		$request = $this->getRequest();

		// Disable for alt domains
		if (
			// Only on english main site
			( Misc::isAltDomain() || $lang->getCode() !== 'en' ) ||
			// Only staff and staff_widget users
			!array_intersect( $user->getGroups(), [ 'staff', 'staff_widget' ] )
		) {
			$output->setStatusCode( 404 );
			$output->setRobotPolicy( 'noindex,nofollow' );
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $request->wasPosted() ) {
			$this->onFormSubmit( $output, $request );
		} else {
			$output->addModules( [ 'ext.wikihow.copyArticleImages' ] );
			$this->setHeaders();
			$output->setPageTitle( wfMessage( 'cai-title' )->text() );
			$output->setHtmlTitle( wfMessage( 'cai-title' )->text() );
			// Build list of active languages
			$langs = [];
			$allLangs = array_merge( [ 'en' ], $wgActiveLanguages );
			foreach ( $allLangs as $lang ) {
				$langs[] = [
					'code' => $lang,
					'autonym' => Language::fetchLanguageName( $lang ),
					'name' => isset( $wgLanguageNames[$lang] ) ?
						$wgLanguageNames[$lang] : "({$lang})"
				];
			}
			// Add data to page
			$data = json_encode( [ 'langs' => $langs ] );
			$output->addHtml( Html::inlineScript(
				"WH.CopyArticleImages = WH.CopyArticleImages || {};" .
				"Object.assign( WH.CopyArticleImages, {$data} );"
			) );
			$output->addHtml( '<div id="cai">' . wfMessage( 'cai-loading' )->text() . '</div>' );
		}
	}

	public static function removeSideBarCallback( &$showSideBar ) {
		$showSideBar = false;
		return true;
	}

	public static function removeBreadCrumbsCallback( &$showBreadCrumb ) {
		$showBreadCrumb = false;
		return true;
	}
}
