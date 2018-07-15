<?php

namespace EditMapper;

use User;

/**
 * Map article edits made by Content Portal editors to 'WRM', 'Seymour Edits', etc
 */
class PortalEditMapper extends EditMapper {

	/**
	 * True if the user has the "Editor" role in Content Portal
	 */
	public function shouldMapEdit($title, $user, bool $isNew): bool {
		return $title && $title->inNamespaces(NS_MAIN, NS_SUMMARY)
			&& $user->hasGroup('editor_team')
			&& !$user->hasGroup('staff')
			&& !in_array($user->getName(), ['WRM', 'Seymour Edits']);
	}

	public function getDestUser(bool $isNew, bool $isSummaryPage) {
		$destUsername = $isNew && !$isSummaryPage ? 'WRM' : 'Seymour Edits';
		return User::newFromName($destUsername);
	}

}
