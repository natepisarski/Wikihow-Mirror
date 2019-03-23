<?php

if (!defined('MEDIAWIKI')) die();

class PagePolicy {

	const VALIDATION_TOKEN_NAME = 'validate';
	const EXCEPTIONS = [ 'Sandbox' ];
	const RESTRICTED_NAMESPACES = [
		NS_TALK,
		NS_IMAGE_TALK,
		NS_VIDEO,
		NS_VIDEO_TALK,
		NS_VIDEO_COMMENTS,
		NS_VIDEO_COMMENTS_TALK,
		NS_MEDIAWIKI,
		NS_MEDIAWIKI_TALK,
		NS_PROJECT_TALK,
		NS_SUMMARY,
		NS_SUMMARY_TALK
	];
	static $mustache;

	private static function render( $template, $vars ) {
		if ( !self::$mustache ) {
			self::$mustache = new Mustache_Engine( array(
				'loader' => new Mustache_Loader_CascadingLoader( [
						new Mustache_Loader_FilesystemLoader( __DIR__ ),
					] )
			) );
		}
		return self::$mustache->render( $template, $vars );
	}

	public static function showCurrentTitle( $context ) {
		static $showCurrentTitle = -1; // compute this lazily, only once
		if ( $showCurrentTitle === -1 ) {
			$title = $context->getTitle();
			$user = $context->getUser();
			$exception = ArticleTagList::hasTag(
				'anon-visibility-exceptions', $title->getArticleId()
			);

			if ( $user->isAnon() && !$exception ) {
				if ( $title->inNamespaces( self::RESTRICTED_NAMESPACES ) ) {
					$showCurrentTitle = false;
				} elseif ( $title->inNamespace( NS_USER ) ) {
					// Hide "bad" user pages
					$showCurrentTitle = UserPagePolicy::isGoodUserPage( $title->getDBKey() );
				} elseif ( $title->inNamespace( NS_USER_TALK ) ) {
					if ( $title->isSubPage() ) {
						// Hide user talk sub pages
						$showCurrentTitle = false;
					} else {
						// Hide user talk pages belonging to users who've been inactive for 1+ years
						$owner = User::newFromName( $title->getBaseText() );
						$lastYear = wfTimestamp( TS_MW, strtotime( '-1 year' ) );
						$showCurrentTitle = !$owner || $owner->getTouched() >= $lastYear;
					}
				} elseif (
					// Any main namespace article that exists and isn't black-listed
					$title->inNamespace( NS_MAIN ) &&
					$title->exists() &&
					!in_array( $title->getDBKey(), self::EXCEPTIONS )
				) {
					$req = $context->getRequest();
					$isNew = $req->getVal( 'new' );
					$token = $req->getVal( self::VALIDATION_TOKEN_NAME );
					if ( $isNew && wfHasCurrentArticleCreationCookie() ) {
						$showCurrentTitle = true;
					} elseif ( $token ) {
						$pageid = $title->getArticleId();
						$showCurrentTitle = self::validateToken( $token, $pageid );
					} else {
						$showCurrentTitle = RobotPolicy::isTitleIndexable( $title, $context );
					}
				} else {
					$showCurrentTitle = true;
				}
			} else {
				$showCurrentTitle = true;
			}
			Hooks::run( 'PagePolicyShowCurrentTitle', array( $title, &$showCurrentTitle ) );
		}
		return $showCurrentTitle;
	}

	// Enables mobile for 404s
	public static function onIsEligibleForMobile( &$mobileAllowed ) {
		global $wgOut;
		if ( !self::showCurrentTitle( $wgOut->getContext() ) ) {
			$mobileAllowed = true;
		}
	}

	// Disables post-processing when displaying a 404 page
	public static function onPreWikihowProcessHTML( $title, &$processHTML ) {
		global $wgOut;
		$processHTML = self::showCurrentTitle( $wgOut->getContext() );
	}

	// We want to 404 deindexed pages for anon users and make them log in to see it.
	// This hook runs within Article::view after the article object is fetch but
	// the usual 404 page is displayed.
	public static function onArticleViewHeader(&$article, &$outputDone, &$useParserCache) {
		if ( !self::showCurrentTitle( $article->getContext() ) ) {
			$outputDone = true;
			$useParserCache = false;
			$article->mOldId = 0; // necessary because GoodRevision sometimes sets an oldid for anons
		}
		return true;
	}

