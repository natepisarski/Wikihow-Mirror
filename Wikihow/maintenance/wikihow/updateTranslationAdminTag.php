<?php
/**
 * This maintenance script is run from the UpdateTranslationAdminTagJob as follows:
 * /opt/wikihow/scripts/whrun --lang=intl -- updateTranslationAdminTag.php --tag=tag_name
 */

require_once __DIR__ . '/../Maintenance.php';

/**
 * Clears and repopulates the translated articles of a tag on EN for an INTL tag. This maintenance
 * job is used for "translation admin tags".
 */
class UpdateTranslationAdminTag extends Maintenance {
	public function __construct() {
		parent::__construct();
        $this->mDescription = "Update list of translated articles for a translation tag in a language";
		$this->addOption( 'tag', 'tag name of the translation admin tag', false, true, 't' );
		$this->addOption( 'all', 'specifying "all" which means to refresh all translation tags', false, false, 'a' );
	}

	public function execute() {
		$langCode = RequestContext::getMain()->getLanguage()->getCode();
		if ($langCode == 'en') {
			$this->output( __CLASS__ . ": won't run on English\n" );
			return;
		}

		$tagOpt = $this->getOption( 'tag' );
		$allOpt = $this->getOption( 'all' );
		if (!$tagOpt && !$allOpt || $tagOpt && $allOpt) {
			$this->output( __CLASS__ . ": either --tag=my_tag or --all must be specified, but not both\n" );
			return;
		}

		if ($allOpt) {
			// fetch all translation tags from db
			$targets = ArticleTag::listEnglishTranslationTags();
		} else {
			$targets = [ $tagOpt ];
		}

		foreach ($targets as $tag) {
			ArticleTag::rewriteTranslationTag($tag);
		}
		$outputTag = $tagOpt ? "tag=$tagOpt" : "ALL (count: " . count($targets) . ")";
		$this->output( __CLASS__ . ": done $outputTag for language '$langCode'\n" );
	}
}

$maintClass = UpdateTranslationAdminTag::class;
require_once RUN_MAINTENANCE_IF_MAIN;
