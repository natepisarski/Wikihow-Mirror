<?php

$wgHooks['UserLogout'][] = 'wfLogoutOfVanilla';
$wgHooks['UserLoginComplete'][] = 'wfProcessVanillaRedirect';
$wgHooks['UserLoginComplete'][] = 'wfLogoutOfVanilla';
$wgHooks['BlockIpComplete'][] = 'wfBlockVanillaUser';

$wgSpecialPages['Vanilla'] = 'Vanilla';
$wgAutoloadClasses['Vanilla'] = __DIR__ . '/Vanilla.body.php';

function wfLogoutOfVanilla() {
	global $wgCookieDomain;
	if ( !headers_sent() ) {
		$cookies = array('Vanilla', 'Vanilla-Volatile');
		foreach ($cookies as $c) {
			setcookie($c, ' ', time() - 3600, '/', '.' . $wgCookieDomain);
			unset($_COOKIE[$c]);
		}
	}
	return true;
}

function wfProcessVanillaRedirect() {
	global $wgRequest, $wgOut, $wgForumLink;
	if ($wgRequest->getVal('returnto') == 'vanilla') {
		$wgOut->redirect($wgForumLink);
	}
	return true;
}

function wfBlockVanillaUser($block, $blocker) {
	try {
		$blockInfo = $block->getTargetAndType();

		if ( !is_array($blockInfo) || $blockInfo[1] != Block::TYPE_USER ) return true;

		$blockedUser = User::newFromName($blockInfo[0], false);
		if ( $blockedUser ) $blockedUser->load();
		if ( !$blockedUser || !$blockedUser->isLoggedIn() ) return true;
		Vanilla::sync($blockedUser);
	} catch ( Exception $e ) {
		//wfDebugLog( 'vanilla', "block user exception: " . $e->getMessage() );
	}
}
