<?php

if (!defined('MEDIAWIKI')) die();

use MediaWiki\MediaWikiServices;

class PagePolicy {

	const VALIDATION_TOKEN_NAME = 'validate';
	const EXCEPTIONS = [ 'Sandbox' ];
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
				$showCurrentTitle = false;
				$req = $context->getRequest();
				if ( $title->inNamespace( NS_MAIN ) ) {
					// Any main namespace article that exists and isn't black-listed
					if ( $title->exists() && !in_array( $title->getDBKey(), self::EXCEPTIONS ) ) {
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
				} elseif ( $title->inNamespace( NS_CATEGORY ) ) {
					// managed on a per-category page level in the WikihowCategory
					// class.
					// Example 200: https://www.wikihow.com/Category:Health
					// Example 404: https://www.wikihow.com/Category:Stub
					$showCurrentTitle = true;
				} elseif ( $title->inNamespace( NS_PROJECT ) ) {
					if ( $title->exists() ) {
						$showCurrentTitle = WikihowNamespacePages::isAvailableToAnons($title);
					} else {
						$showCurrentTitle = true;
					}
				} elseif ( $title->inNamespace( NS_PROJECT_TALK ) ) {
					$pages = WikihowNamespacePages::anonAvailableTalkPages();
					$showCurrentTitle = in_array( $title->getDBKey(), $pages );
				} elseif ( $title->inNamespace( NS_FILE ) ) {
					// Show Image: urls to anons for now on EN and intl. Phase 2 will remove them.
					$showCurrentTitle = true;
				} elseif ( $title->inNamespace( NS_USER ) ) {
					// Hide "bad" user pages
					$showCurrentTitle = UserPagePolicy::isGoodUserPage( $title->getDBKey() );
				} elseif ( $title->inNamespace( NS_USER_TALK ) ) {
					$listAnonVisible = UserPagePolicy::listUserTalkAnonVisible();
					if ( $title->isSubPage() ) {
						// Hide user talk sub pages
						$showCurrentTitle = false;
					} elseif ( in_array( $title->getDBKey(), $listAnonVisible ) ) {
						// Always show this small list of User_talk pages
						$showCurrentTitle = true;
					} elseif ( !$user->hasCookies() ) {
						// Hide user talk pages from users without any backend cookies (ie, including most bots)
						$showCurrentTitle = false;
					} else {
						// Hide user talk pages belonging to users who've been inactive for 1+ years
						$owner = User::newFromName( $title->getBaseText() );
						$lastYear = wfTimestamp( TS_MW, strtotime( '-1 year' ) );
						$showCurrentTitle = !$owner || $owner->getDBTouched() >= $lastYear;
					}
				} elseif ( $title->inNamespace( NS_USER_KUDOS ) ) {
					// Show the kudos pages to anons iff that anon has cookies
					$showCurrentTitle = $user->hasCookies();
				} elseif ( $title->inNamespace( NS_SPECIAL ) ) {
					// Only show special pages where we've designated them as appropriate for anons.
					// Right now, this matches up almost exactly with special pages that we allow
					// to be viewed on mobile.
					$spFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
					$specialPage = $spFactory->getPage( $title->getDBkey() );
					if ( $specialPage &&
						( $req->wasPosted() || $specialPage->isAnonAvailable() )
					) {
						$showCurrentTitle = true;
					}
				} else {
					// All other namespaces are restricted by default
					$showCurrentTitle = false;
				}

				// 404 edit pages for non-Main namespace articles too
				// Ex: https://www.wikihow.com/index.php?title=wikiHow:About-wikiHow&action=edit
				if ( !$title->inNamespace(NS_MAIN) && $title->exists() && $req->getVal('action') == 'edit' ) {
					$showCurrentTitle = false;
				}

				// 404 login pages with returnto actions to pages that don't exist
				// If the page does exist, PageHooks::redirectIfLoginWithReturnToRequest will
				// redirect to the existing page and popup a login dialog. Otherwise the user will
				// get a blank page with a login dialog.
				// Ex: https://www.wikihow.com/Special:UserLogin?returnto=PageThatDoesNotExist
				if ( $title->isSpecial('Userlogin') && $req->getVal('returnto', '') !== '' ) {
					$showCurrentTitle = false;
				}

				if ( $showCurrentTitle && !self::isVisibleAction($req) ) {
					$showCurrentTitle = false;
				}
			} else {
				$showCurrentTitle = true;
			}
			Hooks::run( 'PagePolicyShowCurrentTitle', array( $title, &$showCurrentTitle ) );
		}
		return $showCurrentTitle;
	}

	private static function isVisibleAction($req) {
		global $wgLanguageCode;

		$anonVisible = true;
		$actionParam = $req->getVal('action', '');
		$typeParam = $req->getVal('type', '');
		$oldidParam = $req->getVal('oldid', '');
		// NOTE: $actionParam == 'edit' check is because we do a redirect if action=edit in
		// a different spot back to view and the new anon edit dialog.
		if ( $wgLanguageCode == 'en' && $actionParam == 'edit') {
			$anonVisible = true;
		} elseif ( in_array($actionParam, ['login','preview','purge','submit','submit2']) ) {
			$anonVisible = true;
		} elseif ( $actionParam && $actionParam != 'view' ) {
			// Hide pages if action is set and NOT action=view
			// Example: https://www.wikihow.com/index.php?title=Hang-a-Bike-in-a-Garage&action=history
			$anonVisible = false;
		} elseif ( $typeParam && $typeParam == 'revision' ) {
			// Hide URLs like diff pages
			// Example: https://www.wikihow.com/index.php?title=Hang-a-Bike-in-a-Garage&type=revision&diff=27146112&oldid=26978045
			$anonVisible = false;
		} elseif ( $oldidParam ) {
			// Hide oldid URLs
			// Example: https://www.wikihow.com/index.php?title=Hang-a-Bike-in-a-Garage&oldid=26978045
			$anonVisible = false;
		}
		return $anonVisible;
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

			$specialPageOverride = false;
			if( $wgTitle->inNamespace(NS_SPECIAL) && Misc::isMobileMode() ) {
				$spFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
				$specialPage = $spFactory->getPage( $wgTitle->getDBkey() );
				$specialPageOverride = is_null($specialPage) && $wgUser->getID() == 0;
			}

			// If it's a special page URL and that special page exists, prompt
			// the user to login on the 404 page
			$virtualLogin = false;
			if ( !$wgTitle->canExist() ) {
				$virtualLogin = true;
				if ( $wgTitle->isSpecialPage() ) {
					$spFactory = MediaWikiServices::getInstance()->getSpecialPageFactory();
					$specialPage = $spFactory->getPage( $wgTitle->getDBkey() );
					if (!$specialPage) {
						$virtualLogin = false;
					}
				}
			}

			$css = Misc::getEmbedFile( 'css', __DIR__ . '/pagepolicy.css' );
			$out->addHeadItem( 'pagepolicy-css', HTML::inlineStyle( $css ) );

			if ( $wgTitle->exists()
				|| $virtualLogin
				|| $wgTitle->inNamespaces(NS_TALK, NS_USER, NS_USER_TALK)
				|| $userPageOverride
				|| $specialPageOverride
			) {
				if ( $wgTitle->inNamespace( NS_MAIN ) && self::isVisibleAction( $out->getRequest() ) ) {
					$out->addHTML( self::render(
						GoogleAmp::isAmpMode( $out ) ?
							'article_under_review_amp' : 'article_under_review',
						[
							'review_header' => wfMessage( 'pagepolicy_review_header' )->text(),
							'review_message' => wfMessage( 'pagepolicy_review_message' )->parse(),
							'search_header' => wfMessage( 'pagepolicy_search_header' )->text(),
							'searchbox' => SearchBox::render( $out ),
							'home_message' => wfMessage( 'pagepolicy_home_message' )->parse()
						]
					) );
				} elseif ($specialPageOverride) {
					$out->setPageTitle(wfMessage( 'pagepolicy_special_header' )->text());
					$out->addHTML( self::render( 'special_not_exists',
						[
							'special_message' => wfMessage( 'Noarticletextanon' )->parse(),
							'search_header' => wfMessage( 'pagepolicy_search_header' )->text(),
							'searchbox' => SearchBox::render( $out )
						]
					) );
				} else {
					// Show login dialog
					$out->addHTML( self::render(
						Misc::doResponsive( RequestContext::getMain() ) ? 'login_mobile' : 'login_desktop',
						[
							'login_message' => wfMessage( 'pagepolicy_login_message' )->text()
						]
					) );
					$out->addHTML( '<script>document.body.className += " page-hidden";window.location.hash="#wh-dialog-login";</script>' );
				}
			} else {
				// Otherwise, show a "title doesn't exist" message
				$out->addHTML( self::render(
					Misc::doResponsive( RequestContext::getMain() ) ? 'notitle_mobile' : 'notitle_desktop',
					[
						'error_message' => wfMessage( 'nopagetitle' )->text()
					]
				) );
			}
		}
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
