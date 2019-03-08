<?php

namespace EditMapper;

use RequestContext;
use User;
use WikiPage;

/**
 * Provide hooks to map article edits to different users
 */
abstract class EditMapper {

	protected $origUser = null; // User  The actual user whose edit is being mapped
	protected $destUser = null; // User  The destination user that will appear as the edit author

	/**
	 * Replace the current user with a different one
	 */
	public function doMapping(User &$currentUser, User &$destUser): bool {
		global $wgUser;

		if ($this->origUser || $this->destUser) { // Already mapped
			return false;
		}

		$this->origUser = $currentUser;
		$wgUser = $this->destUser = $destUser;
		$currentUser = $destUser;
		RequestContext::getMain()->setUser($destUser);

		return true;
	}

	/**
	 * Restore the original user that got replaced with doMapping()
	 */
	public function undoMapping(WikiPage $page, User &$currentUser): bool {
		global $wgUser;

		if (!$this->origUser || !$this->destUser) { // Not mapped
			return false;
		}

		$wgUser = $currentUser = $this->origUser;
		RequestContext::getMain()->setUser($this->origUser);
		$this->origUser = null;
		$this->destUser = null;

		$this->afterUnmapping($page, $currentUser);

		return true;
	}

	/**
	 * For subclasses to add custom code after the mapping, like logging
	 */
	protected function afterUnmapping(WikiPage $page, User $user) {}

	/**
	 * Whether the edit should be mapped
	 */
	abstract public function shouldMapEdit($title, $user, bool $isNew, string $comment): bool;

	/**
	 * @return The User to map the edit to, or false, in which case the mapping won't happen
	 */
	abstract public function getDestUser($title, bool $isNew);

}
