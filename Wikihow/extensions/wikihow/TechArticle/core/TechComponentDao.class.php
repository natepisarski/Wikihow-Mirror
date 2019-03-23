<?php

namespace TechArticle;

use Iterator;
use EmptyIterator;

use ResultWrapper;

use Misc;

/**
 * Data Access Object for the `tech_product` and `tech_platform` tables
 */
class TechComponentDao {

	private static function buildQueryValues(TechComponent $tc): array {
		return [
			$tc::FIELDS['name'] => $tc->name,
			$tc::FIELDS['enabled'] => (int) $tc->enabled
		];
	}

	/**
	 * Fetch all elements in the table
	 */
	public function getAllComponents(string $table, array $fields): Iterator {
		$options = [ 'ORDER BY' => [$fields['enabled'] . ' DESC', $fields['name']] ];
		$res = wfGetDB(DB_REPLICA)->select($table, $fields, [], __METHOD__, $options);
		return $res ?? new EmptyIterator();
	}

	/**
	 * Get a component by ID
	 * @return ResultWrapper|bool
	 */
	public function getByID( string $table, array $fields, int $id ) {
        $cond = [$fields['id'] => $id];
		return wfGetDB(DB_REPLICA)->selectRow( $table, $fields, $cond, __METHOD__ );
	}

	public function upsertComponent(TechComponent $tc): bool {
		$values = static::buildQueryValues($tc);
		$set = [ $tc::FIELDS['enabled'] => (int) $tc->enabled ];
		return wfGetDB(DB_MASTER)->upsert($tc::TABLE, $values, [], $set);
	}

	public function updateComponent(TechComponent $tc): bool {
		$values = static::buildQueryValues($tc);
		$conds = [ $tc::FIELDS['id'] => $tc->id ];
		try {
			return wfGetDB(DB_MASTER)->update($tc::TABLE, $values, $conds);
		} catch (\DBQueryError $e) {
			if ($e->errno == 1062) { // "Duplicate entry '%s' for key %d"
				return false;
			}
			throw $e;
		}
	}

}
