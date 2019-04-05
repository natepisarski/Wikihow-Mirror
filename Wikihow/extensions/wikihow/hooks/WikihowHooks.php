<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['ImageHooks'] = __DIR__ . '/ImageHooks.body.php';
$wgAutoloadClasses['DevImageHooks'] = __DIR__ . '/DevImageHooks.body.php';
$wgAutoloadClasses['PageHooks'] = __DIR__ . '/PageHooks.body.php';
$wgAutoloadClasses['SpecialPagesHooks'] = __DIR__ . '/SpecialPagesHooks.php';
$wgAutoloadClasses['ArticleHooks'] = __DIR__ . '/ArticleHooks.php';
$wgAutoloadClasses['DiffHooks'] = __DIR__ . '/DiffHooks.php';
$wgAutoloadClasses['EmailBounceHooks'] = __DIR__ . '/Email.body.php';
$wgAutoloadClasses['EmailNotificationHooks'] = __DIR__ . '/Email.body.php';


//
// ImageHooks - used in image processing or relating to images/files
//
$wgHooks['ImageConvertNoScale'][] = array('ImageHooks::onImageConvertNoScale');
$wgHooks['ImageConvertComplete'][] = array('ImageHooks::onImageConvertComplete');
$wgHooks['FileTransform'][] = array('ImageHooks::onFileTransform');
$wgHooks['BitmapDoTransformScalerParams'][] = array('ImageHooks::onBitmapDoTransformScalerParams');
$wgHooks['FileThumbName'][] = array('ImageHooks::onFileThumbName');
$wgHooks['ThumbnailBeforeProduceHTML'][] = array('ImageHooks::onThumbnailBeforeProduceHTML');
$wgHooks['ImageBeforeProduceHTML'][] = array('ImageHooks::onImageBeforeProduceHTML');
$wgHooks['ImageHandlerParseParamString'][] = array('ImageHooks::onImageHandlerParseParamString');
$wgHooks['ExtractThumbParameters'][] = array('ImageHooks::onExtractThumbParameters');
$wgHooks['ConstructImageConvertCommand'][] = array('ImageHooks::onConstructImageConvertCommand');


//
// PageHooks - used generally in different types of page- or url-level processing
//
$wgHooks['TitleSquidURLs'][] = array('PageHooks::onTitleSquidURLsPurge');
$wgHooks['TitleSquidURLs'][] = array('PageHooks::onTitleSquidURLsDecode');
$wgHooks['PreCDNPurge'][] = array('PageHooks::onPreCDNPurge');
$wgHooks['SetupAfterCache'][] = array('PageHooks::onSetupAfterCacheSetMobile');
$wgHooks['BeforeInitialize'][] = array('PageHooks::maybeRedirectRemoveInvalidQueryParams');
$wgHooks['BeforeInitialize'][] = array('PageHooks::maybeRedirectHTTPS');
$wgHooks['BeforeInitialize'][] = array('PageHooks::maybeRedirectProductionDomain');
$wgHooks['BeforeInitialize'][] = array('PageHooks::maybeRedirectTitus');
$wgHooks['BeforeInitialize'][] = array('PageHooks::redirectIfNotBotRequest');
$wgHooks['BeforeInitialize'][] = array('PageHooks::redirectIfPrintableRequest');
$wgHooks['BeforeInitialize'][] = array('PageHooks::maybeRedirectIfUseformat');
$wgHooks['BeforeInitialize'][] = array('PageHooks::noIndexRecentChangesRSS');
$wgHooks['SpecialPageBeforeExecute'][] = array('PageHooks::onSpecialPageBeforeExecuteRedirectTitus');
$wgHooks['ApiBeforeMain'][] = 'PageHooks::onApiBeforeMain';
$wgHooks['UnknownAction'][] = 'PageHooks::onUnknownAction';
$wgHooks['IsTrustedProxy'][] = array('PageHooks::checkFastlyProxy');
$wgHooks['BeforePageDisplay'][] = 'PageHooks::addFirebug';
$wgHooks['UserRequiresHTTPS'][] = 'PageHooks::makeHTTPSforAllUsers';
$wgHooks['OutputPageAfterGetHeadLinksArray'][] = 'PageHooks::onOutputPageAfterGetHeadLinksArray';
// $wgHooks['OutputPageBeforeHTML'][] = array('PageHooks::checkForDiscussionPage');
$wgHooks['AfterDisplayNoArticleText'][] = 'PageHooks::onAfterDisplayNoArticleText';

