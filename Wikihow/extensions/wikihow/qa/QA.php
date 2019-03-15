<?php

$wgExtensionCredits['QAAdmin'][] = array(
	'name' => 'Q&A Admin Page',
	'author' => 'Jordan Small',
	'description' => 'Admin tool for Q&A project',
);

$wgSpecialPages['QAAdmin'] = 'QAAdmin';
$wgAutoloadClasses['QAAdmin'] = __DIR__ . '/admin/QAAdmin.body.php';
$wgExtensionMessagesFiles['QAAdmin'] = __DIR__ . '/admin/QAAdmin.i18n.php';

$wgExtensionMessagesFiles['QAAliases'] = __DIR__ . '/QA.alias.php';

$wgExtensionCredits['QA'][] = array(
	'name' => 'Q&A',
	'author' => 'Jordan Small',
	'description' => 'Q&A Endpoint for user question submissions',
);

$wgSpecialPages['QA'] = 'QA';
$wgAutoloadClasses['QA'] = __DIR__ . '/widget/QA.body.php';

$wgAutoloadClasses['QAWidget'] = __DIR__ . '/widget/QAWidget.php';
$wgMessagesDirs['QAWidget'] = [
	$IP . '/extensions/wikihow/ext-utils/thumbs_up_down/i18n/',
	__DIR__ . '/widget/i18n/',
];

$wgAutoloadClasses['QAWidgetCache'] = __DIR__ . '/widget/QAWidgetCache.php';
$wgAutoloadClasses['QADB'] = __DIR__ . '/model/QADB.php';
$wgExtensionMessagesFiles['QADB'] = __DIR__ . '/model/QADB.i18n.php';
$wgAutoloadClasses['QADBResult'] = __DIR__ . '/model/QADBResult.php';
$wgAutoloadClasses['ArticleQuestion'] = __DIR__ . '/model/ArticleQuestion.php';
$wgAutoloadClasses['SubmittedQuestion'] = __DIR__ . '/model/SubmittedQuestion.php';
$wgAutoloadClasses['CuratedQuestion'] = __DIR__ . '/model/CuratedQuestion.php';
$wgAutoloadClasses['CuratedAnswer'] = __DIR__ . '/model/CuratedAnswer.php';
$wgAutoloadClasses['QAImportDoc'] = __DIR__ . '/model/QAImportDoc.php';
$wgAutoloadClasses['QADataFile'] = __DIR__ . '/QADataFile.php';
$wgAutoloadClasses['QAUtil'] = __DIR__ . '/QAUtil.php';

$wgAutoloadClasses['QACopyCheckJob'] = __DIR__ . '/jobs/QACopyCheckJob.php';
$wgAutoloadClasses['QAAnswerEmailJob'] = __DIR__ . '/jobs/QAAnswerEmailJob.php';
$wgAutoloadClasses['QAHelpfulnessEmailJob'] = __DIR__ . '/jobs/QAHelpfulnessEmailJob.php';
$wgMessagesDirs['QAHelpfulnessEmailJob'] = [ __DIR__ . '/jobs/i18n/' ];

$wgHooks['BeforePageDisplay'][] = 'QAWidget::onBeforePageDisplay';
$wgHooks['AddDesktopTOCItems'][] = array('QAWidget::onAddDesktopTOCItems');
$wgHooks['AddMobileTOCItemData'][] = array('QAWidget::onAddMobileTOCItemData');
$wgHooks['UnitTestsList'][] = array('ArticleQuestion::onUnitTestsList');
$wgHooks['UnitTestsList'][] = array( 'QAUtil::onUnitTestsList');
$wgHooks['InsertArticleQuestion'][] = ['QAWidgetCache::onInsertArticleQuestion'];
$wgHooks['DeleteArticleQuestion'][] = ['QAWidgetCache::onDeleteArticleQuestion'];
$wgHooks['ArticlePurge'][] = ['QAWidgetCache::onArticlePurge'];
$wgHooks['InsertArticleQuestion'][] = ['QAUtil::onInsertArticleQuestion'];
$wgHooks['QAHelpfulnessVote'][] = ['QAUtil::onQAHelpfulnessVote'];

$wgJobClasses['QACopyCheckJob'] = 'QACopyCheckJob';
$wgJobClasses['QAAnswerEmailJob'] = 'QAAnswerEmailJob';
$wgJobClasses['QAHelpfulnessEmailJob'] = 'QAHelpfulnessEmailJob';

