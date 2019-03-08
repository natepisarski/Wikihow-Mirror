<?php

namespace EditMapper;

use TranslateEditor;
use User;
use TranslateSummariesTool;

/**
 * Map article/summary page edits made with the Special:TranslateSummaries tool
 */
class TranslateSummariesEditMapper extends EditMapper {

	public function shouldMapEdit($title, $user, bool $isNew, string $comment): bool {
		if (!$title) {
			return false;
		}

		// Map the 1st edit to the Summary page
		$mapA = $isNew
			&& $title->inNamespace(NS_SUMMARY)
			&& strcmp($comment, wfMessage('summary_edit_log')->text()) == 0
			&& TranslateSummariesTool::allowedUser();

		// Map the edit that adds the summary to the article page
		$mapB = $title->inNamespace(NS_MAIN)
			&& strcmp($comment, wfMessage('summary_add_log')->text()) == 0
			&& TranslateSummariesTool::allowedUser();

		return $mapA || $mapB;
	}

	public function getDestUser($title, bool $isNew) {
		return User::newFromName( wfMessage('translator_account')->text() );
	}

}