// Mediawiki 1.21 seems to redirect pages differently from 1.12, so we recreate
// the 1.12 functionality from "redirect" articles that are present in the DB.
$wgHooks['InitializeArticleMaybeRedirect'][] = array('PageHooks::onInitializeArticleMaybeRedirect');

$wgHooks['BeforePageDisplay'][] = array('PageHooks::addVarnishHeaders');
$wgHooks['BeforePageDisplay'][] = array('PageHooks::addInternetOrgVaryHeader');
$wgHooks['OutputPageBeforeHTML'][] = array('PageHooks::enforceCountryPageViewBan');

$wgHooks['OutputPageBeforeHTML'][] = array('PageHooks::setPage404IfNotExists');
$wgHooks['TitleMoveComplete'][] = array('PageHooks::fix404AfterMove');
$wgHooks['ArticleDelete'][] = array('PageHooks::fix404AfterDelete');
$wgHooks['PageContentInsertComplete'][] = array('PageHooks::fix404AfterInsert');
$wgHooks['ArticleUndelete'][] = array('PageHooks::fix404AfterUndelete');
$wgHooks['ArticlePurge'][] = array('PageHooks::beforeArticlePurge');
$wgHooks['TitleMoveComplete'][] = array('PageHooks::onTitleMoveCompletePurgeThumbnails');

// ARG added this hook to remove the version from the startup module scripts
$wgHooks['ResourceLoaderStartupModuleQuery'][] = array('PageHooks::onResourceLoaderStartupModuleQuery');
// ARG added this hook to perform a special db operation on change of specific config storage values
$wgHooks['ConfigStorageDbStoreConfig'][] = array('PageHooks::onConfigStorageDbStoreConfig');
$wgHooks['MaybeAutoPatrol'][] = array('PageHooks::onMaybeAutoPatrol');

// Temporary, for redirect debugging
//$wgHooks['BeforePageRedirect'][] = array('PageHooks::onBeforePageRedirect');


//
// ArticleHooks - used in processing article content or metadata
//
$wgHooks['PageContentSaveComplete'][] = array('ArticleHooks::onPageContentSaveUndoEditMarkPatrolled');
$wgHooks['PageContentSaveComplete'][] = array('ArticleHooks::updatePageFeaturedFurtherEditing');
$wgHooks['EditPageBeforeEditToolbar'][] = array('ArticleHooks::editPageBeforeEditToolbar');
$wgHooks['DoEditSectionLink'][] = array('ArticleHooks::onDoEditSectionLink');
$wgHooks['MakeGlobalVariablesScript'][] = array('ArticleHooks::addGlobalVariables');
$wgHooks['MakeGlobalVariablesScript'][] = array('ArticleHooks::addJSglobals');
$wgHooks['DeferHeadScripts'][] = array('ArticleHooks::onDeferHeadScripts');
$wgHooks['PageContentSaveComplete'][] = array('ArticleHooks::firstEditPopCheck');
$wgHooks['PageContentSaveComplete'][] = array('ArticleHooks::onPageContentSaveCompleteAddFirstEditTag');
$wgHooks['ArticlePageDataAfter'][] = array('ArticleHooks::firstEditPopIt');
$wgHooks['AddDesktopTOCItems'][] = array('ArticleHooks::addDesktopTOCItems');


$wgHooks['GoodRevisionUpdated'][] = array('ArticleHooks::updateExpertVerifiedRevision');

