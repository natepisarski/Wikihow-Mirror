<?php
/**
 * Aliases for Special:Payment
 */

$specialPageAliases = [];

/** English (English) */
$specialPageAliases['en'] = [
		// Game is used so Special:Game URLs don't break, this can be removed if the game campaign
		// is removed, or $wgHoneypotDefaultCampaign is changed to something other than 'game'
		'Campaign' => [ 'Campaign', 'Game' ]
];
