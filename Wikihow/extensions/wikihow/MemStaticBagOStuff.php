<?php

if ( !defined('MEDIAWIKI') ) die();

/**
 * Object caching using memcached and local static variables.
 *
 * Our engineers should decide when to use memcache directly as opposed to this
 * caching facility. This cache type is useful to avoid pulling the same data
 * from memcache -- which is often a network call away -- within the same web
 * request context. This facility naturally uses more more memory in the php
 * request, so it shouldn't be used to cache larger sizes or amounts of data.
 *
 * Using this cache type helps avoid the use of adding your own static variable
 * caching layer on top of memcache, which can make the code less readable and
 * more buggy.
 *
 * This class can be used as follows, as a drop-in replacement for $wgMemc
 * or HashBagOStuff cache objects.
 *
 * $cache = wgGetCache(CACHE_MEMSTATIC);
 * $key = wfMemcKey('mykey', $id);
 * $res = $cache->get($key);
 * if ($res === false) {
 *     $res = ... get value from database or wherever ...
 *     $cache->set($key, $res);
 * }
 *
 */
class MemStaticBagOStuff extends BagOStuff {

	// good to have an upper bound on how much memory we can use per request
	// WARNING: this upper bound is not properly checked in $value is not a string
	const REQUEST_MAX_SIZE = 1000000;

	private $memc, $hash;

	public function __construct() {
		global $wgMemc;
		$this->memc = $wgMemc;
		$this->hash = wfGetCache( 'hash' );
	}

	// NOTE: there is a known bug where we do not know the expiry time of objects
	// from memcache, so their proper expiry time is not set in the HashBag after
	// getting the object from memcache.
	public function get( $key, &$casToken = null ) {
		$res = $this->hash->get( $key, $casToken );
		if ($res !== false) {
			return $res;
		} else {
			$res = $this->memc->get( $key, $casToken );
			self::assertValueSize( $res );
			$this->hash->set( $key, $res );
			return $res;
		}
	}

	// We implement a maximum size for values that can be stored in this
	// cache facility.
	public static function getMaxSize() {
		global $wgCommandLineMode;
		if ( $wgCommandLineMode ) {
			return PHP_INT_MAX;
		} else {
			return self::REQUEST_MAX_SIZE;
		}
	}

	// Helper method for asserting size of the value stored
	private static function assertValueSize( $value ) {
		$max = self::getMaxSize();
		// we should have a better way to do this. calling serialize would be
		// unnecessarily expensive if we threw away the result.
		if ( is_string( $value ) && strlen( $value ) > $max
			|| is_array( $value ) && count( $value ) > $max
		) {
			throw new MWException( 'Internal error: ' . __CLASS__ .
				' is not intended to store larger objects because it uses' .
				' static variables.' );
		}
	}

	public function set( $key, $value, $exptime = 0 ) {
		self::assertValueSize( $value );
		$res_memc = $this->memc->set( $key, $value, $exptime );
		$res_hash = $this->hash->set( $key, $value, $exptime );
		return $res_memc && $res_hash;
	}

	// This abstract method must be implemented, but we don't want to
	// support this operation on this relatively simple cache.
	public function cas( $casToken, $key, $value, $exptime = 0 ) {
		throw new MWException( 'Internal error: cas() method is not available ' .
			'in class: ' . __CLASS__ );

		return false;
	}

	public function delete( $key, $time = 0 ) {
		$res_memc = $this->memc->delete( $key, $time );
		$res_hash = $this->hash->delete( $key, $time );
		return $res_memc && $res_hash;
	}
}
