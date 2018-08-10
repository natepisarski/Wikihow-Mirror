<?php
/**
 * SpecialPage to browse and view videos.
 *
 * @class
 */
class SpecialVideoBrowser extends SpecialPage {

	/* Methods */

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'VideoBrowser' );
		$wgHooks['getToolStatus'][] = [ 'SpecialPagesHooks::defineAsTool' ];
	}

	public function execute( $sub ) {
		global $wgHooks;

		$wgHooks['CustomSideBar'][] = [ $this, 'makeCustomSideBar' ];
		$wgHooks['ShowBreadCrumbs'][] = [ $this, 'removeBreadCrumbsCallback' ];

		$this->setHeaders();
		$output = $this->getOutput();
		$output->addModules( [ 'ext.wikihow.videoBrowser' ] );
		$output->addHtml( Html::element( 'div', [ 'id' => 'videoBrowser' ] ) );

		$data = FormatJson::encode( ApiSummaryVideos::query() );

		$url = $output->getRequest()->getRequestURL();
		$root = FormatJson::encode( preg_replace( "/\/?{$sub}\$/", '', $url ) );

		$output->addHtml( Html::inlineScript(
			"WH.VideoBrowser = WH.VideoBrowser || {};" .
			"WH.VideoBrowser.data = {$data};\nWH.VideoBrowser.root = {$root};"
		) );
		$output->setRobotPolicy( 'index,follow' );
		$output->setSquidMaxage( $wgSquidMaxage );
	}

	public static function makeCustomSideBar( &$customSideBar ) {
		$customSideBar = true;
		return true;
	}

	public static function removeBreadCrumbsCallback( &$showBreadCrumb ) {
		$showBreadCrumb = false;
		return true;
	}
}
