<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Top Answerers',
	'author' => 'Scott Cushman',
	'description' => 'A way to manage our top answerers.'
);

$wgSpecialPages['TopAnswerersAdmin'] = 'TopAnswerersAdmin';

$wgAutoloadClasses['TopAnswerersAdmin']            = __DIR__ . '/TopAnswerersAdmin.body.php';
$wgAutoloadClasses['TopAnswerers']                 = __DIR__ . '/TopAnswerers.class.php';
$wgAutoloadClasses['TAInsertArticleQuestionJob']   = __DIR__ . '/jobs/TAInsertArticleQuestionJob.php';

$wgJobClasses['TAInsertArticleQuestionJob'] = 'TAInsertArticleQuestionJob';

$wgMessagesDirs['TopAnswerers'] = __DIR__ . '/i18n/';

$wgHooks['InsertArticleQuestion'][] = ['TopAnswerers::onInsertArticleQuestion'];

$wgResourceModules['ext.wikihow.top_answerers'] = array(
	'scripts' => 'modules/top_answerers_admin.js',
	'messages' => [
		'ta_block_link',
		'ta_added_text',
		'ta_last_answer_text',
		'ta_type_text',
		'ta_answers_live_label',
		'ta_answers_calc_label',
		'ta_sim_label',
		'ta_rating_label',
		'ta_subcats_label',
		'ta_unblock_link'
	],
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/TopAnswerers',
	'targets' => ['desktop', 'mobile']
);

$wgResourceModules['ext.wikihow.top_answerers.style'] = array(
	'styles' => 'modules/top_answerers_admin.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/TopAnswerers',
	'targets' => ['desktop', 'mobile']
);
