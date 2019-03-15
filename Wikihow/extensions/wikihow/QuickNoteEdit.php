<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'QuickNoteEdit',
    'author' => 'Vu <vu@wikihow.com>',
    'description' => 'quick popups for notes and edit',
);

$wgExtensionMessagesFiles['QuickNoteEdit'] = __DIR__ . '/QuickNoteEdit.i18n.php';
$wgSpecialPages['QuickNoteEdit'] = 'QuickNoteEdit';
$wgAutoloadClasses['QuickNoteEdit'] = __DIR__ . '/QuickNoteEdit.body.php';
