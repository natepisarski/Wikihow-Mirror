<?php

class SchemaMarkup {
	private static $mHowToSchema = '';
	private static $mFAQSchema = '';
	const RECIPE_SCHEMA_CACHE_KEY = "recipe_schema";
	const ARTICLE_IMAGE_WIDTH = 1200;

	const YOUTUBE_CHANNEL_IDS = [
		'UC1gi0J2xmZgP4sRFNEfcy7w', // Multi-lingual
	];
	const GUIDECENTRAL_CHANNEL_IDS = [
		'UCvMFOtVdw6HNNLr8DKP_-mQ', // English
		'UCDed6cayU5-_OYXz8d8pnhQ', // English, Maker Stories
		'UCwjB3hbl9ZKPWzLb1dOmWUA', // Spanish
		'UCISX0Ox43TINPlZjXRuYnBQ', // Portuguese
		'UC14ptrk4ZYOVpR8sDh_KNGA', // French
		'UCVGg4nrmDnDvqVOzIztejqw', // Italian
		'UCvQjRuwBFHJ0e6JcZtYnv-Q', // German
		'UCHJCKmYo1W0P95ATjKQcIVA', // Russian
		'UCkcDfW33LRA-NodnzfElorQ', // Japanese
		'UC9cxxHEETkg7kMHjSWHujdQ', // Chinese
	];

	const SOCIAL_DATA = [
		'ar' => [
			'facebook' => 'https://www.facebook.com/ar.wikihow/',
		],
		'cs' => [
			'facebook' => 'https://www.facebook.com/wikihow.cs/',
		],
		'de' => [
			'facebook' => 'https://www.facebook.com/wikiHow.de/',
		],
		'en' => [
			'facebook' => 'https://www.facebook.com/wikiHow/',
			'twitter' => 'https://twitter.com/wikiHow',
			'instagram' => 'https://www.instagram.com/wikihow/',
			'linkedin' => 'https://www.linkedin.com/company/wikihow/',
			'youtube' => 'https://www.youtube.com/user/WikiHow',
//			'pinterest' => 'https://www.pinterest.com/wikihow/',
		],
		'es' => [
			'facebook' => 'https://www.facebook.com/wikihow.es',
			'twitter' => 'https://www.twitter.com/wikihow_es',
		],
		'fr' => [
			'facebook' => 'https://www.facebook.com/wikiHow.fr/',
		],
		'hi'  => [
			'facebook' => 'https://www.facebook.com/wikihow.hi/',
		],
		'id'  => [
			'facebook' => 'https://www.facebook.com/wikihow.id/',
		],
		'it'  => [
			'facebook' => 'https://www.facebook.com/wikiHow.it/',
		],
		'ja'  => [
			'facebook' => 'https://www.facebook.com/wikihow.ja/',
			'twitter' => 'https://twitter.com/wikiHow_ja',
			'pinterest' => 'https://www.pinterest.com/wikihowja/'
		],
		'ko'  => [
			'facebook' => 'https://www.facebook.com/wikihow.ko/',
		],
		'nl'  => [
			'facebook' => 'https://www.facebook.com/wikiHow.nl/',
		],
		'pt' => [
			'facebook' => 'https://www.facebook.com/wikihowpt',
			'twitter' => 'https://www.twitter.com/wikiHow_pt',
		],
		'ru'  => [
			'facebook' => 'https://www.facebook.com/wikihow.ru/',
		],
		'th'  => [
			'facebook' => 'https://www.facebook.com/wikihow.th/',
		],
		'tr'  => [
			'facebook' => 'https://www.facebook.com/tr.wikihow/',
		],
		'vi'  => [
			'facebook' => 'https://www.facebook.com/WikiHow-Ti%E1%BA%BFng-Vi%E1%BB%87t-816102758534978/',
		],
		'zh' => [
			'facebook' => 'https://www.facebook.com/wikiHow.zh/',
		],
	];

	private static function getDatePublished( $title ) {
		$result = array();

		if ( !$title ) {
			return $result;
		}

		$dp = date_create( $title->getEarliestRevTime() );

		if ( !$dp ) {
			return $result;
		}

		$result['datePublished'] = $dp->format( 'Y-m-d' );
		return $result;
	}

	private static function getDateModified( $title ): array {
		if ( !$title ) {
			return [];
		}

		$gr = GoodRevision::newFromTitle( $title );
		if ( !$gr ) {
			return [];
		}

		$latestGood = $gr->latestGood();
		if ( !$latestGood ) {
			return [];
		}

		$r = Revision::newFromId( $latestGood );
		if ( !$r ) {
			return [];
		}

		$timestamp = $r->getTimeStamp();
		if ( !$timestamp )  {
			return [];
		}

		$dm = date_create( $timestamp );
		if ( !$dm ) {
			return [];
		}

		return [ 'dateModified' => $dm->format('Y-m-d') ];
	}

	private static function getLastReviewedDate( $title ): array {
		$result = [];
		$modifiedDate = null;

		$verifiers = VerifyData::getByPageId($title->getArticleID());

		if ( !empty( $verifiers ) ) {
			$verifier = array_pop( $verifiers );
			if (!empty( $verifier ) && !empty($verifier->date)) {
				$modifiedDate = date_create($verifier->date);
			}
		}

		if ($modifiedDate) {
			$result = [ 'lastReviewed' => $modifiedDate->format('Y-m-d') ];
		}

		return $result;
	}


	private static function getNutritionInformation( $t ) {
		$result = array();

		if ( !$t ) {
			return $result;
		}

		$id = $t->getArticleID();
		if ( $id == 3177283 ) {
			$result = [
				"@type" => "NutritionInformation",
				"calories" => "260",
				"servingSize" => "2 Cabbage Rolls"
			];
			$result = [ 'nutrition' => $result ];
		}

		return $result;
	}

	private static function getPrepTime( $t ) {
		$result = array();

		if ( !$t ) {
			return $result;
		}

		$id = $t->getArticleID();
		if ( $id == 3177283 ) {
			$result = [ "prepTime" => "PT20M" ];
		}

		return $result;
	}

	private static function getCookTime( $t ) {
		$result = array();

		if ( !$t ) {
			return $result;
		}

		$id = $t->getArticleID();
		if ( $id == 3177283 ) {
			$result = [ "cookTime" => "PT1H" ];
		}

		return $result;
	}

