<?php

namespace CirrusSearch;

use BagOStuff;

/**
 * Factory class used to create InterwikiResolver
 */
class InterwikiResolverFactory {
	/**
	 * @const string service name used in MediaWikiServices
	 */
	const SERVICE = 'CirrusSearchInterwikiResolverFactory';

	/**
	 * @return InterwikiResolverFactory
	 */
	public static function newFactory() {
		return new InterwikiResolverFactory();
	}

	/**
	 * Based on config variables available in $config
	 * returns the approriate the InterwikiResolver
	 * implementation.
	 * Fallback to EmptyInterwikiResolver.
	 *
	 * @param SearchConfig $config
	 * @param \MultiHttpClient|null $client http client to fetch cirrus config
	 * @param BagOStuff|null $cache Cache object for caching repeated requests
	 * @return InterwikiResolver
	 * @see CirrusSearchInterwikiResolverFactory::accepts()
	 * @see SiteMatrixInterwikiResolver::accepts()
	 */
	public function getResolver( SearchConfig $config, \MultiHttpClient $client = null, BagOStuff $cache = null ) {
		if ( CirrusConfigInterwikiResolver::accepts( $config ) ) {
			return new CirrusConfigInterwikiResolver( $config, $client, $cache );
		}
		if ( SiteMatrixInterwikiResolver::accepts( $config ) ) {
			return new SiteMatrixInterwikiResolver( $config, $client, $cache );
		}
		return new EmptyInterwikiResolver();
	}
}
