<?php

namespace CirrusSearch;

use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Profile\SearchProfileServiceFactory;
use Config;
use MediaWiki\MediaWikiServices;
use RequestContext;

/**
 * Configuration class encapsulating Searcher environment.
 * This config class can import settings from the environment globals,
 * or from specific wiki configuration.
 */
class SearchConfig implements \Config {
	// Constants for referring to various config values. Helps prevent fat-fingers
	const INDEX_BASE_NAME = 'CirrusSearchIndexBaseName';
	const PREFIX_IDS = 'CirrusSearchPrefixIds';
	const CIRRUS_VAR_PREFIX = 'wgCirrus';

	// Magic word to tell the SearchConfig to translate INDEX_BASE_NAME into wfWikiID()
	const WIKI_ID_MAGIC_WORD = '__wikiid__';

	/** @static string[] non cirrus vars to load when loading external wiki config */
	private static $nonCirrusVars = [
		'wgLanguageCode',
		'wgContentNamespaces',
		'wgNamespacesToBeSearchedDefault',
	];

	/**
	 * @var SearchConfig Configuration of host wiki.
	 */
	private $hostConfig;

	/**
	 * Override settings
	 * @var Config
	 */
	private $source;

	/**
	 * Wiki id or null for current wiki
	 * @var string|null
	 */
	private $wikiId;

	/**
	 * @var Assignment\ClusterAssignment|null
	 */
	private $clusters;

	/**
	 * @var SearchProfileService|null
	 */
	private $profileService;

	/**
	 * Create new search config for the current wiki.
	 */
	public function __construct() {
		$this->source = new \GlobalVarConfig();
		$this->wikiId = wfWikiID();
		// The only ability to mutate SearchConfig is via a protected method, setSource.
		// As long as we have an instance of SearchConfig it must then be the hostConfig.
		$this->hostConfig = static::class === self::class ? $this : new SearchConfig();
	}

	/**
	 * This must be delayed until after construction is complete. Before then
	 * subclasses could change out the configuration we see.
	 *
	 * @return Assignment\ClusterAssignment
	 */
	private function createClusterAssignment(): Assignment\ClusterAssignment {
		// Configuring CirrusSearchServers enables "easy mode" which assumes
		// everything happens inside a single elasticsearch cluster.
		if ( $this->has( 'CirrusSearchServers' ) ) {
			return new Assignment\ConstantAssignment(
				$this->get( 'CirrusSearchServers' ) );
		} else {
			return new Assignment\MultiClusterAssignment( $this );
		}
	}

	public function getClusterAssignment(): Assignment\ClusterAssignment {
		if ( $this->clusters === null ) {
			$this->clusters = $this->createClusterAssignment();
		}
		return $this->clusters;
	}

	/**
	 * Reset any cached state so testing can ensures changes to global state
	 * are reflected here. Only public for use from phpunit.
	 */
	public function clearCachesForTesting() {
		$this->profileService = null;
		$this->clusters = null;
	}

	/**
	 * @return bool true if this config was built for this wiki.
	 */
	public function isLocalWiki() {
		// FIXME: this test is somewhat obscure (very indirect to say the least)
		// problem is that testing $this->wikiId === wfWikiId() would not work
		// properly during unit tests.
		return $this->source instanceof \GlobalVarConfig;
	}

