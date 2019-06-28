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
		global $wgHooks, $wgSquidMaxage, $wgMemc, $wgParser, $wgCanonicalServer, $wgSitename;

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
			if ( strpos( $sub, 'Category:' ) === 0 ) {
				// Special category index
			} else {
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
				$htmlTitle = wfMessage( 'videobrowser-viewer-title', $viewing['title'] )->text();
				$summary = SummarySection::summaryData( $viewing['title'] );
				$summaryHtml = SummarySection::summaryData( $viewing['title'] )['content'];
				$summaryText = trim( strip_tags( $summaryHtml ) );
				$titleText = wfMessage( 'videobrowser-meta-title', $viewing['title'] )->text();
				$descriptionText = wfMessage( 'videobrowser-meta-description', $viewing['title'], $summaryText )->text();
				$howToTitle = wfMessage( 'videobrowser-how-to', $viewing['title'] )->text();

				$meta = [
					'meta-title' => [ 'name' => 'title', 'content' => $titleText ],
					'meta-description' => [ 'name' => 'description', 'content' => $descriptionText ],
					'og-title' => [ 'property' => 'og:title', 'content' => $titleText ],
					'og-site_name' => [ 'property' => 'og:site_name', 'content' => $wgSitename ],
					'og-url' => [ 'property' => 'og:url', 'content' => $wgCanonicalServer . $url ],
					'og-description' => [ 'property' => 'og:description', 'content' => $descriptionText ],
					'og-type' => [ 'property' => 'og:type', 'content' => 'video.other' ],
					'og-image' => [ 'property' => 'og:image', 'content' => $viewing['poster'] ],
					'og-video' => [ 'property' => 'og:video', 'content' => $viewing['video'] ],
				];

				// Breadcrumbs
				$pre = [
					[
						'label' => wfMessage( 'videobrowser-breadcrumb-mainpage' )->text(),
						'link' => $wgCanonicalServer . '/Main-Page'
					],
					[
						'label' => wfMessage( 'videobrowser-breadcrumb-video' )->text(),
						'link' => $wgCanonicalServer . '/Video'
					],
				];
				foreach ( $meta as $name => $attributes ) {
					$output->addHeadItem( $name, Html::element( 'meta', $attributes ) );
				}
				$breadcrumbs = explode( ',', $viewing['breadcrumbs'] );
				$categories = array_reverse( array_slice( $breadcrumbs, 0, 1 ) );
				$top = array_pop( array_slice( $breadcrumbs, -1 ) );
				foreach ( $categories as $index => $breadcrumb ) {
					$categories[$index] = [
						'label' => $breadcrumb,
						'link' => $wgCanonicalServer . '/Video/Category:' . str_replace( ' ', '-', $top )
					];
				}
				$post = [
					[
						'label' => $howToTitle,
						'link' => $wgCanonicalServer . $url
					]
				];
				$list = array_merge( $pre, $categories, $post );
				foreach ( $list as $index => $item ) {
					$list[$index]['position'] = (int)$index;
					$list[$index]['first'] = $index === 0;
					$list[$index]['last'] = $index === count( $list ) - 1;
				}

				$youtubeIds = [
					5775245 => 'pPYmcaPwwVU',
					8570867 => 'ogKBevxvdr8',
					8326974 => 'JQ0K--cv5Y4',
					13268 => 'b-F7OtrLaoc',
					66809 => '0c9PExGc9WE',
					663332 => 'I4WHOEsq1Ko',
					41306 => '469JJk1Wf1c',
					1800408 => 'CODnVX7VAZ8',
					155200 => 'emvdufe6t-8',
					316096 => 'jVFV_1pOqDY',
					3399 => 'Mcx1Q4uIjkY',
					3630441 => 'paAKkQUYqjs',
					149992 => 'iiyMR0LhipA',
					3743929 => 'P7fWu3yEw-Y',
					154200 => '6jHI-95fSTY',
					14904 => 'sSV6ZwxVR1U',
					2344358 => 'VPNjnNbzZxA',
					563462 => '2StTVY6y9xg',
					138597 => 'Rg1XZfF-ybc',
					3823 => 'zSv-RzesjYo',
					4420660 => 'Tirwu-YE_3I',
					19549 => 'n9zwdJh7LMA',
					8002860 => 'UvGe6A04bJc',
					482185 => 'YjHVnlOEFc8',
					375502 => 'R0qkRne1_jQ',
					134856 => 'R-QBlNYpl6c',
					2448869 => '23yM30uH-Wo',
					9426953 => 'hhfkNrFxkcM',
					1412189 => 'maCNg8DJ0s4',
					9431347 => 'kYZJKvZaCG4',
					842696 => 'U7Poo8AAIas'
				];

				if ( $youtubeIds[$viewing['id']] ) {
					$key = wfMemcKey( "SpecialVideoBrowser/YouTubeInfo/{$viewing['id']}" );
					$info = $wgMemc->get( $key );
					if ( $info === false ) {
						$data = json_decode( file_get_contents( wfAppendQuery(
							'https://www.googleapis.com/youtube/v3/videos',
							[
								'part' => 'statistics,snippet',
								'id' => $youtubeIds[$viewing['id']],
								'key' => WH_YOUTUBE_API_KEY
							]
						) ) );
						$info = [
							'plays' => $data->items[0]->statistics->viewCount,
							'updated' => $data->items[0]->snippet->publishedAt
						];
						$wgMemc->set( $key, $info );
					}
					$viewing = array_merge( $viewing, $info );
					$schema = SchemaMarkup::getYouTubeVideo( $output->getTitle(), $youtubeIds[$viewing['id']] );
				} else {
					$schema = SchemaMarkup::getVideo( Title::newFromId( $viewing['id'] ) );
				}

				$prerender = VideoBrowser::render( 'viewer-prerender.mustache', [
					'url' => $url,
					'read-more' => wfMessage( 'videobrowser-read-more' )->text(),
					'context' => wfMessage( 'videobrowser-context' )->text(),
					'summary' => "<p>{$summaryHtml}</p>",
					'titleText' => $titleText,
					'howToTitle' => $howToTitle,
					'video' => $viewing,
					'breadcrumbs' => $list,
					'youtube' => $youtubeIds[$viewing['id']],
					'schema' => $schema ? SchemaMarkup::getSchemaTag( $schema ) : ''
				] );
			} else {
				// Index
				$pageTitle = wfMessage( 'videobrowser' )->text();
				$htmlTitle = wfMessage( 'videobrowser-index-title', $pageTitle )->text();
				$prerender = VideoBrowser::render( 'index-prerender.mustache', [] );
			}
			$output->setPageTitle( null );
			$output->setHtmlTitle( $htmlTitle );
			$output->addHtml( $prerender );

			$root = FormatJson::encode(
				preg_replace( "/(\\/" . preg_quote( $sub, '/' ) . ")?\$/", '', urldecode( $parsedUrl['path'] ) )
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
