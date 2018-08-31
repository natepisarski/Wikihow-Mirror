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
		global $wgHooks, $wgSquidMaxage, $wgParser;

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

				$title = Title::newFromText( "Summary:{$viewing['title']}" );
				$article = Article::newFromTitle( $title, $output->getContext() );
				$parserOutput = $wgParser->parse(
					$article->getContent(), $title, new ParserOptions()
				);
				$summary = $parserOutput->getText();

				$prerender = VideoBrowser::render( 'viewer-prerender.mustache', [
					'url' => $url,
					'read-more' => wfMessage( 'videobrowser-read-more' )->text(),
					'summary' => $summary,
					'summaryText' => trim( strip_tags( $summary ) ),
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
