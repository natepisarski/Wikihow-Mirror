<?php

namespace TechArticle;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

use OutputPage;
use RequestContext;
use WikiPage;

use Misc;
use RobotPolicy;
use VerifyData;

/**
 * Add the "Community Tested" stamp to the upper right corner of the article intro box
 */
class TechArticleStampHooks {

	public static function setBylineInfo( &$verifiers, $pageId) {
		if (!class_exists('TechArticle\TechArticle')) return true; // EN-only
		$techArticle = TechArticle::newFromDB($pageId);
		if ($techArticle->isFullyTested()) {
			$verifiers[\SocialProofStats::VERIFIER_TYPE_TECH] = true;
		}

		return true;
	}

}