	public static function getSchemaTag( $data ) {
		return Html::rawElement(
			'script',
			[ 'type'=>'application/ld+json' ],
			json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG )
		);
	}

	public static function getWikihowOrganization() {
		global $wgLanguageCode;

		$logo = [
			'@type' => 'ImageObject',
			'url' => 'https://www.wikihow.com/skins/owl/images/wikihow_logo_nobg_60.png',
			'width' => 326,
			'height' => 60,
		];

		// Use the mobile version of the url as suggested by deepcrawl seo
		// and google:  https://developers.google.com/search/mobile-sites/mobile-first-indexing.
		// Alt domains should serve up the 'en' domain url (eg www or m.wikihow.com) as suggested by
		// search brothers seo
		//
		// 12/10/19 - Make the alt domains (which are now responsive) always point to www. As we make other domains
		// responsive we should do the same
		// 2/19/20 - en is now responsive so make that the desktop version as well
		if (Misc::isAltDomain() || !Misc::isMobileModeLite()) {
			$url = Misc::getLangBaseURL($wgLanguageCode);
		} else {
			$url = Misc::getLangBaseURL($wgLanguageCode, Misc::isMobileMode());
		}
		$data = [
			"@context"=> "http://schema.org",
			"@type" => "Organization",
			"name" => "wikiHow",
			"url" => $url,
			"logo" => $logo,
		];

		$socialData = self::getSocialData();
		if ($socialData) {
			$data['sameAs'] =  array_values($socialData);
		}

		return $data;
	}

	public static function getGuideCentralOrganization() {
		return [
			"@context"=> "http://schema.org",
			"@type" => "Organization",
			"name" => "GuideCentral",
			"logo" => [
				'@type' => 'ImageObject',
				'url' => 'https://www.wikihow.com/skins/owl/images/guidecentral.png',
				'width' => 250,
				'height' => 250,
			],
			'sameAs' => [
				'https://www.facebook.com/guidecentral',
				'https://www.instagram.com/guidecentral_/',
				'https://twitter.com/guidecentral',
				'https://www.pinterest.com/guidecentral/',
			]
		];
	}

	private static function getPublisher() {
		$result = [ 'publisher' => self::getWikihowOrganization() ];
		return $result;
	}

	// call in context of php query
	private static function getTextFromStep( $step ) {
		$text = pq( $step)->find('script,.reference')->remove()->end()->text();
		$text = trim( $text );
		return $text;
	}

	public static function getRecipeInstructions( $title, $text ) {
		$parsed = MessageCache::singleton()->parse( $text, null, false, false )->getText();
		$doc = phpQuery::newDocument( $parsed );
		$stepsId = '#'.wfMessage('steps')->text();
		$stepsUpper = strtoupper( $stepsId );
		$stepsSelector = $stepsId . ', ' . $stepsUpper;
		// if we want all the steps then we can iterate over this pq($stepsId)->parent()->nextAll()
		// until we see the next h2
		$steps = array();

		foreach ( pq( $stepsSelector )->filter(':first')->parent()->nextAll( 'ol:first' )->children( 'li' ) as $step ) {
			$text = self::getTextFromStep( $step );
			$text = preg_replace( '~\x{00a0}~siu',' ', $text );
			if ( !trim( $text ) ) {
				continue;
			}
			$stepData = [
				"@type" => "HowToStep",
				"text" => $text
			];
			$steps[] = $stepData;
		}
		return $steps;
	}

	private static function getHowToStepImageFromStep( $step ) {
		global $wgIsDevServer;
		$url = '';
		$img = pq( $step )->find( 'img:not(.m-video-wm-img):first' );
		$ampImg = pq( $step )->find( 'amp-img:not(.m-video-wm-img):first' );
		if ( $img->length > 0 ) {
			$url = $img->attr( 'src' );
		} elseif ( $ampImg->length > 0 ) {
			$url = $ampImg->attr( 'src' );
		} else {
			$video = pq( $step )->find( 'video.m-video:first' );
			if ( $video->length > 0 ) {
				$url = $video->attr( 'data-poster' );
			}
		}

		if ( $url && $wgIsDevServer && !preg_match('@^https?:@', $url) ) {
			// just use a valid url for testing purposes
			$url = "https://www.wikihow.com" . $url;
		}

		return $url;
	}

	private static function getHowToSteps() {
		global $wgTitle, $wgServer;
		$sections = array();
		$sectionNumber = 1;

		foreach ( pq( '.section.steps' ) as $section ) {
			if ( pq( $section )->hasClass( '.sample' ) ) {
				continue;
			}
			$name = pq($section)->find('.mw-headline:first')->text();
			$name = trim( preg_replace( '~\x{00a0}~siu',' ', $name ) );
			if ( !$name ) {
				$name = "Method " . $sectionNumber;
			}
			$steps = array();
			$i = 0;
			foreach ( pq( $section )->find( '.steps_list_2 > li' ) as $stepItem ) {
				$stepId = pq( $stepItem )->attr( 'id' );
				$step = pq( $stepItem )->find( '.step' )->clone();
				pq( $step )->find( 'sup,script' )->remove();
				$i++;
				$text = pq( $step)->text();

				// use this to change nbsp to regular space
				$text = trim( preg_replace( '~\x{00a0}~siu',' ',$text ) );
				if ( !$text ) {
					continue;
				}
				$stepData = [
					"@type" => "HowToStep",
					"text" => $text
				];

				// TODO this?
				$stepName = "";
				if ( $stepName ) {
					$stepData['name'] = $stepName;
				}

				$stepImage = self::getHowToStepImageFromStep( $stepItem );
				if ( $stepImage ) {
					$stepData['image'] = $stepImage;
				}
				if ( $stepId ) {
					$url = $wgServer . '/' . $wgTitle->getPrefixedURL();
					$url = wfExpandUrl( $url, PROTO_CANONICAL );
					$stepData['url'] = $url . '#' . $stepId;
				}
				$steps[] = $stepData;
			}

			if ( count( $steps ) < 2 ) {
				continue;
			}

			$sectionNumber++;

			$sections[] = array( 'stepdata' => $steps, 'name' => $name );
		}


		if ( empty( $sections ) ) {
			return array();
		}

		if ( count( $sections ) == 1 ) {
			$result = ['step' => $sections[0]['stepdata']];
		} else {
			$howToSections = array();
			foreach ( $sections as $section ) {
				$howToSections[] = [
					'@type' => 'HowToSection',
					'name' => $section['name'],
					'itemListElement' => $section['stepdata']
				];
			}
			$result = [ 'step' => $howToSections ];
		}
		return $result;
	}

	/**
	 * get an html script tag of ld+json schema for a Recipe article
	 *
	 *
	 * @param title Title page title
	 * @param rvisionId int revision id of schema we want
	 * @return string script tag with json data or empty string if we can't create one
	 */
	public static function getRecipeSchema( $title, $revisionId ) {
		global $wgMemc;

		if ( !$title ) {
			return "";
		}
		$pageId = $title->getArticleID();
		if ( !$pageId ) {
			return "";
		}

		$cacheKey = wfMemcKey(
			self::RECIPE_SCHEMA_CACHE_KEY,
			$title->getArticleID(),
			$revisionId,
			$title->getTouched()
		);
		$val = $wgMemc->get( $cacheKey );
		if ( $val !== false ) {
			return $val;
		}

		$schema = '';
		$dbw = wfGetDB( DB_MASTER );
		if ( $dbw->tableExists( 'recipe_schema' ) ) {
			$schema = $dbw->selectField( 'recipe_schema',
				'rs_data',
				array( 'rs_page_id' => $pageId, 'rs_rev_id' => $revisionId ),
				__METHOD__
			);
			if ( $schema == false ) {
				$schema = '';
			}
		}

		// set in memcached
		$wgMemc->set( $cacheKey, $schema);

		return $schema;
	}

	public static function calculateRecipeSchema( $title ) {
		global $wgMemc;

		if ( !$title ) {
			return "";
		}
		$pageId = $title->getArticleID();
		if ( !$pageId ) {
			return "";
		}

		$latestGoodText = self::getLatestGoodRevisionText( $title );
		$ingredients = self::getIngredients( $title, $latestGoodText );
		if ( !$ingredients ) {
			return '';
		}

		// get some more recipe data
		$data = self::getTestRecipeData( $pageId );
		$data += [
			"@context"=> "http://schema.org",
			"@type" => "Recipe",
			"name" => $title->getText(),
		];

		$data += self::getSchemaImage( $title );
		$data += self::getAuthors( $title );
		$data += self::getAggregateRating( $title );
		$data += self::getDatePublished( $title );
		$data += self::getDateModified( $title );
		$data += self::getNutritionInformation( $title );
		$data += self::getPrepTime( $title );
		$data += self::getCookTime( $title );
		$data['description'] = self::getDescription( $title );
		$data['recipeIngredient'] = $ingredients;

		if ( WHVid::isYtSummaryArticle( $title ) ) {
			$youTubeVideo = WHVid::getYTVideoFromArticle( $title );
			if ( $youTubeVideo['status'] === 'ok' ) {
				$videoData = self::getYouTubeVideo( $title, $youTubeVideo['youtube_id'] );
			}
		} else {
			$videoData = self::getVideo( $title );
		}

		if ( $videoData ) {
			$data['video'] = $videoData;
		}
		$data['recipeCategory'] = self::getRecipeCategory( $title );

		$recipeInstructions = self::getRecipeInstructions( $title, $latestGoodText );

		if ( !empty( $recipeInstructions ) )  {
			$data['recipeInstructions'] = $recipeInstructions;
			$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );
		}

		return $schema;
	}

	// run in the context of php query to get the how to schema information
	public static function calcHowToSchema( $out ) {
		// does sanity checks on the title and wikipage and $out
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		$title = $out->getTitle();
		$wikiPage = $out->getWikiPage();

		$data = [
			"@context"=> "http://schema.org",
			"@type" => "HowTo",
			"headline" => $title->getText(),
			"name" => $title->getText(),
		];

		$data += self::getSchemaImage();
		$data += self::getAuthors( $title );
		$data += self::getAggregateRating( $title );
		$data += self::getDatePublished( $title );
		$data += self::getDateModified( $title );
		$data += self::getPublisher();
		$data += self::getContributors( $title );
		$steps = self::getHowToSteps();

		$schema = '';
		if ( !empty( $steps ) ) {
			if ( is_array( $steps ) ) {
				$data = array_merge( $data, $steps );
			}

			$data['description'] = self::getDescription( $title );

			Hooks::run( 'SchemaMarkupAfterGetData', array( &$data ) );

			$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );
		}

		// set in class static var
		self::$mHowToSchema = $schema;
	}

	private static function getFAQQuestion( $qaItem ) {
		$okToShow = false;
		if ( pq( $qaItem )->find( '.qa_expert_area' )->length > 0 ) {
			$okToShow = true;
		}
		if ( pq( $qaItem )->find( '.qa_user_label' )->text() == 'Staff Answer' ) {
			$okToShow = true;
		}
		if ( $okToShow != true ) {
			return;
		}

		$name = pq( $qaItem )->find( '.qa_q_txt:first' )->text();
		$name = preg_replace( '~\x{00a0}~siu',' ',$name );
		$name = trim( $name );
		if ( !$name ) {
			return '';
		}
		$answer = array();
		$answerText = pq( $qaItem )->find( '.qa_answer:first' )->text();
		$answerText = preg_replace( '~\x{00a0}~siu',' ',$answerText );
		$answerText = trim( $answerText );
		if ( !$answerText ) {
			return '';
		}
		$answer['@type'] = 'Answer';
		$answer['text'] = $answerText;
		$result = array();
		$result['@type'] = "Question";
		$result['name'] = $name;
		$result['acceptedAnswer'] = $answer;
		return $result;
	}

	// run in the context of php query to get the how to schema information
	public static function calcFAQSchema( $out ) {
		// does sanity checks on the title and wikipage and $out
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		$title = $out->getTitle();
		$wikiPage = $out->getWikiPage();

		$questions = array();

		foreach ( pq( '#qa .qa_li_container' ) as $section ) {
			$question = self::getFAQQuestion( $section );
			if ( !$question ) {
				continue;
			}
			$questions[] = $question;
		}
		if ( empty( $questions ) ) {
			return '';
		}

		$data = [
			"@context"=> "http://schema.org",
			"@type" => "FAQPage",
			"headline" => $title->getText(),
			"name" => $title->getText(),
			"mainEntity" => $questions
		];

		$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );

		// set in class static var
		self::$mFAQSchema = $schema;
	}

	private static function getAggregateRating( $title ) {
		global $wgLanguageCode;
		$result = array();

		if ( !$title ) {
			return $result;
		}

		if ( $wgLanguageCode != 'en' ) {
			return $result;
		}

		if ( !SocialProofStats::okToShowRating( $title ) ) {
			return $result;
		}

		$data = SocialProofStats::getPageRatingData( $title->getArticleID() );

		if ( !$data || !$data->ratingCount || !$data->rating ) {
			return $result;
		}

		$rating = [
			'@type' => 'AggregateRating',
			'bestRating' => 100,
			'ratingCount' => $data->ratingCount,
			'ratingValue' => $data->rating
		];

		$result = [ 'aggregateRating' => $rating ];
		return $result;
	}

	public static function getRecipeCategory( $title ) {
		//check if it has recipe
		$allCats = self::getCategoryListForBreadcrumb( $title );
		if ( !$allCats ) {
			return 'general';
		}
		$cat = null;
		$matchingCategories = null;
		for ( $i = 0; $i < count( $allCats ); $i++ ) {
			$count = count( $allCats[$i] );
			if ( !$count ) {
				continue;
			}
			foreach ( $allCats[$i] as $num => $catTitle ) {
				$text = $catTitle->getText();
				// if one of the categories is recipes and it's not the last one
				// then we are ok to get the category from the tree
				if ( ( $text == "Recipes" || $text == "Food and Entertaining" ) && $num != $count - 1 ) {
					$matchingCategories = $allCats[$i];
					break;
				}
			}
			if ( $matchingCategories != null ) {
				break;
			}
		}
		if ( $matchingCategories ) {
			$cat = $matchingCategories[$count - 1];
			if ( $cat && $cat != "Category:Recipes" ) {
				$cat = $cat->getText();
			} else {
				$cat = '';
			}
		}
		if ( !$cat ) {
			$cat = 'general';
		}
		return $cat;
	}

	private static function getVideoData( $title, $videoTemplateText ) {
		global $wgServer, $wgLanguageCode;
		if ( !$videoTemplateText ) {
			return '';
		}

		$uploadDate = '';
		$exploded = explode( '|', trim( $videoTemplateText, '{}' ) );
		if ( $exploded )  {
			$last = end( $exploded );
			$last = Title::newFromText( $last, NS_IMAGE );
			if ( $last ) {
				$uploadDate = $last->getEarliestRevTime();
				$uploadDate = wfTimestamp( TS_ISO_8601, $uploadDate );
			}
		}

		$titleText = $title->getText();
		$description = '';
		$summaryData = SummarySection::summaryData( $titleText );
		if ( $summaryData && array_key_exists( 'content', $summaryData ) ) {
			$description = trim( strip_tags( SummarySection::summaryData( $titleText )['content'] ) );
		}

		// Get combined mobile/desktop play count for summary video
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			'titus_copy',
			[ 'plays' => '(ti_summary_video_play + ti_summary_video_play_mobile)' ],
			[
				'ti_page_id' => $title->getArticleID(),
				'ti_language_code' => $wgLanguageCode
			],
			__METHOD__
		);
		$plays = $row ? $row->plays : 0;

		$videoTemplate = explode( '|', $videoTemplateText );
		if ( count( $videoTemplate ) < 2 ) {
			return '';
		}
		$videoName = $videoTemplate[1];
		$contentUrl= WHVid::getVidUrl( $videoName );
		if ( $contentUrl ) {
			$contentUrl = WH_CDN_VIDEO_ROOT . wfUrlencode( $contentUrl );
		}

		$previewImg = str_replace( "}}", "", $videoTemplate[2] );
		$summaryIntroImage = WHVid::getSummaryImage( $previewImg );
		$thumbnailUrl= WHVid::getSummaryImageThumbUrl( $summaryIntroImage, 1000 );
		if ( !preg_match( '/^https?:\/\//', $thumbnailUrl ) ) {
			$thumbnailUrl= $wgServer . $thumbnailUrl;
		}

		$data = [
			'@context'=> 'http://schema.org',
			'@type' => 'VideoObject',
			'publisher' => self::getWikihowOrganization(),
			'name' => wfMessage( 'howto', $titleText )->text(),
			'description' => $description,
			'thumbnailUrl' => $thumbnailUrl,
			'contentUrl' => $contentUrl,
			'interactionStatistic' => [
				'@type' => 'InteractionCounter',
				'interactionType' => [ '@type' => 'http://schema.org/WatchAction' ],
				'userInteractionCount' => $plays
			],
			'uploadDate' => $uploadDate,
		];
		return $data;
	}

	public static function getLatestGoodRevisionText( $title ) {
		$gr = GoodRevision::newFromTitle( $title );
		if ( !$gr ) {
			return '';
		}

		$latestGood = $gr->latestGood();
		if ( !$latestGood ) {
			return '';
		}
		$r = Revision::newFromId( $latestGood );
		if ( !$r ) {
			return '';
		}
		return ContentHandler::getContentText( $r->getContent() );
	}

	public static function getVideo( $title ) {
		if ( !$title ) {
			return '';
		}

		$text = self::getLatestGoodRevisionText( $title );
		if ( !$text ) {
			return '';
		}

		preg_match_all( '@{{whvid.*?Step 0.*?}}@m', $text, $out );
		$data = '';
		if ( $out ) {
			if ( $out[0] ) {
				$data = self::getVideoData( $title, $out[0][0] );
			}
			return $data;
		}

		// we could look for regular videos too but for now just summary videos
		//preg_match_all( '@{{whvid(.*?)\}}@m', $text, $out );

		return '';
	}

	public static function getYouTubeVideoStatus( $id ) {
		global $wgMemc;
		$requestKey = "YouTubeInfo({$id})";
		$cacheKey = wfMemcKey( $requestKey );
		$info = $wgMemc->get( $cacheKey );
		$response = AsyncHttp::read( $requestKey );
		$isExpired = AsyncHttp::isExpired( $requestKey );
		$apiCacheKey = md5( $requestKey );
		$apiCacheStatus = $response ? ( $isExpired ? 'expired' : 'ok' ) : 'not-found';
		if ( $apiCacheStatus === 'expired' ) {
			// Be a little more specific
			$updated = wfTimestamp( TS_UNIX, $response['updated'] );
			if ( $updated + $response['ttl'] < wfTimestamp() + ( 24 * 60 * 60 ) ) {
				$apiCacheStatus .= ' - refresh pending';
			}
		}
		if ( $response['updated'] != $response['created'] ) {
			$apiCacheStatus .= ' - retry pending';
		}
		$apiResponseStatus = $response ? 'valid' : 'none';
		if ( $response ) {
			// Check for other problems that can occur
			$data = @json_decode( $response['body'] );
			if ( $data === null ) {
				$apiResponseStatus = 'unparsable';
			} else {
				if ( !$data ) {
					$apiResponseStatus = 'invalid';
				} else if ( !is_array( $data->items ) || !count( $data->items ) ) {
					$apiResponseStatus = 'empty';
				}
			}
		}
		$objectCacheStatus = $info ? 'ok' : 'not-found';
		return [
			'videoId' => $id,
			'apiCacheKey' => $apiCacheKey,
			'apiCacheStatus' => $apiCacheStatus,
			'apiResponseStatus' => $apiResponseStatus,
			'objectCacheStatus' => $objectCacheStatus
		];
	}

	public static function getYouTubeVideoReport( $id ) {
		$status = static::getYouTubeVideoStatus( $id );
		return "Video Schema Report\n" .
			"Video ID: {$status['videoId']}\n" .
			"API Cache Key: {$status['apiCacheKey']}\n" .
			"API Cache Status: {$status['apiCacheStatus']}\n" .
			"API Response Status: {$status['apiResponseStatus']}\n" .
			"Object Cache Status: {$status['objectCacheStatus']}";
	}

	/**
	 * Gets schema markup for a YouTube video.
	 *
	 * Info is fetched async, so the first time it is called for a given YouTube ID it will return
	 * an empty string, indicating the schema isn't available yet. Once the info is available, the
	 * title that initiated the call will be purged so this method is called again - except this
	 * time it will read from the cached response.
	 *
	 * AsyncHttp is used to store and read back the response from the API called invoked in a job,
	 * and memcache is used for the trimmed down portion of the response we use in the schema.
	 *
	 * @param  [type] $title Title object of page YouTube video is embedded in, if page is in the
	 *     main namespace this will be used to purge the cache and regnerate the page after the info
	 *     has been fetched
	 * @param  [type] $id YouTube video ID string
	 * @param  [type] $forceRefresh Forces the function to call out to the YouTube api to get new data
	 * @return [array|string] Array containing VideoObject schema data or an empty string if schema
	 *   is currently being fetched
	 */
	public static function getYouTubeVideo( $title, $id, $forceRefresh = false ) {
		global $wgMemc, $wgCanonicalServer;

		$requestKey = "YouTubeInfo({$id})";
		$cacheKey = wfMemcKey( $requestKey );

		$info = $wgMemc->get( $cacheKey );
		if ( $info === false || $forceRefresh) {
			// Lookup info in DB
			$response = AsyncHttp::read( $requestKey );
			if ( $response && $response['status'] === 200 ) {
				$data = json_decode( $response['body'] );
				$item = $data->items[0];
				$info = [
					'name' => $item->snippet->title,
					'description' => $item->snippet->description,
					'thumbnailUrl' => [
						$item->snippet->thumbnails->default->url,
						$item->snippet->thumbnails->high->url
					],
					'contentUrl' => "https://www.youtube.com/watch?v={$id}",
					'embedUrl' => "https://www.youtube.com/embed/{$id}",
					'interactionStatistic' => [
						'@type' => 'InteractionCounter',
						'interactionType' => [ '@type' => 'http://schema.org/WatchAction' ],
						'userInteractionCount' => $item->statistics->viewCount
					],
					'uploadDate' => $item->snippet->publishedAt
				];
				// Add publisher info for videos on our own channels
				if ( in_array( $item->snippet->channelId, self::YOUTUBE_CHANNEL_IDS ) ) {
					$info = array_merge( [ 'publisher' => self::getWikihowOrganization() ], $info );
				} else if ( in_array( $item->snippet->channelId, self::GUIDECENTRAL_CHANNEL_IDS ) ) {
					$info = array_merge( [ 'publisher' => self::getGuideCentralOrganization() ], $info );
				}

				$wgMemc->set( $cacheKey, $info );
			}

			// If the response is missing or expired, setup a job to fetch it
			if ( !$response || AsyncHttp::isExpired( $requestKey ) || $forceRefresh ) {
				$purgeUrls = [];
				if ( $title->inNamespace( NS_MAIN ) ) {
					$altDomain = AlternateDomain::getAlternateDomainForPage( $title->getArticleID() );
					if ( $altDomain ) {
						$purgeUrls[] = 'https://' . $altDomain . '/' . $title->getPrefixedDBkey();
					} else {
						$purgeUrls[] = $wgCanonicalServer . '/' . $title->getPrefixedDBkey();
					}
				}

				wfDebugLog(
					'youtubeinfo',
					">> SchemaMarkup::getYouTubeVideo\n" . var_export( [
						'title' => $title->getText(),
						'id' => $id,
						'requestKey' => $requestKey,
						'cacheKey' => $cacheKey,
						'purgeUrls' => $purgeUrls,
						'forceRefresh' => $forceRefresh,
						'status' => 'queuing'
					], true ) . "\n"
				);

				$job = Job::factory( 'YouTubeInfoJob', $title, [
					'id' => $id,
					'requestKey' => $requestKey,
					'cacheKey' => $cacheKey,
					'purgeUrls' => $purgeUrls,
					'forceRefresh' => $forceRefresh
				] );
				JobQueueGroup::singleton()->push( $job );
			}
		}
		if ( $info ) {
			return array_merge( [
				'@context'=> 'http://schema.org',
				'@type' => 'VideoObject'
			], $info );
		}
		return '';
	}

	public static function getIngredientsText( $title, $text ) {
		global $wgParser;
		if ( !$text ) {
			return '';
		}
		$ingredientsPos = strpos( $text , '== Ingredients ==' );

		$section = '';
		$i = 0;
		$done = false;
		while ( !$done ) {
			$section = $wgParser->getSection($text, $i);
			$sectionTitle = WikihowArticleEditor::getSectionTitle( $section );
			if ( $sectionTitle == "Ingredients" ) {
				$section = trim(preg_replace("@^==.*==@", "", $section));
				$done = true;
			}
			if ( !$section ) {
				$done = true;
			}
			$i++;
		}

		return trim( $section );
	}

	public static function getIngredientsFromText( $ingredients ) {

		// this is a way to parse some text...it might be a way to get the list we want
		//$x = MessageCache::singleton()->parse( $ingredients, null, true, true )->getText();
		// some ingredients sections use ''' instead of ===, so fix it first
		$ingredients = str_replace( "'''", "===", $ingredients );
		// if we have a subsection or multiple ingredient subsections, just get data from first one
		$filtered = array();
		foreach ( explode( "\n", $ingredients ) as $key => $line ) {
			if ( substr( $line, 0, 3 ) == "===" ) {
				if ( $key == 0 ) {
					continue;
				} else {
					break;
				}
			} else {
				$filtered[] = $line;
			}
		}
		$ingredients = implode( "\n", $filtered );
		$ingredients = array_values( array_filter( array_map( 'trim', explode( '*', $ingredients ) ) ) );
		foreach ( $ingredients as $k=>$val ) {
			$item = strtok( $val, "\n" );
			$item = MessageCache::singleton()->parse( $item, null, false, false )->getText();
			$item = Sanitizer::stripAllTags( $item );
			$ingredients[$k] = $item;
		}
		return $ingredients;
	}

	public static function getIngredients( $title, $latestGoodText ) {
		$text = self::getIngredientsText( $title, $latestGoodText );
		$ingredients = self::getIngredientsFromText( $text );
		return $ingredients;
	}

	private static function getContributors( $t ) {
		$result = [];

		$verifier = self::getVerifierName( $t );
		if ($verifier) {
			$result['contributor'] = ['@type' => 'Person', 'name' => $verifier];
		}
		return $result;
	}

	private static function getReviewedBy( $t ) {
		$result = [];

		$verifier = self::getVerifierName( $t );
		if ($verifier) {
			$result['reviewedBy'] = [ '@type' => 'Person', 'name' => $verifier ];
		}

		return $result;
	}

	private static function getVerifierName( $t ) {
		$verifiers = VerifyData::getByPageId( $t->getArticleID() );

		if ( empty( $verifiers ) ) {
			return '';
		}

		$verifier = array_pop( $verifiers );
		if ( empty( $verifier ) || $verifier->name == '' ) {
			return '';
		}

		return $verifier->name;
	}

	private static function getAuthors( $t, $isMedicalWebpage = false) {
		$author = self::getWikihowOrganization();

		$aid = $t->getArticleID();
		if ( !$isMedicalWebpage && ArticleTagList::hasTag( 'schema_expert_author', $aid ) ) {
			$verifier = self::getVerifierName( $t );
			if ( $verifier ) {
				$author = $verifier;
			}
		}

		$result = [ 'author' => $author ];

		return $result;
	}

	private static function getSchemaImage( $title = null ) {
		global $wgIsDevServer;

		$result = array();
		// if we pass a title in, clear out the AMI cached info so we can
		// force it to regenerate the image. otherwise if this is run in a script
		// it will not get the correct image due to using a static var
		if ( $title != null ) {
			ArticleMetaInfo::$wgTitleAmiImageName = null;
			ArticleMetaInfo::$wgTitleAMIcache = null;
		}
		$thumb = ArticleMetaInfo::getTitleImageThumb( $title, self::ARTICLE_IMAGE_WIDTH );
		if ( !$thumb ) $thumb = self::getDefaultArticleImage();
		if ( !$thumb ) return $result;

		$url = wfGetPad( $thumb->getUrl() );
		if ( !$url ) {
			return $result;
		}

		if ( $wgIsDevServer && !preg_match('@^https?:@', $url) ) {
			// just use a valid url for testing purposes
			$url = "https://www.wikihow.com" . $url;
		}

		// check if the thumb we requested is actually created at that size
		// if not, use the file width and height
		$width = $thumb->getWidth();
		$height = $thumb->getHeight();
		if ( get_class( $thumb ) == "ThumbnailImage" ) {
			$file = $thumb->getFile();
			$fileWidth = intval( $file->getWidth() );
			$fileHeight = intval( $file->getHeight() );
			if ( $fileWidth < intval( $width ) ) {
				$width = $fileWidth;
				$height = $fileHeight;
			}
		}
		$image = [ '@type' => 'ImageObject',
			'url' => $url,
			'width' => $width,
			'height' => $height
		];

		$result = [ 'image' => $image ];
		return $result;
	}

	private static function getDefaultArticleImage() {
		global $wgDefaultImage;

		if (RequestContext::getMain()->getLanguage()->getCode() == 'en')
			$image_name = $wgDefaultImage;
		else
			$image_name = 'Default_wikihow_green_large_intl.png';

		$file = wfFindFile($image_name);

		return $file ? $file->getThumbnail(self::ARTICLE_IMAGE_WIDTH) : null;
	}

	private static function okToShowSchema( $out ) {

		// sanity check the input
		if ( !$out ) {
			return false;
		}

		// getting the wikipage does not work for special pages so
		// do more sanity checking
		$title = $out->getTitle();
		if ( !$title || !$title->inNamespace(NS_MAIN) || !WikihowSkinHelper::shouldShowMetaInfo($out) ) {
			return false;
		}

		return true;
	}

	// get the json schema to put on the page if applicable
	// uses $out to get title and wikipage but does not write to $out
	public static function getSchema( $out ) {
		global $wgIsDevServer;
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		$title = $out->getTitle();
		$pageId = $title->getArticleID();

		$isMainPage = $title
			&& $title->inNamespace(NS_MAIN)
			&& $title->getText() == wfMessage('mainpage')->inContentLanguage()->text()
			&& $out->getRequest()->getVal('action', 'view') == 'view';

		$schema = "";
		if ( $isMainPage ) {
			$schema = self::getOrganizationSchema( $out );
		} else {
			$schema = self::getArticleSchema( $out );
			$howToSchema = self::getHowToSchema( $pageId );
			if ( $howToSchema ) {
				$schema .= $howToSchema;
			}

			$faqSchema = self::getFAQSchema();
			if ( $faqSchema ) {
				$schema .= $faqSchema;
			}

			if ( !ArticleTagList::hasTag( 'breadcrumb_schema_off', $title->getArticleID() ) ) {
				$schema .= self::getBreadcrumbSchema( $out );
			}

			if ( CategoryHelper::isTitleInCategory( $title, "Recipes" ) ) {
				if ( $wgIsDevServer ) {
					$goodRevision = GoodRevision::newFromTitle( $title );
					self::processRecipeSchema( $title, $goodRevision, true );
				}
				$schema .= self::getRecipeSchema( $title, $out->getRevisionId() );
			}

			if ( ArticleTagList::hasTag('schema_medicalwebpage', $title->getArticleId() ) ) {
				$schema .= self::getMedicalWebPageShema( $out );
			}
		}

		return $schema;
	}

	private static function getHowToSchema() {
		if ( self::$mHowToSchema ) {
			return self::$mHowToSchema;
		}
	}

	private static function getFAQSchema() {
		if ( self::$mFAQSchema ) {
			return self::$mFAQSchema;
		}
	}

	private static function getMainEntityOfPage( $title ) {
		$result = array();
		if ( !$title ) {
			return $result;
		}

	   $canonical = "https://" . Misc::getCanonicalDomain() . '/' . $title->getPrefixedURL();
	   $canonical = wfExpandUrl( $canonical, PROTO_CANONICAL );

		if ( !$canonical ) {
			return $result;
		}

		$result = [ "mainEntityOfPage" => [ '@type' => 'WebPage', 'id' => $canonical] ];

		return $result;
	}

	/**
	 * Returns the Organization schema to be displayed on our main pages.  Reference:
	 * https://developers.google.com/search/docs/data-types/corporate-contact
	 * @param $out
	 * @return string Schema script
	 */
	public static function getOrganizationSchema( $out ) {
		// does sanity checks on the title and wikipage and $out
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		// Don't add a schema for no branding domains
		if (class_exists('AlternateDomain')
			&& AlternateDomain::onNoBrandingDomain()) {
			return '';
		}

		$data = self::getWikihowOrganization();

		$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );
		return $schema;
	}

	private static function getBreadcrumbItem( $num, $id, $name ) {
		$data = [
			'@type' => 'ListItem',
			'position' => $num,
			'item' => [ '@id' => $id, 'name' => $name ]
		];
		return $data;
	}

	public static function getBreadcrumbSchema( $out ) {
		// does sanity checks on the title and wikipage and $out
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		return self::getBreadcrumbSchemaFromTitle( $out->getTitle() );
	}

	public static function getCategoryListForBreadcrumb( $title ) {
		$tree = CategoryHelper::getCurrentParentCategoryTree();
		$allCats = array();

		// get the list
		foreach ( $tree as $catTitle => $parents ) {
			// skip any in categories to ignore
			$tempCategories = wfMessage( 'categories_to_ignore' )->inContentLanguage()->text();
			$tempCategories = explode( "\n", $tempCategories );
			$categoriesToIgnore = array();
			foreach ( $tempCategories as $catToIgnore ) {
				$parts = explode( "/", $catToIgnore );
				$categoriesToIgnore[] = end( $parts );
			}

			if ( in_array( $catTitle, $categoriesToIgnore ) ) {
				continue;
			}
			// check if top level category is indexable or not
			$catTitle = Title::newFromText($catTitle);
			$indexable = RobotPolicy::isIndexable( $catTitle );
			if ( !$indexable ) {
				continue;
			}

			$cats = [$catTitle];
			$parentCats = CategoryHelper::flattenCategoryTree( $parents );
			if ( !$parentCats ) {
				// edge case if the article is in a top level category
				if ( count( $tree ) == 1 ) {
					$allCats[] = array_reverse( $cats );
				}
				continue;
			}
			foreach ( $parentCats as $parent ) {
				$parentTitle = Title::newFromText( $parent );
				$indexable = RobotPolicy::isIndexable( $catTitle );
				if ( $indexable ) {
					$cats[] = $parentTitle;
				}
			}
			$allCats[] = array_reverse( $cats );
		}
		return $allCats;
	}

	public static function getBreadcrumbSchemaFromTitle( $title ) {
		$items = array();
		$items[] = self::getBreadcrumbItem( 1, 'https://www.wikihow.com', 'wikiHow' );
		$allCats = self::getCategoryListForBreadcrumb( $title );
		global $wgServer;
		if ( count( $allCats ) > 0 ) {
			foreach ( $allCats[0] as $num => $catTitle ) {
				$url = $wgServer . '/' . $catTitle->getPrefixedURL();
				$url = wfExpandUrl( $url, PROTO_CANONICAL );
				$name = $catTitle->getText();
				$items[] = self::getBreadcrumbItem( $num + 2, $url, $name );
			}
		}
		$url = $wgServer . '/' . $title->getPrefixedURL();
		$url = wfExpandUrl( $url, PROTO_CANONICAL );
		$name = wfMessage('howto', $title->getText() )->text();
		$items[] = self::getBreadcrumbItem( count( $items ) + 1, $url, $name );
		$data = [
			"@context"=> "http://schema.org",
			"@type" => "BreadcrumbList",
			"itemListElement" => $items
		];

		$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );
		return $schema;
	}

	private static function getDescription( $title ) {
		$description = ArticleMetaInfo::getCurrentTitleMetaDescription();
		$description = preg_replace( '~\x{00a0}~siu', ' ', $description );
		if ( !trim( $description ) ) {
			$description = wfMessage( 'article_meta_description', $title->getText() )->text();
		}
		return $description;
	}
	public static function getArticleSchema( $out ) {
		// does sanity checks on the title and wikipage and $out
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		$title = $out->getTitle();
		$wikiPage = $out->getWikiPage();

		// TODO do we want the headline to say How to??
		$data = [
			"@context"=> "http://schema.org",
			"@type" => "Article",
			"headline" => $title->getText(),
			"name" => "How to " . $title->getText(),
		];

		$data += self::getMainEntityOfPage( $title );
		$data += self::getSchemaImage();
		$data += self::getAuthors( $title );
		$data += self::getDatePublished( $title );
		$data += self::getDateModified( $title );
		$data += self::getPublisher();
		$data += self::getContributors( $title );

		$data['description'] = self::getDescription( $title );

		Hooks::run( 'SchemaMarkupAfterGetData', array( &$data ) );

		$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );
		return $schema;
	}

	public static function getMedicalWebPageShema($out ) {
		// does sanity checks on the title and wikipage and $out
		if ( !self::okToShowSchema( $out ) ) {
			return '';
		}

		$title = $out->getTitle();

		// TODO do we want the headline to say How to??
		$data = [
			"@context"=> "http://schema.org",
			"@type" => "MedicalWebPage",
			"headline" => $title->getText(),
			"name" => "How to " . $title->getText(),
		];
		$data += self::getMainEntityOfPage( $title );
		$data += self::getSchemaImage();
		$data += self::getAuthors( $title, true );
		$data += self::getDatePublished( $title );
		$data += self::getDateModified( $title );
		$data += self::getLastReviewedDate( $title );
		$data += self::getPublisher();
		$data += self::getReviewedBy( $title );


		Hooks::run( 'SchemaMarkupAfterGetData', array( &$data ) );

		$schema = Html::rawElement( 'script', [ 'type'=>'application/ld+json' ], json_encode( $data, JSON_PRETTY_PRINT | JSON_HEX_TAG ) );
		return $schema;
	}


	public static function getTestRecipeData( $pageId ) {
		global $IP;
		$result = array( $pageId => []);
		$csv = file_get_contents($IP .'/extensions/wikihow/schema/testRecipeData.csv');
		$elements = array_map("str_getcsv", explode("\n", $csv));

		// the first line has the keys we will use
		$firstLine = array_shift( $elements );
		foreach ( $elements as $el ) {
			$line = [ 'nutrition'=> [ '@type'=>'NutritionInformation' ] ];
			$pos = 0;
			$id = $el[0];
			foreach ( $firstLine as $key ) {
				$pos++;
				if ( !$el[$pos-1] || !in_array( $key, [ "calories", "servingSize", "prepTime", "cookTime", "recipeYield" ] ) ) {
					continue;
				}
				if ( in_array( $key, [ "calories", "servingSize" ] ) ) {
					$line['nutrition'][$key] = $el[$pos - 1];
				} else {
					$line[$key] = $el[$pos - 1];
				}
			}
			if ( isset( $line['prepTime'] ) ) {
				try {
					$t =  new DateInterval( $line['prepTime'] );
					if ( intval( $t->format('%h%i%s') == 0 ) ) {
						unset( $line['prepTime'] );
					}
				} catch ( Exception $e ) {
					unset( $line['prepTime'] );
				}
			}
			if ( isset( $line['cookTime'] ) ) {
				try {
					$t =  new DateInterval( $line['cookTime'] );
					if ( intval( $t->format('%h%i%s') == 0 ) ) {
						unset( $line['cookTime'] );
					}
				} catch ( Exception $e ) {
					unset( $line['cookTime'] );
				}
			}
			// add the line to the result only if it has calorie information
			if ( $line['nutrition'] && isset( $line['nutrition']['calories'] ) ) {
				$result[$id] = $line;
			}
		}

		return $result[$pageId];
	}

	public static function updateRecipeSchema( $pageId, $revisionId, $schema ) {
		global $wgReadOnly;
		if ($wgReadOnly) {
			// We don't throw an error here because this apparently
			// is sometimes called when viewing an article page as
			// anon.
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'recipe_schema',
			array( 'rs_rev_id'=> $revisionId, 'rs_data' => $schema ),
			array( 'rs_page_id' => $pageId ),
			__METHOD__
		);
	}

	public static function insertRecipeSchema( $pageId, $revisionId, $schema ) {
		global $wgReadOnly;
		if ($wgReadOnly) {
			// Don't try to update database when site is read-only
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'recipe_schema',
			array( 'rs_rev_id'=> $revisionId, 'rs_page_id' => $pageId, 'rs_data' => $schema ),
			__METHOD__
		);
	}


	public static function getRecipeSchemaRevision( $pageId ) {
		$dbw = wfGetDB( DB_MASTER );
		$revision = $dbw->selectField( 'recipe_schema',
			'rs_rev_id',
			array( 'rs_page_id' => $pageId ),
			__METHOD__
		);
		return $revision;
	}

	/*
	 *
	 * forceUpdate - do an update even if the schema for this revision is already in the table
	 */
	public static function processRecipeSchema( $title, $goodRevision, $forceUpdate = false ) {
		if ( !$title || !$goodRevision ) {
			return true;
		}
		if ( !CategoryHelper::isTitleInCategory( $title, "Recipes" ) ) {
			return true;
		}
		// do not process this if the recipe schema table does not exist
		$dbr = wfGetDB( DB_REPLICA );
		if ( !$dbr->tableExists( 'recipe_schema' ) ) {
			return '';
		}
		$latestGood = $goodRevision->latestGood();
		if ( !$latestGood ) {
			return true;
		}
		$pageId = $title->getArticleID();
		$dbRev = self::getRecipeSchemaRevision( $pageId );
		$schema = null;
		if ( !$dbRev ) {
			$schema = SchemaMarkup::calculateRecipeSchema( $title );
			self::insertRecipeSchema( $pageId, $latestGood, $schema );
		} elseif ( $dbRev < $latestGood || $forceUpdate ) {
			$schema = SchemaMarkup::calculateRecipeSchema( $title );
			self::updateRecipeSchema( $pageId, $latestGood, $schema );
		}
		if ( $schema ) {
			global $wgMemc;
			$cacheKey = wfMemcKey( self::RECIPE_SCHEMA_CACHE_KEY, $title->getArticleID(), $latestGood );
			$wgMemc->set( $cacheKey, $schema);
		}
		return true;
	}
	/**
	 * Refresh the metadata after the article edit is patrolled, good revision is updated
	 * and before squid is purged. See GoodRevision::onMarkPatrolled for more details.
	 */
	public static function onAfterGoodRevisionUpdated( $title, $goodRevision ) {
		if ( !CategoryHelper::isTitleInCategory( $title, "Recipes" ) ) {
			return true;
		}
		global $wgTitle;
		$oldTitle = $wgTitle;
		$wgTitle = $title;
		self::processRecipeSchema( $title, $goodRevision );
		$wgTitle = $oldTitle;
	}

	public static function beforeArticlePurge( $wikiPage ) {
		if ( $wikiPage ) {
			$title = $wikiPage->getTitle();
			if ( !CategoryHelper::isTitleInCategory( $title, "Recipes" ) ) {
				return true;
			}
			$revision = $title->getLatestRevID();

			$goodRevision = GoodRevision::newFromTitle( $title );
			$latestGood = $goodRevision->latestGood();
			if ( $latestGood == $revision ) {
				self::processRecipeSchema( $title, $goodRevision, true );
			}
		}
		return true;
	}

	public static function getSocialData($lang = ''): array {
		global $wgLanguageCode;
		$lang = $lang ? $lang : $wgLanguageCode;
		return self::SOCIAL_DATA[$lang] ?? [];
	}
}

