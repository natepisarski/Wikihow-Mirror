<?php

$wgExtensionCredits['other'][] = array(
	'name' => 'SensitiveArticle',
	'author' => 'Alberto Burgos',
	'description' => 'Sensitive Article Tagging'
);

$wgMessagesDirs['SensitiveArticle'] = __DIR__ . '/i18n';

// Classes shared across the project
$wgAutoloadClasses['SensitiveArticle\SensitiveArticleDao'] = __DIR__ . '/core/SensitiveArticleDao.class.php';
$wgAutoloadClasses['SensitiveArticle\SensitiveArticle'] = __DIR__ . '/core/SensitiveArticle.class.php';
$wgAutoloadClasses['SensitiveArticle\SensitiveReason'] = __DIR__ . '/core/SensitiveReason.class.php';
$wgAutoloadClasses['SensitiveArticle\SensitiveArticleVote'] = __DIR__ . '/core/SensitiveArticleVote.class.php';
$wgAutoloadClasses['SensitiveArticle\SensitiveArticleVoteAction'] = __DIR__ . '/core/SensitiveArticleVoteAction.class.php';
$wgAutoloadClasses['SensitiveArticle\SensitiveTopicJob'] = __DIR__ . '/core/SensitiveTopicJob.class.php';
$wgHooks['SensitiveReasonDeleted'][] = ['SensitiveArticle\SensitiveArticle::onSensitiveReasonDeleted'];

// The Sensitive Article Tagging widget on the staff-only section
$wgSpecialPages['SensitiveArticleWidgetApi'] = 'SensitiveArticle\SensitiveArticleWidgetApi';
$wgAutoloadClasses['SensitiveArticle\SensitiveArticleWidget'] = __DIR__ . '/widget/SensitiveArticleWidget.class.php';
$wgAutoloadClasses['SensitiveArticle\SensitiveArticleWidgetApi'] = __DIR__ . '/widget/SensitiveArticleWidgetApi.body.php';

// Special:SensitiveArticleAdmin
$wgSpecialPages['SensitiveArticleAdmin'] = 'SensitiveArticle\SensitiveArticleAdmin';
$wgAutoloadClasses['SensitiveArticle\SensitiveArticleAdmin'] = __DIR__ . '/admin/SensitiveArticleAdmin.body.php';
$wgResourceModules['ext.wikihow.SensitiveArticle.admin'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/SensitiveArticle/admin/resources',
	'localBasePath' => __DIR__ . '/admin/resources',
	'styles' => ['sensitive_article_admin.less'],
	'scripts' => ['sensitive_article_admin.js'],
	'messages' => [
		'saa_delete_confirm_2'
	]
];

// Special:TopicTagging
$wgSpecialPages['TopicTagging'] = 'SensitiveArticle\TopicTagging';
$wgAutoloadClasses['SensitiveArticle\TopicTagging'] = __DIR__ . '/tool/TopicTagging.body.php';
$wgResourceModules['ext.wikihow.topic_tagging_tool'] = [
	'targets' => ['desktop', 'mobile'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/SensitiveArticle/tool/resources',
	'localBasePath' => __DIR__ . '/tool/resources',
	'styles' => ['topic_tagging_tool.less'],
	'scripts' => ['topic_tagging_tool.js'],
	'messages' => [
		'ti_TopicTagging_bullets'
	]
];

$wgLogTypes[] = 'topic_tagging';
$wgLogNames['topic_tagging'] = 'topic_tagging';
$wgLogHeaders['topic_tagging'] = 'topic_tagging';

// Special:TopicTaggingAdmin
$wgSpecialPages['TopicTaggingAdmin'] = 'SensitiveArticle\TopicTaggingAdmin';
$wgAutoloadClasses['SensitiveArticle\TopicTaggingAdmin'] = __DIR__ . '/admin/TopicTaggingAdmin.body.php';
$wgResourceModules['ext.wikihow.topic_tagging_admin'] = [
	'targets' => ['desktop'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/SensitiveArticle/admin/resources',
	'localBasePath' => __DIR__ . '/admin/resources',
	'styles' => [
		'topic_tagging_admin.less',
		'../../../common/font-awesome-4.2.0/css/font-awesome.min.css'
	],
	'scripts' => [
		'topic_tagging_admin.js',
		'../../../common/jquery.simplemodal.1.4.4.min.js'
	],
	'messages' => [
		'tta_err_no_topic',
		'tta_err_no_articles',
		'tta_err_no_question',
		'tta_err_no_description',
		'tta_job_name_label',
		'tta_job_question_label',
		'tta_job_description_label',
		'tta_articles_prompt',
		'tta_articles_example',
		'submit',
		'tta_done_button'
	]
];

// Special:BulkTopicTagging
$wgSpecialPages['BulkTopicTagging'] = 'SensitiveArticle\BulkTopicTagging';
$wgAutoloadClasses['SensitiveArticle\BulkTopicTagging'] = __DIR__ . '/admin/BulkTopicTagging.body.php';
$wgResourceModules['ext.wikihow.bulk_topic_tagging'] = [
	'targets' => ['desktop'],
	'position' => 'top',
	'remoteExtPath' => 'wikihow/SensitiveArticle/admin/resources',
	'localBasePath' => __DIR__ . '/admin/resources',
	'styles' => ['bulk_topic_tagging.less'],
	'scripts' => ['bulk_topic_tagging.js'],
	'messages' => [
		'btt_no_tag',
		'btt_err_no_tag',
		'btt_err_no_action',
		'btt_err_no_articles'
	],
	'dependencies' => ['jquery.chosen']
];