// Reuben 1/14: this hook will get rid of [Mark as Patrolled] at bottom of page.
$wgHooks['ArticleShowPatrolFooter'][] = array('ArticleHooks::onArticleShowPatrolFooter');
$wgHooks['ParserClearState'][] = array('ArticleHooks::turnOffAutoTOC');
$wgHooks['AtAGlanceTest'][] = array('ArticleHooks::runAtAGlanceTest');
$wgHooks['ProcessArticleHTMLAfter'][] = ['ArticleHooks::BuildMuscleHook'];
$wgHooks['MobileProcessDomAfterSetSourcesSection'][] = ['ArticleHooks::BuildMuscleHook'];
//$wgHooks['BeforeOutputAltMethodTOC'][] = array('ArticleHooks::runAltMethodTOCTest');


//
// SpecialPagesHooks - used primarily on particular special pages
//
$wgHooks['ArticleConfirmDelete'][] = array('SpecialPagesHooks::getDeleteReasonFromCode');
$wgHooks['EditPage::showEditForm:fields'][] = array('SpecialPagesHooks::onShowEditFormFields');
$wgHooks['BeforeWelcomeCreation'][] = array('SpecialPagesHooks::onBeforeWelcomeCreation');

$wgHooks['SpecialRecentChangesPanel'][] = array('SpecialPagesHooks::onSpecialRecentChangesPanel');
$wgHooks['SpecialRecentChangesQuery'][] = array('SpecialPagesHooks::onSpecialRecentChangesQuery');
$wgHooks['wgQueryPages'][] = array('SpecialPagesHooks::onPopulateWgQueryPages');
$wgHooks['WantedPages::getQueryInfo'][] = array('SpecialPagesHooks::onWantedPagesGetQueryInfo');
$wgHooks['UserLogoutComplete'][] = array('SpecialPagesHooks::onUserLogoutComplete');
$wgHooks['WebRequestPathInfoRouter'][] = array('SpecialPagesHooks::onWebRequestPathInfoRouter');


//
// DiffHooks - used to modify the Difference Engine, or in diff processing
//
$wgHooks['NewDifferenceEngine'][] = array('DiffHooks::onNewDifferenceEngine');
$wgHooks['DifferenceEngineShowDiff'][] = array('DiffHooks::onDifferenceEngineShowDiff');
$wgHooks['DifferenceEngineShowDiffPage'][] = array('DiffHooks::onDifferenceEngineShowDiffPage');
$wgHooks['DifferenceEngineOldHeaderNoOldRev'][] = array('DiffHooks::onDifferenceEngineOldHeaderNoOldRev');

$wgHooks['DifferenceEngineOldHeader'][] = array('DiffHooks::onDifferenceEngineOldHeader');
$wgHooks['DifferenceEngineNewHeader'][] = array('DiffHooks::onDifferenceEngineNewHeader');
$wgHooks['DifferenceEngineNotice'][] = array('DiffHooks::onDifferenceEngineNotice');
$wgHooks['DifferenceEngineMarkPatrolledRCID'][] = array('DiffHooks::onDifferenceEngineMarkPatrolledRCID');
$wgHooks['DifferenceEngineMarkPatrolledLink'][] = array('DiffHooks::onDifferenceEngineMarkPatrolledLink');
$wgHooks['DifferenceEngineGetRevisionHeader'][] = array('DiffHooks::onDifferenceEngineGetRevisionHeader');
$wgHooks['DifferenceEngineRenderRevisionShowFinalPatrolLink'][] = array('DiffHooks::onDifferenceEngineRenderRevisionShowFinalPatrolLink');
$wgHooks['DifferenceEngineRenderRevisionAddParserOutput'][] = array('DiffHooks::onDifferenceEngineRenderRevisionAddParserOutput');
$wgHooks['DifferenceEngineShowEmptyOldContent'][] = array('DiffHooks::onDifferenceEngineShowEmptyOldContent');


//
// Email-related hooks
//
$wgHooks['FilterOutBouncingEmails'][] = array( 'EmailBounceHooks::onFilterOutBouncingEmails' );
$wgHooks['AppendUnsubscribeLinkToBody'][] = array( 'EmailNotificationHooks::appendUnsubscribeLinkToBody' );

// hook for unit testing
$wgHooks['UnitTestsList'][] = array('ImageHooks::onUnitTestsList');
