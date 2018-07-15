<?php
/**
 * SubscriptionManager extension -- a special page for opting out from receiving
 * e-mails
 *
 * @file
 * @ingroup Extensions
 * @version 1.2.0 (2014-08-09)
 * @author Lojjik Braughler (SudoKing)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 3.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file can only be run through MediaWiki.' );
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SubscriptionManager',
	'version' => '1.1.4',
	'author' => 'Lojjik Braughler (SudoKing)',
	'url' => 'http://src.wikihow.com',
	'descriptionmsg' => 'subscriptionmanager-desc'
);

$dir = __DIR__;

// Internationalization
$wgMessagesDirs['SubscriptionManager'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SubscriptionManagerAliases'] = "$dir/SubscriptionManager.alias.php";

// Set up special page classes
$wgAutoloadClasses['SpecialSubscriptionManager'] = "$dir/SpecialSubscriptionManager.php";
$wgAutoloadClasses['SubscriptionFormValidator'] = "$dir/SubscriptionFormValidator.php"; // hunky validating thing
$wgSpecialPages['SubscriptionManager'] = 'SpecialSubscriptionManager';


// Configuration options
$wgSupportEmail = $wgPasswordSender;
$wgUnsubscribeLinkedAccounts = true;