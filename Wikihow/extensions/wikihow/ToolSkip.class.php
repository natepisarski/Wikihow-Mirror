<?php

if ( !defined('MEDIAWIKI') ) die();

/**
 * A utility class of static functions that produce html snippets
 */
class ToolSkip {

	var $skippedKey = null;
	var $inUseKey = null;
	var $toolTable = null;
	var $checkoutTimeField = null;
	var $checkoutUserField = null;
	var $checkoutItemField = null;

	const DEFAULT_VALUE = 'default';
	const ONE_WEEK = 604800;

	public function __construct($toolName = self::DEFAULT_VALUE,
		$toolTable = self::DEFAULT_VALUE,
		$checkoutTimeField = self::DEFAULT_VALUE,
		$checkoutUserField = self::DEFAULT_VALUE,
		$checkoutItemField = self::DEFAULT_VALUE
	) {
		$user = RequestContext::getMain()->getUser();

		if ($user->getId() == 0) {
			$id = WikihowUser::getVisitorId();
		} else {
			$id = $user->getID();
		}
		$this->skippedKey = $toolName . "_" . $id . "_skipped";
		$this->inUseKey = $toolName . "_inUse";
		$this->toolTable = $toolTable;
		$this->checkoutTimeField = $checkoutTimeField;
		$this->checkoutUserField = $checkoutUserField;
		$this->checkoutItemField = $checkoutItemField;
	}

	public function skipItem($itemId = 0) {
		global $wgMemc;

		$key = $this->skippedKey;
		$val = $wgMemc->get($key);

		if ($val) {
			$val[] = $itemId;
		} else {
			$val = array($itemId);
		}
		$wgMemc->set($key, $val, ToolSkip::ONE_WEEK);
	}

	public function useItem($itemId = 0) {
		$user = RequestContext::getMain()->getUser();

		$dbw = wfGetDB(DB_MASTER);
		if ($this->checkoutTimeField != ToolSkip::DEFAULT_VALUE
			&& $this->checkoutUserField != ToolSkip::DEFAULT_VALUE
			&& $this->toolTable != ToolSkip::DEFAULT_VALUE
		) {
			$dbw->update($this->toolTable,
				array($this->checkoutTimeField => wfTimestampNow(),
					$this->checkoutUserField => $user->getID()),
				array($this->checkoutItemField => $itemId),
				__METHOD__);
		}
	}

	public function unUseItem($itemId = 0) {
		$dbw = wfGetDB(DB_MASTER);
		if ($this->checkoutTimeField != ToolSkip::DEFAULT_VALUE && $this->checkoutUserField != ToolSkip::DEFAULT_VALUE && $this->toolTable != ToolSkip::DEFAULT_VALUE) {
			$dbw->update($this->toolTable, array($this->checkoutTimeField => "", $this->checkoutUserField => ""), array($this->checkoutItemField => $itemId));
		}
	}

	public function getSkipped() {
		global $wgMemc;

		$key = $this->skippedKey;
		$val = $wgMemc->get($key);

		return $val;
	}

	public function clearSkipCache(){
		global $wgMemc;
		$wgMemc->delete($this->skippedKey);
	}

}