	/**
	 * @return SearchConfig Configuration of the host wiki.
	 */
	public function getHostWikiConfig(): SearchConfig {
		return $this->hostConfig;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has( $name ) {
		return $this->source->has( $name );
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get( $name ) {
		if ( !$this->source->has( $name ) ) {
			return null;
		}
		$value = $this->source->get( $name );
		if ( $name === self::INDEX_BASE_NAME && $value === self::WIKI_ID_MAGIC_WORD ) {
			return $this->getWikiId();
		}
		return $value;
	}

	/**
	 * Produce new configuration from globals
	 * @return SearchConfig
	 */
	public static function newFromGlobals() {
		return new self();
	}

	/**
	 * Return configured Wiki ID
	 * @return string
	 */
	public function getWikiId() {
		return $this->wikiId;
	}

	/**
	 * @todo
	 * The indices have to be rebuilt with new id's and we have to know when
	 * generating queries if new style id's are being used, or old style. It
	 * could plausibly be done with the metastore index, but that seems like
	 * overkill because the knowledge is only necessary during transition, and
	 * not post-transition.  Additionally this function would then need to know
	 * the name of the index being queried, and that isn't always known when
	 * building.
	 *
	 * @param string|int $pageId
	 * @return string
	 */
	public function makeId( $pageId ) {
		$prefix = $this->get( self::PREFIX_IDS )
			? $this->getWikiId()
			: null;

		if ( $prefix === null ) {
			return (string)$pageId;
		} else {
			return "{$prefix}|{$pageId}";
		}
	}

	/**
	 * Convert an elasticsearch document id back into a mediawiki page id.
	 *
	 * @param string $docId Elasticsearch document id
	 * @return int Related mediawiki page id
	 * @throws \Exception
	 */
	public function makePageId( $docId ) {
		if ( !$this->get( self::PREFIX_IDS ) ) {
			return (int)$docId;
		}

		$pieces = explode( '|', $docId );
		switch ( count( $pieces ) ) {
		case 2:
			return (int)$pieces[1];
		case 1:
			// Broken doc id...assume somehow this didn't get prefixed.
			// Attempt to continue on...but maybe should throw exception
			// instead?
			return (int)$docId;
		default:
			throw new \Exception( "Invalid document id: $docId" );
		}
	}

	/**
	 * Get user's language
	 * @return string User's language code
	 */
	public function getUserLanguage() {
		// I suppose using $wgLang would've been more evil than this, but
		// only marginally so. Find some real context to use here.
		return RequestContext::getMain()->getLanguage()->getCode();
	}

	/**
	 * Get chain of elements from config array
	 * @suppress PhanCommentParamWithoutRealParam
	 * @param string $configName
	 * @param string $path,... list of path elements
	 * @return mixed Returns value or null if not present
	 */
	public function getElement( $configName ) {
		if ( !$this->has( $configName ) ) {
			return null;
		}
		$data = $this->get( $configName );
		$path = func_get_args();
		array_shift( $path );
		foreach ( $path as $el ) {
			if ( !isset( $data[$el] ) ) {
				return null;
			}
			$data = $data[$el];
		}
		return $data;
	}

	/**
	 * For Unit tests
	 * @param Config $source Config override source
	 */
	protected function setSource( Config $source ) {
		$this->source = $source;
		$this->clusters = null;
	}

	/**
	 * Check if a cluster is declared "writable".
	 * NOTE: a cluster is considered writable even if one of its index is
	 * frozen.
	 * Before sending any writes in this cluster, the forzen index status
	 * must be checked fr the  target index.
	 * @see DataSender::isAvailableForWrites()
	 *
	 * @param string $cluster
	 * @return bool
	 */
	public function canWriteToCluster( $cluster ) {
		return in_array( $cluster, $this->getClusterAssignment()->getWritableClusters() );
	}

	/**
	 * for unit tests purpose only
	 * @return string[] list of "non-cirrus" var names
	 */
	public static function getNonCirrusConfigVarNames() {
		return self::$nonCirrusVars;
	}

	/**
	 * @return true if cross project (same language) is enabled
	 */
	public function isCrossProjectSearchEnabled() {
		if ( $this->get( 'CirrusSearchEnableCrossProjectSearch' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @return true if cross language (same project) is enabled
	 */
	public function isCrossLanguageSearchEnabled() {
		if ( $this->get( 'CirrusSearchEnableAltLanguage' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Load the SearchProfileService suited for this SearchConfig.
	 * The service is initialized thanks to SearchProfileServiceFactory
	 * that will load CirrusSearch profiles and additional extension profiles
	 *
	 * <b>NOTE:</b> extension profiles are not loaded if this config is built
	 * for a sister wiki.
	 *
	 * @return SearchProfileService
	 * @see SearchProfileService
	 * @see SearchProfileServiceFactory
	 */
	public function getProfileService() {
		if ( $this->profileService === null ) {
			/** @var SearchProfileServiceFactory $factory */
			$factory = MediaWikiServices::getInstance()
				->getService( SearchProfileServiceFactory::SERVICE_NAME );
			$this->profileService = $factory->loadService( $this );
		}
		return $this->profileService;
	}
}