$wgResourceModules['mobile.wikihow.qa_widget'] = array(
	'styles' => ['qa_widget_mobile.less'],
	'scripts' => ['qa_widget.js'],
	'localBasePath' => __DIR__ . '/widget',
	'remoteExtPath' => 'wikihow/qa/widget',
	'position' => 'bottom',
	'messages' => [
		'qa_prompt_first',
		'qa_submitted',
		'qa_show_submitted_singular',
		'qa_show_submitted_plural',
		'qa_curate',
		'qa_ignore',
		'qa_flag',
		'qa_edit',
		'qa_mark_inactive',
		'qa_edit_form_submit',
		'qa_edit_form_delete',
		'qa_edit_form_cancel',
		'qa_edit_form_inactive',
		'qa_edit_form_question_placeholder',
		'qa_edit_form_answer_placeholder',
		'qa_none_submitted',
		'qa_proposed_answer_confirmation_mobile',
		'qa_pa_email_placeholder',
		'qa_flagged_confirmation',
		'qa_cq_text_error',
		'qa_ca_text_error',
		'qa_ca_text_min',
		'qa_ca_error_url',
		'qa_ca_error_phone',
		'thumbs_default_prompt',
		'thumbs_response',
		'qa_curate_mobile',
		'qa_edit_form_verifier_label',
		'qa_thanks_for_answer',
		'qa_social_login_form_cta',
		'qa_social_login_disclaimer',
		'qa_thanks_for_social_login',
		'qa_info_after_social_login',
		'qa_social_login_error',
		'qa_answered_by',
		'qa_edit_form_remove_submitter',
		'qa_asked_count',
		'ta_answers_label',
		'ta_label',
		'ta_subcats_intro',
		'ta_subcats_outro',
		'qa_question_label'
	],
	'targets' => [
		'mobile',
		'desktop'
	],
	'dependencies' => [
		'mobile.wikihow',
		'wikihow.common.mustache',
		'jquery.throttle-debounce',
		'wikihow.common.string_validator',
		'mobile.wikihow.common.thumbs_up_down',
		'wikihow.common.pub_sub',
		'ext.wikihow.socialauth',
        'ext.wikihow.sociallogin.buttons'
	],
);

$wgResourceModules['ext.wikihow.qa_widget'] = array(
	'styles' => array('qa_widget_desktop.less'),
	'scripts' => array('qa_widget.js'),
	'localBasePath' => __DIR__ . '/widget',
	'remoteExtPath' => 'wikihow/qa/widget',
	'position' => 'bottom',
	'messages' => [
		'qa_prompt_first',
		'qa_submitted',
		'qa_show_submitted_singular',
		'qa_show_submitted_plural',
		'qa_curate',
		'qa_ignore',
		'qa_flag',
		'qa_edit',
		'qa_mark_inactive',
		'qa_edit_form_submit',
		'qa_edit_form_delete',
		'qa_edit_form_cancel',
		'qa_edit_form_inactive',
		'qa_edit_form_remove_submitter',
		'qa_edit_form_question_placeholder',
		'qa_edit_form_answer_placeholder',
		'qa_none_submitted',
		'qa_proposed_answer_confirmation_desktop',
		'qa_pa_email_placeholder',
		'qa_flagged_confirmation',
		'qa_cq_text_error',
		'qa_ca_text_error',
		'qa_ca_text_min',
		'qa_ca_error_url',
		'qa_ca_error_phone',
		'thumbs_default_prompt',
		'thumbs_response',
		'qa_curate_mobile',
		'qa_edit_form_verifier_label',
		'thumbs_response',
		'qa_thumbs_yes',
		'qa_thumbs_no',
		'qa_thumbs_nohelp',
		'qa_thumbs_help',
		'qa_target_page',
		"qa_thanks_for_answer",
		"qa_social_login_form_cta",
		"qa_social_login_disclaimer",
		"qa_thanks_for_social_login",
		"qa_info_after_social_login",
		"qa_social_login_error",
		'qa_flag_duplicate',
		'qa_dup_thanks',
		'qa_asked_count',
		'qa_checkout_error',
		'qa_up_edit_form_approve',
		'qa_up_edit_form_delete',
		'qa_up_edit_form_reject',
		'qa_generic_username',
		'ta_answers_label',
		'ta_label',
		'ta_subcats_intro',
		'ta_subcats_outro',
		'qa_afo_incorrect',
		'qa_afo_other',
		'qa_question_label'
	],
	'targets' => array('mobile', 'desktop'),
	'dependencies' => [
		'ext.wikihow.common_bottom',
		'wikihow.common.mustache',
		'jquery.throttle-debounce',
		'wikihow.common.string_validator',
		'wikihow.common.thumbs_up_down',
		'wikihow.common.pub_sub',
		'ext.wikihow.socialauth',
        'ext.wikihow.sociallogin.buttons'
	],
);

$wgResourceModules['mobile.wikihow.qa_admin'] = array(
	'styles' => array('qa_admin.less'),
	'scripts' => array('qa_admin.js'),
	'localBasePath' => __DIR__ . '/admin' ,
	'remoteExtPath' => 'wikihow/qa/admin',
	'position' => 'top',
	'targets' => array('mobile', 'desktop'),
	'messages' => [
		'qa_approved_success',
		'qa_ignored_success',
		'qa_update_success',
	],
	'dependencies' => array('wikihow.common.mustache', 'wikihow.common.jquery.download'),
);
