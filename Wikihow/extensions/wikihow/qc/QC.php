<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'QG',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Provides a way of reviewing a set of edits separate from RC Patrol, such as removal of stub templates.',
);

$dir = __DIR__ . '/';

$wgSpecialPages['QG'] = 'QG';
$wgSpecialPages['QC'] = 'QG';
$wgAutoloadClasses['QG'] = $dir . 'QC.body.php';

$wgAutoloadClasses['QCRuleTemplateChange'] = __DIR__ . '/QC.body.php';

# Internationalisation file
$wgExtensionMessagesFiles['QG'] = $dir . 'QC.i18n.php';

$wgChangedTemplatesToQC = array("stub", "format", "cleanup", "copyedit");
$wgTemplateChangedVotesRequired = array(
	"removed" => array("yes"=>1, "no"=>2),
	"added" => array("yes"=>1, "no"=>2)
);

$wgAutoloadClasses["QCRule"] = $dir . 'QC.body.php';
$wgAutoloadClasses["QCRCPatrol"] = $dir . 'QC.body.php';
$wgAutoloadClasses["QCRuleIntroImage"] = $dir . 'QC.body.php';
$wgAutoloadClasses["QCRuleTip"] = $dir . 'QC.body.php';

$wgResourceModules['ext.wikihow.quality_guardian'] = array(
    'styles' => array('qc.css'),
    'scripts' => array('qc.js'),
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/qc',
    'position' => 'top',
    'targets' => array('desktop', 'mobile'),
    'dependencies' => array(
    	'ext.wikihow.common_top',
    	'ext.wikihow.common_bottom',
    ),
);

$wgQCIntroImageVotesRequired = array ("yes"=>2, "no"=>2);
$wgQCVideoChangeVotesRequired = array ("yes"=>2, "no"=>1);
$wgQCRCPatrolVotesRequired = array ("yes"=>1, "no"=>1);
$wgQCNewTipVotesRequired = array ("yes"=>2, "no"=>2);


$wgHooks["PageContentSaveComplete"][] = "wfCheckQC";
$wgHooks["MarkPatrolledBatchComplete"][] = array("wfCheckQCPatrols");

//$wgQCRulesToCheck = array("ChangedTemplate/Stub", "ChangedTemplate/Format", "ChangedTemplate/Cleanup", "ChangedTemplate/Copyedit", "ChangedIntroImage", "ChangedVideo", "RCPatrol");
$wgQCRulesToCheck = array("ChangedVideo", "RCPatrol", "NewTip");

$wgAvailableRights[] = 'qc';
$wgGroupPermissions['staff']['qc'] = true;

// Log page definitions
$wgLogTypes[]              = 'qc';
$wgLogNames['qc']          = 'qclogpage';
$wgLogHeaders['qc']        = 'qclogtext';

$wgHooks['ArticleDelete'][] = array("wfClearQCOnDelete");

$wgHooks["IsEligibleForMobileSpecial"][] = array("wfQGIsEligibleForMobile");

/*
CREATE TABLE `qc` (
  `qc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qc_key` varchar(32) NOT NULL DEFAULT '',
  `qc_action` varchar(16) NOT NULL DEFAULT '',
  `qc_page` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_timestamp` varchar(14) NOT NULL DEFAULT '',
  `qc_user` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_user_text` varchar(255) NOT NULL DEFAULT '',
  `qc_rev_id` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_old_rev_id` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_patrolled` tinyint(3) unsigned DEFAULT '0',
  `qc_yes_votes_req` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `qc_no_votes_req` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `qc_yes_votes` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `qc_no_votes` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `qc_checkout_time` varchar(14) NOT NULL DEFAULT '',
  `qc_checkout_user` int(10) unsigned NOT NULL DEFAULT '0',
  `qc_extra` varchar(32) DEFAULT '',
  `qc_page_alt` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`qc_id`)
);

CREATE TABLE `qc_vote` (
  `qcv_qcid` int(10) unsigned NOT NULL,
  `qcv_user` int(10) unsigned NOT NULL,
  `qcv_vote` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `qc_timestamp` varbinary(14) NOT NULL DEFAULT '',
  KEY `qcv_qcid` (`qcv_qcid`),
  KEY `qcv_user` (`qcv_user`),
  KEY `qc_timestamp` (`qc_timestamp`)
);
 */

function wfCheckQC(&$wikiPage, &$user, $content, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	global $wgChangedTemplatesToQC;

	// if an wikiPage becomes a redirect, vanquish all previous qc entries
	if ($content->isRedirect()) {
		QCRule::markAllAsPatrolled($wikiPage->getTitle());
		return true;
	}

	// check for bots
	$bots = WikihowUser::getBotIDs();
	if (in_array($user->getID(),$bots)) {
		return true;
	}

	// ignore reverted edits
	if (preg_match("@Reverted edits by@", $summary)) {
		return true;
	}

	// check for intro image change, reverts are ok for this one
	// $l = new QCRuleIntroImage($revision, $wikiPage);
	// $l->process();

	// do the templates
	foreach ($wgChangedTemplatesToQC as $t) {
		wfDebug("QC: About to process template change $t\n");
		$l = new QCRuleTemplateChange($t, $revision, $wikiPage);
		$l->process();
	}

	// check for video changes
	$l = new QCRuleVideoChange($revision, $wikiPage);
	$l->process();

	return true;
}

function wfCheckQCPatrols(&$article, &$rcids, &$user) {
	if ($article && $article->getTitle() && $article->getTitle()->inNamespace(NS_MAIN)) {
			$l = new QCRCPatrol($article, $rcids); //
			$l->process();
	}
	return true;
}

function wfClearQCOnDelete($wikiPage) {
	try {
		$dbw = wfGetDB(DB_MASTER);
		$id = $wikiPage->getId();
		$dbw->delete("qc", array("qc_page"=>$id));
	} catch (Exception $e) {}
	return true;
}

//only eligible for our Tips Guardian needs
function wfQGIsEligibleForMobile(&$isEligible) {
	global $wgTitle, $wgRequest;
	if ($wgTitle && strrpos($wgTitle->getText(), "QG") === 0 &&
		($wgRequest->getVal('fetchInnards') || $wgRequest->getVal('postResults'))) {
		$isEligible = true;
	}

	return true;
}