	public static function onBeforePageDisplay( $out, $skin ) {
		global $wgTitle, $wgUser, $wgLanguageCode;

		if ( !self::showCurrentTitle( $out->getContext() ) ) {
			$out->getRequest()->response()->header( 'HTTP/1.1 404 Not Found' );
			$out->clearHTML();

			// Pretend the page exists if it's for an existing user
			$userPageOverride = false;
			if ( $wgTitle->inNamespace( NS_USER ) ) {
				$user = User::newFromName( $wgTitle->getDBKey() );
				$userPageOverride = $user && $user->getID() !== 0;
			}

			$css = Misc::getEmbedFile( 'css', __DIR__ . '/pagepolicy.css' );
			$out->addHeadItem( 'pagepolicy-css', HTML::inlineStyle( $css ) );

			if ( $wgTitle->exists() || $userPageOverride ) {
				if ( $wgTitle->inNamespace( NS_MAIN ) ) {
					$login = Title::newFromText( 'Special:UserLogin' );
					$url = $login->getCanonicalURL( [ 'returnto' => $wgTitle->getPrefixedUrl() ] );
					$out->addHTML( self::render(
						GoogleAmp::isAmpMode( $out ) ?
							'article_under_review_amp' : 'article_under_review',
						[
							'review_header' => wfMessage( 'pagepolicy_review_header' )->text(),
							'review_message' => wfMessage( 'pagepolicy_review_message', $url )->parse(),
							'search_header' => wfMessage( 'pagepolicy_search_header' )->text(),
							'searchbox' => SearchBox::render( $out ),
							'home_message' => wfMessage( 'pagepolicy_home_message' )->parse()
						]
					) );
				} else {
					// For existing pages that are being hidden, show a login to view message and form
					$out->addModules( 'ext.wikihow.login_popin' );
					$out->addHTML( self::render(
						Misc::isMobileMode() ? 'login_mobile' : 'login_desktop',
						[
							'encoded_title' => $wgTitle->getPrefixedUrl(),
							'login_message' => wfMessage( 'pagepolicy_login_message' )->text()
						]
					) );
				}
			} else {
				// Otherwise, show a "title doesn't exist" message
				$out->addHTML( self::render(
					Misc::isMobileMode() ? 'notitle_mobile' : 'notitle_desktop',
					[
						'error_message' => wfMessage( 'nopagetitle' )->text()
					]
				) );
			}
		}
	}

	public static function getLoginModal($returnto) {
		return self::render(
			'login_popin',
			[
				'login_chunk' => UserLoginBox::getLogin( false, true, $returnto ),
				'login_header' => wfMessage('pagepolicy_login_header')->text()
			]
		);
	}

	private static function validateToken($token, $pageid) {
		list($time, $digest_in) = explode('-', $token);
		$time = (int)$time;
		if (!$digest_in) return false; // check input
		if ($time < time()) return false; // token has expired
		$digest_gen = self::generateToken($time, $pageid);
		return $digest_gen === $digest_in;
	}

	private static function generateToken($time, $pageid) {
		$time = (int)$time;
		$pageid = (int)$pageid;
		if (!$time || !$pageid) return '';
		$data = "$time,$pageid";
		$digest = hash_hmac("sha256", $data, WH_VALIDATRON_HMAC_KEY);
		// keep only first 16 characters, because we don't need THAT much security :^)
		return substr($digest, 0, 16);
	}

	/**
	 * Pass in a URL to append validation tokens to.
	 * @param string $url URL to which to append
	 * @param int $pageid page ID of the page to validate. This must stay constant for token to validate.
	 * @param int $duration how long the validation token should last, in seconds. default is 1 week.
	 */
	public static function generateTokenURL($url, $pageid, $duration = 7 * 24 * 60 * 60) {
		$time = time() + $duration;
		$token = self::generateToken($time, $pageid);
		if ( strpos($url, '?') !== false && !preg_match('@[?&]$@', $url) ) {
			$url .= '&';
		} else {
			$url .= '?';
		}
		$url .= self::VALIDATION_TOKEN_NAME . "=$time-$token";
		return $url;
	}
}
