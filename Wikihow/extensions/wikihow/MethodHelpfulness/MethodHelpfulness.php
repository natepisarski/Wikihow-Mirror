<?php
if (!defined('MEDIAWIKI')) {
	die();
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'MethodHelpfulness',
	'author' => 'George Bahij',
	'namemsg' => 'methodhelpfulness',
	'description' => 'A tool to collect helpfulness data for article methods',
	'descriptionmsg' => 'methodhelpfulnessdescription',
	'version' => 1
);

$wgSpecialPages['MethodHelpfulness'] = 'MethodHelpfulness\MethodHelpfulness';
$wgAutoloadClasses['MethodHelpfulness\MethodHelpfulness'] = __DIR__ . '/MethodHelpfulness.body.php';
$wgAutoloadClasses['MethodHelpfulness\CTA'] = __DIR__ . '/MethodHelpfulnessCTA.class.php';
$wgAutoloadClasses['MethodHelpfulness\Controller'] = __DIR__ . '/MethodHelpfulnessController.class.php';
$wgAutoloadClasses['MethodHelpfulness\ArticleMethod'] = __DIR__ . '/ArticleMethod.class.php';
$wgAutoloadClasses['MethodHelpfulness\Widget'] = __DIR__ . '/MethodHelpfulnessWidget.class.php';
$wgAutoloadClasses['MethodHelpfulness\MethodHeaderWidgetSection'] = __DIR__ . '/MethodHelpfulnessWidget.class.php';

$wgResourceModules['ext.wikihow.methodhelpfulness.cta'] = array(
	'scripts' => array(
		'resources/cta/common/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/common/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile')
);

$wgResourceModules['ext.wikihow.methodhelpfulness.cta.bottom_form'] = array(
	'scripts' => array(
		'resources/cta/bottom_form/common/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/bottom_form/common/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array(
		'ext.wikihow.methodhelpfulness.cta'
	)
);

$wgResourceModules['ext.wikihow.methodhelpfulness.cta.bottom_form.desktop'] = array(
	'scripts' => array(
		'resources/cta/bottom_form/desktop/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/bottom_form/desktop/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'ext.wikihow.methodhelpfulness.cta.bottom_form'
	)
);

$wgResourceModules['ext.wikihow.methodhelpfulness.cta.bottom_form.mobile'] = array(
	'scripts' => array(
		'resources/cta/bottom_form/mobile/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/bottom_form/mobile/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('mobile'),
	'dependencies' => array(
		'ext.wikihow.methodhelpfulness.cta.bottom_form'
	)
);

$wgResourceModules['ext.wikihow.methodhelpfulness.cta.method_thumbs'] = array(
	'scripts' => array(
		'resources/cta/method_thumbs/common/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/method_thumbs/common/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array(
		'ext.wikihow.methodhelpfulness.cta'
	)
);

$wgResourceModules['ext.wikihow.methodhelpfulness.cta.method_thumbs.desktop'] = array(
	'scripts' => array(
		'resources/cta/method_thumbs/desktop/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/method_thumbs/desktop/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'ext.wikihow.methodhelpfulness.cta.method_thumbs'
	)
);

$wgResourceModules['ext.wikihow.methodhelpfulness.cta.method_thumbs.mobile'] = array(
	'scripts' => array(
		'resources/cta/method_thumbs/mobile/scripts/mh.js'
	),
	'styles' => array(
		'resources/cta/method_thumbs/mobile/styles/mh.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('mobile'),
	'dependencies' => array(
		'ext.wikihow.methodhelpfulness.cta.method_thumbs'
	)
);

$wgResourceModules['ext.wikihow.methodhelpfulness.widget'] = array(
	'scripts' => array(
		'resources/widget/mh_widget.js'
	),
	'styles' => array(
		'resources/widget/mh_widget.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/MethodHelpfulness',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array(
		'ext.wikihow.common_bottom'
	)
);

$wgExtensionMessagesFiles['MethodHelpfulness'] = __DIR__ . '/MethodHelpfulness.i18n.php';
$wgExtensionMessagesFiles['MethodHelpfulnessAliases'] = __DIR__ . '/MethodHelpfulness.alias.php';

$wgHooks['RatingsCleared'][] = array('MethodHelpfulness\Controller::onRatingsCleared');

