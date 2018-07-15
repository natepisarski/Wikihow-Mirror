<?php

namespace SensitiveArticle;

class SensitiveReason
{
	protected static $dao = null; // SensitiveArticleDao

	public $id; // int
	public $name; // string
	public $internal_name; // string
	public $question; // string
	public $description; // string
	public $enabled; // bool

	protected function __construct() {}

	/**
	 * Create an instance from the given values
	 */
	public static function newFromValues(int $id, string $name, string $internal_name,
		string $question, string $description, bool $enabled): SensitiveReason
	{
		$reason = new static();
		$reason->id = $id;
		$reason->name = trim($name);
		$reason->internal_name = trim($internal_name);
		$reason->question = trim($question);
		$reason->description = trim($description);
		$reason->enabled = $enabled;
		return $reason;
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
			$reasons[] = static::newFromValues(
				$r->sr_id,
				$r->sr_name,
				$r->sr_internal_name,
				$r->sr_question,
				$r->sr_description,
				$r->sr_enabled
			);
		}
		return $reasons;
	}

	/**
	 * Get one specific reason
	 *
	 * @return SensitiveReason[]
	 */
	public static function getReason(int $reason_id): SensitiveReason
	{
		$rows = static::getDao()->getReason($reason_id);
		foreach ($rows as $r) {
			$reason = static::newFromValues(
				$r->sr_id,
				$r->sr_name,
				$r->sr_internal_name,
				$r->sr_question,
				$r->sr_description,
				$r->sr_enabled
			);
		}
		return $reason;
	}

	public function isValid(): bool
	{
		return !empty(trim($this->name)) && !empty(trim($this->internal_name));
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
