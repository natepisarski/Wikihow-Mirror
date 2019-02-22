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
		global $wgHooks, $wgSquidMaxage, $wgParser, $wgCanonicalServer, $wgSitename;

		$output = $this->getOutput();

		// Disable for alt domains
		if ( Misc::isAltDomain() ) {
			$output->setStatusCode( 404 );
			return;
		}

		$wgHooks['CustomSideBar'][] = [ $this, 'makeCustomSideBar' ];
		$wgHooks['ShowBreadCrumbs'][] = [ $this, 'removeBreadCrumbsCallback' ];

		$output->addModules( [ 'ext.wikihow.videoBrowser' ] );

		$url = $output->getRequest()->getRequestURL();
		$output->setCanonicalUrl( $wgCanonicalServer . $url );
		$parsedUrl = parse_url( $url );
		$parts = explode( '/', $parsedUrl['path'] );
		if ( $parts[0] === '' && $parts[1] === 'Special:VideoBrowser' ) {
			$path = implode( '/', array_merge( [ '', 'Video' ], array_slice( $parts, 2 ) ) );
			$output->redirect( $path, 301 );
			return;
		}

		$this->setHeaders();

		$videos = ApiSummaryVideos::query();
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
				$summary = SummarySection::summaryData( $viewing['title'] );
				$summaryHtml = SummarySection::summaryData( $viewing['title'] )['content'];
				$summaryText = trim( strip_tags( $summaryHtml ) );
				$titleText = wfMessage( 'videobrowser-meta-title', $viewing['title'] )->text();
				$descriptionText = wfMessage( 'videobrowser-meta-description', $viewing['title'], $summaryText )->text();

				$meta = [
					'meta-title' => [ 'name' => 'title', 'content' => $titleText ],
					'meta-description' => [ 'name' => 'description', 'content' => $descriptionText ],
					'og-title' => [ 'property' => 'og:title', 'content' => $pageTitle ],
					'og-site_name' => [ 'property' => 'og:site_name', 'content' => $wgSitename ],
					'og-url' => [ 'property' => 'og:url', 'content' => $wgCanonicalServer . $url ],
					'og-description' => [ 'property' => 'og:description', 'content' => $descriptionText ],
					'og-type' => [ 'property' => 'og:type', 'content' => 'video.other' ],
					'og-image' => [ 'property' => 'og:image', 'content' => $viewing['poster'] ],
					'og-video' => [ 'property' => 'og:video', 'content' => $viewing['video'] ],
				];

				$items = [];
				foreach ( $meta as $name => $attributes ) {
					$output->addHeadItem( $name, Html::element( 'meta', $attributes ) );
				}

				$prerender = VideoBrowser::render( 'viewer-prerender.mustache', [
					'url' => $url,
					'read-more' => wfMessage( 'videobrowser-read-more' )->text(),
					'context' => wfMessage( 'videobrowser-context' )->text(),
					'summary' => "<p>{$summaryHtml}</p>",
					'summaryText' => $summaryText,
					'howToTitle' => wfMessage( 'videobrowser-how-to', $viewing['title'] )->text(),
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

			$root = FormatJson::encode(
				preg_replace( "/(\\/" . preg_quote( $sub, '/' ) . ")?\$/", '', $parsedUrl['path'] )
			);
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
