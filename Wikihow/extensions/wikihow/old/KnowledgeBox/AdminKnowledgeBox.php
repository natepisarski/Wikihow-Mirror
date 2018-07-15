<?

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Knowledge Box article management',
    'author' => 'George Bahij',
    'description' => 'Administrative special page for managing KnowledgeBox articles',
);

$wgSpecialPages['AdminKnowledgeBox'] = 'AdminKnowledgeBox';
$wgAutoloadClasses['AdminKnowledgeBox'] = dirname(__FILE__) . '/AdminKnowledgeBox.body.php';
