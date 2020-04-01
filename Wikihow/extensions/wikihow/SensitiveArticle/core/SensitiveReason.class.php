<?php

namespace SensitiveArticle;

class SensitiveReason
{
	protected static $dao = null; // SensitiveArticleDao

	public $id; // int
	public $name; // string
	public $enabled; // bool

	protected function __construct() {}

	/**
	 * Create an instance from the given values
	 */
	public static function newFromValues(int $id, string $name, bool $enabled): SensitiveReason
	{
		$reason = new static();
		$reason->id = $id;
		$reason->name = trim($name);
		$reason->enabled = $enabled;
		return $reason;
	}

	public static function newFromDB(int $id): SensitiveReason
	{
		$name = '';
		$enabled = false;

		$res = static::getDao()->getReason($id);
		foreach ($res as $row) {
			$name = $row->sr_name;
			$enabled = (int)$row->sr_enabled;
		}

		return static::newFromValues($id, $name, $enabled);
	}

	/**
	 * Get all items in the database
	 *
	 * @return SensitiveReason[]
	 */
	public static function getAll(): array
	{
		$rows = static::getDao()->getAllReasons();
		$reasons = [];
		foreach ($rows as $r) {
			$reasons[] = static::newFromValues($r->sr_id, $r->sr_name, $r->sr_enabled);
		}
		return $reasons;
	}

	public function isValid(): bool
	{
		return !empty(trim($this->name));
	}

	/**
	 * Store the object in the database
	 */
	public function save(): bool
	{
		if (!$this->isValid()) {
			return false;
		}

		if ($this->id > 0) {
			$res = static::getDao()->updateReason($this);
		} else {
			$this->id = static::getDao()->getNewReasonId();
			$res = static::getDao()->insertReason($this);
		}
		return $res;
	}

	public function delete(): bool
	{
		$res = static::getDao()->deleteReason($this);
		Hooks::run( "SensitiveReasonDeleted" , [$this->id]);
		return $res;
	}

	/**
	 * Access a single instance of SensitiveArticleDao
	 */
	protected static function getDao(): SensitiveArticleDao
	{
		if (!static::$dao) {
			static::$dao = new SensitiveArticleDao();
		}
		return static::$dao;
	}

}
