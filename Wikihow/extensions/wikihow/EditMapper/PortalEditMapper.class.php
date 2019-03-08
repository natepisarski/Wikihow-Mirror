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
	public function shouldMapEdit($title, $user, bool $isNew, string $comment): bool {
		return $title && $title->inNamespaces(NS_MAIN, NS_SUMMARY)
			&& $user->hasGroup('editor_team')
			&& !$user->hasGroup('staff')
			&& !in_array($user->getName(), ['WRM', 'Seymour Edits']);
	}

	public function getDestUser($title, bool $isNew) {
		$isSummaryNS = $title && $title->inNamespace(NS_SUMMARY);
		$destUsername = $isNew && !$isSummaryNS ? 'WRM' : 'Seymour Edits';
		return User::newFromName($destUsername);
	}

}
