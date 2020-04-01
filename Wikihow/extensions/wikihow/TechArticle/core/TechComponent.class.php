<?php

namespace TechArticle;

/**
 * Instances represent rows in the `tech_product` table
 */
class TechProduct extends TechComponent {
	const TABLE = 'tech_product';
	const FIELDS = [ 'id' => 'tpr_id', 'name' => 'tpr_name', 'enabled' => 'tpr_enabled' ];
}

/**
 * Instances represent rows in the `tech_platform` table
 */
class TechPlatform extends TechComponent {
	const TABLE = 'tech_platform';
	const PLATFORM_ANDROID = 1;
	const PLATFORM_IOS = 2;
	const PLATFORM_MAC = 3;
	const PLATFORM_WINDOWS = 4;
	const PLATFORM_OTHER = 5;
	const FIELDS = [ 'id' => 'tpl_id', 'name' => 'tpl_name', 'enabled' => 'tpl_enabled' ];

}

/**
 * Base class for TechProduct and TechPlatform
 */
abstract class TechComponent {

	const TABLE = null;
	const FIELDS = null;

	protected static $dao = null; // TechComponentDao

	public $id; // int
	public $name; // string
	public $enabled; // bool

	protected function __construct() {}

	/**
	 * Create an instance from the given values
	 */
	public static function newFromValues(int $id, string $name, bool $enabled): TechComponent {
		$techComponent = new static();
		$techComponent->id = $id;
		$techComponent->name = $name;
		$techComponent->enabled = $enabled;
		return $techComponent;
	}

    public function newFromID( $id ) {
		$row = static::getDao()->getById( static::TABLE, static::FIELDS, $id );
		$techComponent = new static();
		$techComponent->id = $row->id;
		$techComponent->name = $row->name;
		$techComponent->enabled = $row->enabled;
		return $techComponent;
    }

	/**
	 * Get all items in the database
	 *
	 * @return TechComponent[]
	 */
	public static function getAll(): array {
		$rows = static::getDao()->getAllComponents(static::TABLE, static::FIELDS);
		$techComponents = [];
		foreach ($rows as $r) {
			$techComponents[] = static::newFromValues($r->id, $r->name, $r->enabled);
		}
		return $techComponents;
	}

	public function isValid(): bool {
		return !empty(trim($this->name));
	}

	/**
	 * Store object in the database
	 */
	public function save(): bool {
		if (!$this->isValid()) {
			return false;
		}

		return $this->id > 0
			? static::getDao()->updateComponent($this)
			: static::getDao()->upsertComponent($this);
	}

	/**
	 * Access a single instance of TechComponentDao
	 */
	protected static function getDao(): TechComponentDao {
		if (!static::$dao) {
			static::$dao = new TechComponentDao();
		}
		return static::$dao;
	}

}
