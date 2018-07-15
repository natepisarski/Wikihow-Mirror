<?

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Terms of use',
    'author' => 'Aaron',
    'description' => 'a terms of use page that is dependent upon the domain',
);

$wgSpecialPages['Terms-Of-Use'] = 'TermsOfUse';
$wgAutoloadClasses['TermsOfUse'] = dirname(__FILE__) . '/TermsOfUse.body.php';

