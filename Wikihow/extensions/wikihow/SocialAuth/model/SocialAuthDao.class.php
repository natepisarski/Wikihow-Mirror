<?php

namespace SocialAuth;

/**
 * Data Acces Object for the `social_auth` table
 */
class SocialAuthDao {

	const TABLE = WH_DATABASE_NAME_SHARED . '.social_auth';
	const FIELDS = ['sa_id','sa_wh_user_id','sa_external_id','sa_type'];

	/**
	 * Fetch a single row or return false if not found
	 *
	 * @param  int    $whId         WikiHow user ID
	 * @param  string $type         Platform used for social login (e.g. "facebook")
	 * @param  bool   $useMasterDB  To avoid DB replication lag issues on SocialUser::link()
	 * @return stdClass|false       An object with the properties: SocialAuthDao.FIELDS
	 */
	public function getByWhId(int $whId, string $type, bool $useMasterDB = false) {
		if (empty($whId) || empty($type)) {
			return false;
		}
		$db = $useMasterDB ? wfGetDB(DB_MASTER) : wfGetDB(DB_REPLICA);
		return $db->selectRow(
			static::TABLE,
			static::FIELDS,
			['sa_wh_user_id' => $whId, 'sa_type' => $type]
		);
	}

	/**
	 * Fetch a single row or return false if not found
	 *
	 * @param  string $exId    User ID in the 3rd party platform
	 * @param  string $type    Platform used for social login (e.g. "facebook")
	 * @return stdClass|false  An object with the properties: SocialAuthDao.FIELDS
	 */
	public function getByExternalId(string $exId, string $type) {
		if (empty($exId) || empty($type)) {
			return false;
		}
		return wfGetDB(DB_REPLICA)->selectRow(
			static::TABLE,
			static::FIELDS,
			['sa_external_id' => $exId, 'sa_type' => $type, 'sa_wh_user_id != 0']
		);
		// Note: 'sa_wh_user_id != 0' is only necessary until release. After "orphaned"
		// accounts have beeen removed from db1, it will be safe to remove that condition.
	}

	/**
	 * Delete a row
	 * @param  int    $whId  WikiHow user ID
	 * @param  string $type  Platform used for social login (e.g. "facebook")
	 * @return bool          The result of DatabaseBase->delete()
	 */
	public function deleteByWhId(int $whId, string $type) {
		if (empty($whId) || empty($type)) {
			return false;
		}
		return wfGetDB(DB_MASTER)->delete(
			static::TABLE,
			['sa_wh_user_id' => $whId, 'sa_type' => $type]
		);
	}

	/**
	 * Insert a row
	 *
	 * @param  int    $whId   WikiHow user ID
	 * @param  string $exId   User ID in the 3rd party platform
	 * @param  string $type   Platform used for social login (e.g. "facebook")
	 * @return bool           The result of DatabaseBase->insert()
	 */
	public function insert(int $whId, string $exId, string $type) {
		if (empty($whId) || empty($exId) || empty($type)) {
			return false;
		}
		return wfGetDB(DB_MASTER)->insert(static::TABLE, [
			'sa_wh_user_id' => $whId,
			'sa_external_id' => $exId,
			'sa_type' => $type
		]);
	}

}
