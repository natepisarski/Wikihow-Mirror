<?

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Knowledge Box CTA and special page',
    'author' => 'George Bahij',
    'description' => 'A box for soliciting raw content from users',
);

$wgSpecialPages['KnowledgeBox'] = 'KnowledgeBox';

$wgAutoloadClasses['KnowledgeBox'] = dirname(__FILE__) . '/KnowledgeBox.body.php';
$wgAutoloadClasses['KnowledgeBoxCopyscapeJob'] = dirname(__FILE__) . '/KnowledgeBoxCopyscapeJob.body.php';

$wgResourceModules['ext.wikihow.knowledgebox'] = array(
	'scripts' => array(
		'resources/scripts/knowledgebox.js'
	),
	'styles' => array(
		'resources/styles/kb_general.css',
		'resources/styles/kb_layout.css',
		'resources/styles/kb_box.css',
		'resources/styles/kb_submit_section.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/KnowledgeBox',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'mediawiki.page.ready'
	)
);

$wgResourceModules['ext.wikihow.knowledgebox_pager'] =
	$wgResourceModulesDesktopBoiler + [
		'styles' => [ 'KnowledgeBox/knowledgeboxpager.css' ]
	];

$wgJobClasses['KnowledgeBoxCopyscapeJob'] = 'KnowledgeBoxCopyscapeJob';

$wgExtensionMessagesFiles['KnowledgeBox'] = dirname(__FILE__) . '/KnowledgeBox.i18n.php';

