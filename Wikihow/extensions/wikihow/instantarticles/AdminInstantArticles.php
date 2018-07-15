<?

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Admin Instant Articles',
    'description' => 'upload to fb instant articles',
);

$wgSpecialPages['AdminInstantArticles'] = 'AdminInstantArticles';
$wgAutoloadClasses['AdminInstantArticles'] = dirname(__FILE__) . '/AdminInstantArticles.body.php';

$wgResourceModules['ext.wikihow.admininstantarticles'] = array(
	'scripts' => array( 'admininstantarticles.js', ),
	'styles' => array( 'admininstantarticles.css' ),
	'position' => 'top',
	'targets' => array( 'desktop' ),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/instantarticles',
	'dependencies' => array('mediawiki.page.startup', 'jquery.spinner'),
);
