<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'UserTrustStats',
	'author' => 'David Morrow',
	'description' => 'lets you see all the scores for a particular user',
);

// $wgAutoloadClasses['UserTrustStats'] = __DIR__ . '/UserTrustStats.php';
$wgSpecialPages['UserTrustStats'] = 'UserTrustStats';

class UserTrustStats extends UnlistedSpecialPage {

	function __construct() {
		global $wgHooks;
		parent::__construct('UserTrustStats');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function execute($par) {
		$out = $this->getContext()->getOutput();
		$out->setArticleBodyOnly(true);
		
		$allPlants = UserTrustScore::$camelized;
		$payload = array("visitorId" => WikihowUser::getVisitorId());
		
		foreach($allPlants as $key => $val) {
			$key = $key;
			$scoreInst = new UserTrustScore($key);
			$payload[$key] = $scoreInst->getScore();
		}
		
		print_r(json_encode($payload));
	}
}