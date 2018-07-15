<?php

$wgExtensionCredits['MessengerSearchBot'][] = array(
	'name' => 'MessengerSearchBot Page',
	'author' => 'Jordan Small',
	'description' => 'A wikiHow bot for Facebook Messenger',
);

$wgSpecialPages['MessengerSearchBot'] = 'MessengerSearchBot';
$wgAutoloadClasses['MessengerSearchBot'] = __DIR__ . '/MessengerSearchBot.php';
$wgAutoloadClasses['WikihowFbBotApp'] = __DIR__ . '/WikihowFbBotApp.php';
$wgAutoloadClasses['WikihowTitlesMessage'] = __DIR__ . '/messages/WikihowTitlesMessage.php';
$wgAutoloadClasses['CallsToActionMessage'] = __DIR__ . '/messages/CallsToActionMessage.php';


$wgMessagesDirs['MessengerSearchBot'] = [__DIR__ . '/i18n/'];
$wgMessagesDirs['WikihowTitlesMessage'] = [__DIR__ . '/i18n/'];
$wgMessagesDirs['CallsToActionMessage'] = [__DIR__ . '/i18n/'];