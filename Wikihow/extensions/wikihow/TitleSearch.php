<?

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'TitleSearch',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Used for the related wikihows tool drop down auto-suggest feature',
);

$wgSpecialPages['TitleSearch'] = 'TitleSearch';
$wgAutoloadClasses['TitleSearch'] = dirname( __FILE__ ) . '/TitleSearch.body.php';

