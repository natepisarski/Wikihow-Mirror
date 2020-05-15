<?php

class AdminYouTubeIds extends UnlistedSpecialPage {

	static $tags = [
		Misc::YT_WIKIHOW_VIDEOS,
		Misc::YT_GUIDECENTRAL_VIDEOS
	];

	function __construct() {
		parent::__construct( 'AdminYouTubeIds' );
	}

	/**
	 * Execute special page. Only available to wikihow staff.
	 */
	function execute( $par ) {
		global $wgMemc;

		$userGroups = $this->getUser()->getGroups();
		$out = $this->getOutput();
		$request = $this->getRequest();

		if ( !in_array('staff', $userGroups ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$force = false;
		if ( $request->wasPosted() ) {
			$force = $request->getBool( 'rebuild' );
		}
		$tag = in_array( $par, static::$tags ) ? $par : static::$tags[0];
		$key = wfMemcKey( 'AdminYouTubeIds::getAllResults', $tag );
		$data = $wgMemc->get( $key );
		if ( !$data || $force ) {
			$tagOptions = [];
			foreach ( static::$tags as $value ) {
				$tagOptons[] = [
					'value' => $value,
					'selected' => $value === $tag
				];
			}
			$data = [
				'tagOptions' => $tagOptons,
				'tag' => $tag,
				'results' => $this->getAllResults( $tag ),
				'rebuilt' => date( DATE_RFC2822 )
			];
			// Cache results for a day
			$wgMemc->set( $key, $data, 24 * 60 * 60 );
			$out->redirect( '/Special:AdminYouTubeIds/' . $tag );
		}

		$options = array(
			'loader' => new Mustache_Loader_FilesystemLoader( __DIR__ ),
		);
		$m = new Mustache_Engine($options);
		$out->addHtml( $m->render( 'adminyoutubeids.mustache', $data ) );
		$out->setPageTitle( 'YouTube IDs' );
	}

	/**
	 * Collect the list of YouTube IDs by scraping video pages referred to by articles tagged with
	 * youtube_wikihow_videos.
	 *
	 * @return array List of results
	 */
	function getAllResults( $adminTag ) {
		// Build results
		$dbr = wfGetDB( DB_REPLICA );
		$ids = $dbr->selectField(
			'config_storage',
			'cs_config',
			[ 'cs_key' => $adminTag ],
			__METHOD__
		);
		$ids = explode( "\n", $ids );
		$results = [];
		foreach ( $ids as $id ) {
			$articleTitle = Title::newFromId( $id );
			if ( $articleTitle ) {
				$result = [
					'page_id' => $id,
					'page_title' => $articleTitle->getDbKey(),
					'status' => 'error'
				];
				$articleRevision = Revision::newFromPageId( $id );
				if ( $articleRevision ) {
					$articleContent = $articleRevision->getContent();
					if ( $articleContent ) {
						$articleContentText = $articleContent->getText();
						if ( preg_match( '/\{\{Video:([^|]*)\|/', $articleContentText, $matches ) ) {
							$video = Title::newFromText( $matches[1], NS_VIDEO );
							if ( $video ) {
								$videoRevision = Revision::newFromPageId( $video->getArticleId() );
								if ( $videoRevision ) {
									$videoContent = $videoRevision->getContent();
									if ( $videoContent ) {
										$videoContentText = $videoContent->getText();
										if ( preg_match( '/\{\{Curatevideo\|youtube\|([^|]*)\|/', $videoContentText, $matches ) ) {
											$result['status'] = 'ok';
											$result['youtube_id'] = $matches[1];
											$result['channel'] = 'other';
										} else if ( preg_match( '/\{\{Curatevideo\|whyoutube\|([^|]*)\|/', $videoContentText, $matches ) ) {
											$result['status'] = 'ok';
											$result['youtube_id'] = $matches[1];
											$result['channel'] = 'wikiHow';
										} else {
											$result['error'] = 'Curatevideo template not found';
										}
									} else {
										$result['error'] = 'Video content not found';
									}
								} else {
									$result['error'] = 'Video revision not found';
								}
							} else {
								$result['error'] = 'Video title not found';
							}
						} else {
						$result['error'] = 'Video transclusion not found in article';
						}
					} else {
						$result['error'] = 'Article content not found';
					}
				} else {
					$result['error'] = 'Article revision not found';
				}
			} else {
				$result['error'] = 'Article title not found';
			}
			$results[] = $result;
		}
		return $results;
	}
}
