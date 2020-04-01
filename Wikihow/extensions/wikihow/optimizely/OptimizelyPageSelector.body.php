<?php

/**
 * Generates Optmizely JavaScript tags for pages.
 */
class OptimizelyPageSelector {

	/* Static Properties */

	// @property Black-list of specific articles due to weird indexing issue
	//   - per Elizabeth and Reuben, Sept 2016
	// @see https://dl.dropboxusercontent.com/s/lelzq6j1zfzqyb9/2016-09-14%20at%202.26%20PM%202x.png?dl=0
	protected static $titleBlackList = [ 'Gain Weight' ];

	protected static $specialPageWhiteList = [ 'Charity', 'DocViewer' ];

	protected static $altDomainWhitelist = [ ];
	protected static $altDomainSnippetIds = [ 'wikihow.pet' => '10427340836', 'wikihow.mom' => '10370078292'];

	/* Static Methods */

	/**
	 * Get the JavaScript tags needed to included Optimizely on the page.
	 *
	 * @param RequestContext $context Context to get tag for
	 * @param string $location Location to get tag for, either 'head' or 'body', may be empty if
	 *     there's no tag for that context or location
	 */
	public static function getOptimizelyTag( $context, $location ) {
		global $wgIsDevServer, $wgIsAnswersDomain;

		$altDomain = $isOptiDomain = false;
		if ( class_exists( 'AlternateDomain' )) {
			$altDomain = AlternateDomain::getAlternateDomainForCurrentPage();
		}

		if ($altDomain !== false) {
			$isOptiDomain = in_array($altDomain, self::$altDomainWhitelist);
		}

		$tag = '';
		if (
			// Locations ('head' and 'body') are mutually exclusive
			$location === static::getOptimizelyTagLocation( $context ) &&
			// Optimizely must be enabled for this user
			static::isUserEnabled( $context->getUser() ) &&
			// Optimizely must be enabled for this article
			static::isArticleEnabled( $context->getTitle() )
		) {
			if ( $wgIsAnswersDomain ) {
				// Answers
				$tag = Html::rawElement( 'script', [
					'async', 'src' => 'https://cdn.optimizely.com/js/8223773184.js'
				] );
			} elseif ( $isOptiDomain ) {
				// wikiHow.somethingelse
				$tag = Html::rawElement( 'script', [
					'async', 'src' => 'https://cdn.optimizely.com/js/' . self::$altDomainSnippetIds[$altDomain] . '.js'
				] );
			} else {
				if ( $location === 'head' ) {
					// Header project
					$tag = Html::rawElement( 'script', [
						'src' => 'https://cdn.optimizely.com/js/9727930021.js'
					] );
				} elseif ( $location === 'body' ) {
					// General production
					$tag = Html::rawElement( 'script', [
						'async', 'src' => 'https://cdn.optimizely.com/js/526710254.js'
					] );
				}
			}
		}

		$append = function ( $with ) use ( $tag ) {
			$tag .= $with;
		};

		$replace = function ( $with ) use ( $tag ) {
			$tag = $with;
		};

		// Comment out tag on dev servers
		if ( $tag && $wgIsDevServer ) {
			$tag = '<!-- DEV ' . $tag . '-->';
		}

		// Allow extensions to override or append tag
		Hooks::run( 'OptimizelyGetTag', array( $context, $location, &$tag ) );

		return $tag;
	}

	/**
	 * Check if Optimizely should be placed in the head
	 *
	 * @param Title $title Title to check
	 * @return string Tag location, either 'head' or 'body'
	 */
	public static function getOptimizelyTagLocation( $context ) {
		$title = $context->getTitle();

		return $title &&
		(ArticleTagList::hasTag( 'opti_header', $title->getArticleID() ) ) ?
			'head' : 'body';
	}

	/**
	 * Check if Optimizely should be enabled for a specified user.
	 *
	 * @param User $user User to check
	 * @return boolean Optimizely is enabled for this user
	 */
	public static function isUserEnabled( $user ) {
		// Enable Optimizely for all users
		return true;
	}

	/**
	 * Check if Optimizely should be enabled for a specified title.
	 *
	 * @param Title $title Title to check
	 * @return boolean Optimizely is enabled for this title
	 */
	public static function isArticleEnabled( $title ) {
		global $wgLanguageCode;

		$activationTag = 'opti_' . ( Misc::isMobileMode() ? 'mobile' : 'desktop' );
		$hasActivationTag = ArticleTagList::hasTag( $activationTag, $title->getArticleId() );
		list( $name, $subpage ) = SpecialPageFactory::resolveAlias( $title->getDBkey() );

		// Various conditions to enable optimizely
		return (
			//could be on whitelist domains
			(class_exists( 'AlternateDomain' ) && in_array(AlternateDomain::getAlternateDomainForCurrentPage(), self::$altDomainWhitelist)) ||
			// Isn't on any other alternate domain
			( !class_exists( 'AlternateDomain' ) || !AlternateDomain::onAlternateDomain() ) &&
			// Is in English
			$wgLanguageCode === 'en' &&
			(
				// Special pages
				(
					// Is a special page
					$title->inNamespace( NS_SPECIAL ) &&
					// In whitelist
					in_array( $name, static::$specialPageWhiteList )
				) ||
				// Articles
				(
					// Has a title
					$title &&
					// Isn't black-listed
					!in_array( $title->getText(), static::$titleBlackList ) &&
					// Has an activation tag for the current target (desktop or mobile)
					$hasActivationTag
				)
			)
		);
	}

	public static function isArticleEnabledOptimize( $title ) {
		global $wgLanguageCode;

		if ($wgLanguageCode == "en" &&
			(!class_exists( 'AlternateDomain' ) || !AlternateDomain::onAlternateDomain()) &&
			!Misc::isMobileMode() &&
			ArticleTagList::hasTag( 'optimize_list', $title->getArticleId() )
			) {
			return true;
		} else {
			return false;
		}
	}

}
