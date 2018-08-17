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
		global $wgHooks, $wgSquidMaxage;

		$wgHooks['CustomSideBar'][] = [ $this, 'makeCustomSideBar' ];
		$wgHooks['ShowBreadCrumbs'][] = [ $this, 'removeBreadCrumbsCallback' ];

		$this->setHeaders();
		$output = $this->getOutput();
		$output->addModules( [ 'ext.wikihow.videoBrowser' ] );

		$videos = ApiSummaryVideos::query();
		$url = $output->getRequest()->getRequestURL();

		$viewing = null;
		$missing = false;
		if ( !empty( $sub ) ) {
			foreach ( $videos['videos'] as $video ) {
				if ( str_replace( ' ', '-', $video['title'] ) === $sub ) {
					$viewing = $video;
					break;
				}
			}
			if ( $viewing === null ) {
				$missing = true;
			}
		}
		if ( $missing ) {
			$output->setStatusCode( 404 );
			$output->setPageTitle( 'Video not found' );
			$output->setHtmlTitle( 'Video not found - wikiHow' );
			$output->addHtml(
				'<div class="section_text">' .
					"<p><b>{$sub}</b> was not found.<br><br></p>" .
					'<p>Visit the <a href="/Video">videos page</a>.</p>' .
				'</div>'
			);
		} else {
			if ( $viewing ) {
				// Viewer
				$pageTitle = wfMessage( 'videobrowser-how-to', $viewing['title'] )->text();
				$htmlTitle = wfMessage( 'videobrowser-viewer-title', $pageTitle )->text();
				$prerender = VideoBrowser::render( 'viewer-prerender.mustache', [
					'url' => $url,
					'read-more' => wfMessage( 'videobrowser-read-more' )->text(),
					'video' => $viewing
				] );
			} else {
				// Index
				$pageTitle = wfMessage( 'videobrowser' )->text();
				$htmlTitle = wfMessage( 'videobrowser-index-title', $pageTitle )->text();
				$prerender = VideoBrowser::render( 'index-prerender.mustache', [] );
			}
			$output->setPageTitle( $pageTitle );
			$output->setHtmlTitle( $htmlTitle );
			$output->addHtml( $prerender );

			$root = FormatJson::encode( preg_replace( "/\/?{$sub}(\?.*)?\$/", '', $url ) );
			$data = FormatJson::encode( $videos );

			$output->addHtml( Html::inlineScript(
				"WH.VideoBrowser = WH.VideoBrowser || {};" .
				"WH.VideoBrowser.data = {$data};\nWH.VideoBrowser.root = {$root};"
			) );
			$output->setRobotPolicy( 'index,follow' );
		}
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
