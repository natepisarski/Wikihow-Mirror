<?

if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['special'][] = array(
	'name' => 'Slider',
	'author' => 'Scott Cushman',
	'description' => 'The box that slides in to prompt the user for more stuff.',
);

$wgSpecialPages['Slider'] = 'Slider';
$wgAutoloadClasses['Slider'] = dirname( __FILE__ ) . '/Slider.body.php';
$wgExtensionMessagesFiles['Slider'] = dirname(__FILE__) . '/Slider.i18n.php';

/*
logging options:
- start-button
- no-link
- x-button
- start-link
- appear
*/
