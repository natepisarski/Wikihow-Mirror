<?php

namespace SocialAuth;

use Exception;

use User;

use SocialAuth\SocialAuthDao;

/**
 * Represents a WikiHow user with social login enabled
 */
abstract class SocialUser {

	/**
	 * @var SocialAuthDao
	 */
	private static $dao = null;

	/**
	 * Auto-generated primary key in the `social_auth` table
	 * @var int
	 */
	private $id;

	/**
	 * WikiHow user
	 * @var User
	 */
	private $whUser;

	/**
	 * User ID in the 3rd party platform
	 * @var string
	 */
	private $externalId;

	/**
	 * Platform used for social login (e.g. 'facebook', 'google')
	 */
	abstract protected static function getType() : string;

	/**
	 * Return an uninitialized object of the relevant SocialUser subclass, which can then
	 * be used to instantiate objects using static factory methods.
	 */
	final public static function newFactory(string $type) : SocialUser {
		$canonicalType = strtolower(trim($type));
		if ($canonicalType == 'facebook') {
			return new FacebookSocialUser();
		} elseif ($canonicalType == 'google') {
			return new GoogleSocialUser();
		} elseif ($canonicalType == 'civic') {
			return new CivicSocialUser();
		}
		else {
			throw new Exception("SocialUser type '$type' is not supported");
		}
	}

	/**
	 * Static factory method for instantiation by WikiHow user ID
	 * @return SocialUser|null
	 */
	final public static function newFromWhId(int $whId, bool $useMasterDB = false) {
		$row = $whId ? self::getDao()->getByWhId($whId, static::getType(), $useMasterDB) : null;
		if ($row) {
			return static::newFromRow($row);
		}
		return null;
	}

	/**
	 * Static factory method for instantiation by external ID (e.g. Facebook user ID)
	 * @return SocialUser|null
	 */
	final public static function newFromExternalId(string $exId) {
		$row = $exId ? self::getDao()->getByExternalId($exId, static::getType()) : null;
		if ($row) {
			return static::newFromRow($row);
		}
		return null;
	}

	/**
	 * Create a new SocialUser instance from a table row
	 */
	private static function newFromRow(\stdClass $r) : SocialUser {
		$su = new static();
		$su->id = (int) $r->sa_id;
		$su->whUser = User::newFromId($r->sa_wh_user_id);
		$su->externalId = $r->sa_external_id;
		return $su;
	}

	/**
	 * Enable social login for an existing WikiHow account and return the SocialLogin instance
	 * @return SocialUser|null
	 */
	public static function link(int $whId, string $exId) {
		if (!$whId || !$exId) {
			return null;
		}
		if (self::getDao()->insert($whId, $exId, static::getType())) {
			return static::newFromWhId($whId, true);
		}
		return null;
	}

	/**
	 * Remove the social login details associated to a WikiHow account
	 */
	public function unlink() : bool {
		$whId = $this->getWhUser()->getId();
		return self::getDao()->deleteByWhId($whId, static::getType());
	}

	/**
	 * Access a single instance of SocialAuthDao
	 */
	private static function getDao() : SocialAuthDao {
		if (!self::$dao) {
			self::$dao = new SocialAuthDao();
		}
		return self::$dao;
	}

	/* Getters */

	public function getWhUser() : User {
		return $this->whUser;
	}

	public function getExternalId() : string {
		return $this->externalId;
	}
}
