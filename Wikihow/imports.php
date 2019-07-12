<?php
#
# wikiHow Extensions
#

require_once("$IP/extensions/wikihow/ext-utils/ExtAutoload.php");
require_once("$IP/extensions/wikihow/Misc.php");
require_once("$IP/extensions/wikihow/CommonModules.php");
require_once("$IP/extensions/wikihow/statsd/WikihowStatsd.php");

# English-specific extensions
if ($wgLanguageCode == 'en') {
	require_once("$IP/extensions/wikihow/dedup/DedupTool.php");
	require_once("$IP/extensions/wikihow/wikigame/WikiGame.php");
	require_once("$IP/extensions/wikihow/FeaturedContributor.php");
	require_once("$IP/extensions/wikihow/rctest/RCTest.php");
	require_once("$IP/extensions/wikihow/rctest/RCTestGrader.php");
	require_once("$IP/extensions/wikihow/rctest/RCTestAdmin.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsUp.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsNotifications.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsEmailNotifications.php");
	require_once("$IP/extensions/wikihow/HAWelcome/HAWelcome.php");
	require_once("$IP/extensions/wikihow/WikitextDownloader.php");
	require_once("$IP/extensions/wikihow/video/Videoadder.php");
	require_once("$IP/extensions/wikihow/thumbratings/ThumbRatings.php");
	require_once("$IP/extensions/wikihow/wap/WAP.php");
	//require_once("$IP/extensions/wikihow/mobile_app_cta/MobileAppCTA.php");
	require_once("$IP/extensions/wikihow/reverification/Reverification.php");
	require_once("$IP/extensions/wikihow/concierge/Concierge.php");
	require_once("$IP/extensions/wikihow/babelfish/Babelfish.php");
	require_once("$IP/extensions/wikihow/editfish/Editfish.php");
	require_once("$IP/extensions/wikihow/chocofish/Chocofish.php");
	require_once("$IP/extensions/wikihow/titlefish/Titlefish.php");
	require_once("$IP/extensions/wikihow/retranslatefish/Retranslatefish.php");
	require_once("$IP/extensions/wikihow/bots/messenger_bot/MessengerBot.php");
	require_once("$IP/extensions/wikihow/bots/alexa_skills/AlexaSkills.php");
	require_once("$IP/extensions/wikihow/tipsandwarnings/TipsAndWarnings.php");
	require_once("$IP/extensions/wikihow/tipsandwarnings/TipsPatrol.php");
	require_once("$IP/extensions/wikihow/docviewer/DocViewer.php");
	require_once("$IP/extensions/wikihow/articlecreator/ArticleCreator.php");
	require_once("$IP/extensions/wikihow/imagefeedback/ImageFeedback.php");
	require_once("$IP/extensions/wikihow/tipsandwarnings/TPCoachAdmin.php");
	require_once("$IP/extensions/wikihow/apiappsupport/APIAppAdmin.php");
	require_once("$IP/extensions/EventLogging/EventLogging.php");
	require_once("$IP/extensions/GuidedTour/GuidedTour.php");
	require_once("$IP/extensions/wikihow/rclite/RCLite.php");
	require_once("$IP/extensions/wikihow/AdminUnlinkSocial/AdminUnlinkSocial.php");
	require_once("$IP/extensions/wikihow/sherlock/SpecialSherlock.php");
	require_once("$IP/extensions/wikihow/answerquestions/AnswerQuestions.php");

	if ($wgIsDaikonDomain || $wgIsDevServer) {
		require_once("$IP/extensions/wikihow/ContentPortal/ContentPortal.php");
	}

	// Limit to dev and parsnip as this is an internal tool only
	if ($wgIsToolsServer || $wgIsDevServer) {
		require_once("$IP/extensions/wikihow/moneybags/MoneyBags.php");
	}

	require_once("$IP/extensions/wikihow/guidededitor/GuidedEditor.php");
	require_once("$IP/extensions/wikihow/AdminImageRemoval.php");
	require_once("$IP/extensions/wikihow/ucipatrol/UCIPatrol.php");
	require_once("$IP/extensions/wikihow/spelltool/Spellchecker.php");
	require_once("$IP/extensions/wikihow/category_guardian/CategoryGuardian.php");
	require_once("$IP/extensions/wikihow/usercompletedimages/UserCompletedImages.php");
	require_once("$IP/extensions/wikihow/plants/Plants.php");
	require_once("$IP/extensions/wikihow/qc/TipsGuardian.php");
	require_once("$IP/extensions/wikihow/admintools/AdminLatestRevision.php");
	require_once("$IP/extensions/wikihow/unitguardian/UnitGuardian.php");
	require_once("$IP/extensions/wikihow/stepeditor/StepEditor.php");
	require_once("$IP/extensions/wikihow/toolinfo/ToolInfo.php");
	require_once("$IP/extensions/wikihow/WikiVisualLibrary/WikiVisualLibrary.php");
	require_once("$IP/extensions/wikihow/SearchAd/SearchAd.php");
	require_once("$IP/extensions/wikihow/EditorInvoiceNotifier/EditorInvoiceNotifier.php");
	require_once("$IP/extensions/wikihow/QAPatrol/QAPatrol.php");
	require_once("$IP/extensions/wikihow/Ouroboros/Ouroboros.php");
	require_once("$IP/extensions/wikihow/qafilter/QAFilter.php");
	require_once("$IP/extensions/wikihow/qabox/QABox.php");
	require_once("$IP/extensions/wikihow/sort_questions/SortQuestions.php");
	require_once("$IP/extensions/wikihow/AnswerResponse/AnswerResponse.php");
	require_once("$IP/extensions/wikihow/MobileMenuFlag/MobileMenuFlag.php");
	require_once("$IP/extensions/wikihow/admintools/AdminIntroSummary.php");
	require_once("$IP/extensions/wikihow/quiz/Quiz.php");
	require_once("$IP/extensions/wikihow/TechArticle/TechArticle.php");
	require_once("$IP/extensions/wikihow/SheetInvoicing/SheetInvoicing.php");
	require_once("$IP/extensions/wikihow/AdminCloseAccount/AdminCloseAccount.php");
	require_once("$IP/extensions/wikihow/SensitiveArticle/SensitiveArticle.php");
	require_once("$IP/extensions/wikihow/techlayout/TechLayout.php");
	require_once("$IP/extensions/wikihow/duptool/DuplicateTitles.php");
	require_once("$IP/extensions/wikihow/charity/Charity.php");
	require_once("$IP/extensions/wikihow/TwitterReport/TwitterReport.php");
	require_once("$IP/extensions/wikihow/FlaggedAnswers/FlaggedAnswers.php");
	require_once("$IP/extensions/wikihow/WinterSurvivalGuide/WinterSurvivalGuide.php");
	require_once("$IP/extensions/wikihow/Hypothesis/Hypothesis.php");
	require_once("$IP/extensions/wikihow/Honeypot/Honeypot.php");
	require_once("$IP/extensions/wikihow/AsyncHttp.php");
	require_once("$IP/extensions/wikihow/VideoBrowser/VideoBrowser.php");
	require_once("$IP/extensions/wikihow/HighSchoolHacks/HighSchoolHacks.php");
	require_once("$IP/extensions/wikihow/BibleCitation/BibleCitation.php");
	require_once("$IP/extensions/wikihow/contribute/Contribute.php");
	require_once("$IP/extensions/wikihow/admintools/AdminImageLists.php");
}

if ($wgLanguageCode == "zh") {
	require_once("$IP/extensions/wikihow/chinesevariantselector/ChineseVariantSelector.php");
}

require_once("$IP/extensions/wikihow/DupImage.php");
require_once("$IP/skins/WikihowDesktopSkin.php");
require_once("$IP/extensions/wikihow/MemStaticBagOStuff.php");
require_once("$IP/extensions/wikihow/whredis/WHRedis.php");
require_once("$IP/extensions/wikihow/risingstar/RisingStar.php");
require_once("$IP/extensions/wikihow/lightbox/Lightbox.php");
require_once("$IP/extensions/wikihow/mobile_tool_common/MobileToolCommon.php");
require_once("$IP/extensions/wikihow/mobile_tool_common/ArticleDisplayWidget.php");
require_once("$IP/extensions/wikihow/mobile_tool_common/MobileToolCommon.php");
require_once("$IP/extensions/wikihow/ext-utils/ExtAutoload.php");
require_once("$IP/extensions/wikihow/UserTrustStats.php");
require_once("$IP/extensions/wikihow/load_images/DeferImages.php");
require_once("$IP/extensions/wikihow/PinterestMod.php");
require_once("$IP/extensions/wikihow/android_helper/AndroidHelper.php");
require_once("$IP/extensions/wikihow/ios_helper/IOSHelper.php");
require_once("$IP/extensions/wikihow/usage_logs/UsageLogs.php");
require_once("$IP/extensions/wikihow/MassEdit/AdminMassEdit.php");
require_once("$IP/extensions/MobileFrontend/MobileFrontend.php" );
require_once("$IP/extensions/wikihow/qadomain/QADomain.php");
require_once("$IP/extensions/wikihow/MobileFrontendWikihow/MobileFrontendWikihow.php");
require_once("$IP/extensions/wikihow/mobile/WikihowMobileTools.php");
require_once("$IP/extensions/wikihow/nab/Newarticleboost.php");
require_once("$IP/extensions/wikihow/wikihowAds/AdminAdExclusions/AdminAdExclusions.php");
require_once("$IP/extensions/wikihow/whvid/WHVid.php");
require_once("$IP/extensions/wikihow/translateeditor/TranslateEditor.php");
require_once("$IP/extensions/wikihow/QuickEdit.php");
require_once("$IP/extensions/wikihow/ext-utils/ExtUtils.php");
require_once("$IP/extensions/wikihow/qa/QA.php");
require_once("$IP/extensions/wikihow/utils/Utils.php");
require_once("$IP/extensions/wikihow/video/Importvideo.php");
require_once("$IP/extensions/Scribunto/Scribunto.php");
require_once("$IP/extensions/CheckUser/CheckUser.php");
require_once("$IP/extensions/SpamBlacklist/SpamBlacklist.php");
require_once("$IP/extensions/wikihow/WikihowImagePage/WikihowImagePage.php");
require_once("$IP/extensions/wikihow/WikihowUserPage/WikihowUserPage.php");
require_once("$IP/extensions/Cite/Cite.php");
require_once("$IP/extensions/AntiSpoof/AntiSpoof.php");
require_once("$IP/extensions/Drafts/Drafts.php");
require_once("$IP/extensions/ImageMap/ImageMap.php");
require_once("$IP/extensions/wikihow/EasyTemplate.php");
require_once("$IP/extensions/wikihow/Articlestats.php");
require_once("$IP/extensions/wikihow/PatrolCount/PatrolCount.php");
require_once("$IP/extensions/wikihow/PatrolHelper.php");
require_once("$IP/extensions/wikihow/search/LSearch.php");
require_once("$IP/extensions/wikihow/search/GoogSearch.php");
require_once("$IP/extensions/wikihow/search/SearchBox.php");
require_once("$IP/extensions/wikihow/Newcontributors.php");
require_once("$IP/extensions/wikihow/TitleSearch.php");
require_once("$IP/extensions/wikihow/ThankAuthors/ThankAuthors.php");
require_once("$IP/extensions/wikihow/createpage/CreatePage.php");
require_once("$IP/extensions/wikihow/TwitterFeed/TwitterFeed.php");
require_once("$IP/extensions/wikihow/Standings.php");
require_once("$IP/extensions/wikihow/qc/QC.php");
require_once("$IP/extensions/wikihow/Unguard.php");
require_once("$IP/extensions/wikihow/wikihowtoc/WikihowToc.php");
require_once("$IP/extensions/wikihow/CreateEmptyIntlArticle/CreateEmptyIntlArticle.php");
if ($wgLanguageCode == 'en') {
	require_once("$IP/extensions/wikihow/Vanilla/Vanilla.php");
}
require_once("$IP/extensions/wikihow/VanillaProxyConnect/ProxyConnect.php");
require_once("$IP/extensions/wikihow/unpatrol/Unpatrol.php");
require_once("$IP/extensions/wikihow/rcpatrol/RCPatrol.php");
require_once("$IP/extensions/wikihow/fblogin/FBLink.php");
require_once("$IP/extensions/wikihow/fblogin/FBLogin.php");
require_once("$IP/extensions/wikihow/GPlusLogin/GPlusLogin.php");
require_once("$IP/extensions/wikihow/civic_login/CivicLogin.php");
require_once("$IP/extensions/wikihow/WikihowArticle.php");
require_once("$IP/extensions/wikihow/Wikitext.class.php");
require_once("$IP/extensions/wikihow/RobotPolicy.class.php");
require_once("$IP/extensions/wikihow/tags/ArticleTags.php");
require_once("$IP/extensions/wikihow/WikiPhoto.php");
require_once("$IP/extensions/wikihow/FBAppContact.php");
require_once("$IP/extensions/wikihow/categories/Categorylisting.php");
require_once("$IP/extensions/wikihow/Randomizer.php");
require_once("$IP/extensions/wikihow/Generatefeed.php");
require_once("$IP/extensions/wikihow/ToolbarHelper.php");
require_once("$IP/extensions/wikihow/Sitemap.php");
require_once("$IP/extensions/wikihow/suggestedtopics/SuggestedTopics.php");
require_once("$IP/extensions/wikihow/MWMessages.php");
require_once("$IP/extensions/wikihow/Rating/Rating.php");
require_once("$IP/extensions/wikihow/SpamDiffTool.php");
require_once("$IP/extensions/wikihow/Bunchpatrol.php");
require_once("$IP/extensions/wikihow/MultipleUpload.php");
require_once("$IP/extensions/wikihow/FormatEmail/FormatEmail.php");
require_once("$IP/extensions/wikihow/MagicArticlesStarted.php");
require_once("$IP/extensions/wikihow/PostComment/SpecialPostComment.php");
require_once("$IP/extensions/Renameuser/SpecialRenameuser.php");
require_once("$IP/extensions/wikihow/categories/Categoryhelper.php");
require_once("$IP/extensions/wikihow/categories/Categories.php");
require_once("$IP/extensions/wikihow/categories/admin/AdminCategoryDescriptions.php");
require_once("$IP/extensions/wikihow/AddRelatedLinks.php");
require_once("$IP/extensions/wikihow/ManageRelated/ManageRelated.php");
require_once("$IP/extensions/wikihow/Changerealname.php");
require_once("$IP/extensions/ConfirmEdit/ConfirmEdit.php");
require_once("$IP/extensions/ConfirmEdit/FancyCaptcha.php");
require_once("$IP/extensions/ParserFunctions/ParserFunctions.php");
require_once("$IP/extensions/wikihow/AutotimestampTemplates.php");
require_once("$IP/extensions/wikihow/popbox/PopBox.php");
require_once("$IP/extensions/wikihow/video/EmbedVideo.php");
require_once("$IP/extensions/wikihow/catsearch/CatSearch.php");
require_once("$IP/extensions/wikihow/catsearch/CatSearchUI.php");
require_once("$IP/extensions/wikihow/cattool/Categorizer.php");
//require_once("$IP/extensions/wikihow/adblock_notice/AdblockNotice.php");
require_once("$IP/extensions/wikihow/articledata/ArticleData.php");
require_once("$IP/extensions/wikihow/catsearch/CategoryInterests.php");
require_once("$IP/extensions/wikihow/Mypages.php");
require_once("$IP/extensions/wikihow/hooks/WikihowHooks.php");
require_once("$IP/extensions/wikihow/Wikihow_i18n.class.php");
require_once("$IP/extensions/wikihow/HtmlSnips.class.php");
require_once("$IP/extensions/wikihow/FeaturedArticles.php");
require_once("$IP/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php");
require_once("$IP/extensions/wikihow/Welcome.php");
require_once("$IP/extensions/wikihow/authors/AuthorEmailNotification.php");
require_once("$IP/extensions/wikihow/avatar/Avatar.php");
require_once("$IP/extensions/wikihow/profilebox/ProfileBox.php");
require_once("$IP/extensions/wikihow/QuickNoteEdit.php");
require_once("$IP/extensions/wikihow/imageupload/ImageUpload.php");
require_once("$IP/extensions/wikihow/leaderboard/Leaderboard.php");
require_once("$IP/extensions/wikihow/FollowWidget.php");
require_once("$IP/extensions/wikihow/mobileslideshow/MobileSlideshow.php");
require_once("$IP/extensions/wikihow/relatedwikihows/RelatedWikihows.class.php");
require_once("$IP/extensions/wikihow/motiontostatic/MotionToStatic.class.php");
require_once("$IP/extensions/wikihow/modal/WikihowModal.php");
require_once("$IP/extensions/wikihow/TopAnswerers/TopAnswerers.php");
require_once("$IP/extensions/wikihow/keywordtool/SearchVolume.php");
require_once("$IP/extensions/wikihow/jatrending/JaTrending.php");

// We create a triaged form of wikiHow if WIKIHOW_LIMITED is defined
// in LocalSettings.php, which requires fewer resources and pings
// our servers less.
if (!defined('WIKIHOW_LIMITED')) {
	require_once("$IP/extensions/wikihow/rcwidget/RCWidget.php");
	require_once("$IP/extensions/wikihow/RCBuddy.php");
	require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.php");
	require_once("$IP/extensions/wikihow/stu/StuLogger.php");
	require_once("$IP/extensions/wikihow/pagestats/Pagestats.php");
} else {
	if (strpos(@$_SERVER['REQUEST_URI'], '/Special:Stu?') === 0) {
		header('HTTP/1.1 409 Not implemented');
		print "Not available";
		exit;
	}
}

require_once("$IP/extensions/wikihow/StatsList.php");
require_once("$IP/extensions/wikihow/AdminResetPassword.php");
require_once("$IP/extensions/wikihow/AdminMarkEmailConfirmed.php");
require_once("$IP/extensions/wikihow/avatar/AdminRemoveAvatar.php");
require_once("$IP/extensions/wikihow/AdminLookupPages.php");
require_once("$IP/extensions/wikihow/AdminRedirects.php");
require_once("$IP/extensions/wikihow/AdminEnlargeImages.php");
require_once("$IP/extensions/wikihow/AdminRatingReasons.php");
require_once("$IP/extensions/wikihow/custom_meta/AdminEditInfo.php");
require_once("$IP/extensions/wikihow/stu/AdminBounceTests.php");
require_once("$IP/extensions/wikihow/AdminSearchResults.php");
require_once("$IP/extensions/wikihow/Bloggers.php");
require_once("$IP/extensions/wikihow/loginreminder/LoginReminder.php");
require_once("$IP/extensions/wikihow/editfinder/EditFinder.php");
require_once("$IP/extensions/wikihow/ctalinks/CTALinks.php");
require_once("$IP/extensions/wikihow/dashboard/AdminCommunityDashboard.php");
require_once("$IP/extensions/wikihow/slider/Slider.php");
require_once("$IP/extensions/wikihow/ProfileBadges.php");
require_once("$IP/extensions/wikihow/ImageHelper/ImageHelper.php");
require_once("$IP/extensions/wikihow/ImageCaptions.php");
require_once("$IP/extensions/wikihow/nfd/NFDGuardian.php");
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");
require_once("$IP/extensions/wikihow/CustomTitle/CustomTitle.php");
require_once("$IP/extensions/wikihow/GoodRevision.class.php");
require_once("$IP/extensions/wikihow/DailyEdits.php");
require_once("$IP/extensions/wikihow/ArticleWidgets/ArticleWidgets.php");
require_once("$IP/extensions/wikihow/ToolSkip.php");
require_once("$IP/extensions/wikihow/wikihowAds/wikihowAds.class.php");
require_once("$IP/extensions/wikihow/wikihowAds/DesktopAds.php");
require_once("$IP/extensions/wikihow/WikihowShare/WikihowShare.php");
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");
require_once("$IP/extensions/wikihow/WikihowUser.php");
require_once("$IP/extensions/wikihow/Alien.php");
require_once("$IP/extensions/wikihow/authors/ArticleAuthors.php");
require_once("$IP/extensions/wikihow/NewlyIndexed.class.php");
require_once("$IP/extensions/wikihow/AdminAnomalies.php");
require_once("$IP/extensions/wikihow/TranslationLink.php");
require_once("$IP/extensions/wikihow/translationlinkoverride/TranslationLinkOverride.php");
require_once("$IP/extensions/wikihow/reverttool/RevertTool.php");
require_once("$IP/extensions/wikihow/AdminSamples.php");
require_once("$IP/extensions/wikihow/custom_meta/AdminCustomMeta.php");
require_once("$IP/extensions/wikihow/quizzes/Quizzes.php");
require_once("$IP/extensions/wikihow/WikihowUserPage/UserPagePolicy.php");
require_once("$IP/extensions/wikihow/alfredo/Alfredo.php");
require_once("$IP/extensions/wikihow/schema/SchemaMarkup.php");
require_once("$IP/extensions/wikihow/AdminUserCompletedImages.php");
require_once("$IP/extensions/wikihow/AdminClearRatings.php");
require_once("$IP/extensions/wikihow/AdminCopyCheck.php");
require_once("$IP/extensions/wikihow/AdminReadabilityScore.php");
require_once("$IP/extensions/wikihow/WelcomeWagon/WelcomeWagon.php");
require_once("$IP/extensions/wikihow/interfaceelements/InterfaceElements.php");
require_once("$IP/extensions/wikihow/Watermark.php");
require_once("$IP/extensions/wikihow/ArticleHTMLParser.php");
require_once("$IP/extensions/wikihow/WikiError.php");

if ($wgIsTitusServer || $wgIsDevServer) {
	require_once("$IP/extensions/wikihow/titus/TitusQueryTool.php");
	require_once("$IP/extensions/wikihow/historicalpv/HistoricalPV.php");
	require_once("$IP/extensions/wikihow/titus/ApiTitus.php");
	require_once("$IP/extensions/wikihow/flavius/ApiFlavius.php");
	require_once("$IP/extensions/wikihow/flavius/FlaviusQueryTool.php");
	require_once("$IP/extensions/wikihow/titus/TitusStoredQuery.php");
	require_once("$IP/extensions/wikihow/dedup/Dedup.php");
	require_once("$IP/extensions/wikihow/dedup/CommunityExpert.php");
	require_once("$IP/extensions/wikihow/leonard/Leonard.php");
	require_once("$IP/extensions/wikihow/editcontribution/EditContribution.php");
	require_once("$IP/extensions/wikihow/NABPrioritizer/NABPrioritizer.php");
	require_once("$IP/extensions/wikihow/atlas/AtlasAdmin.php");
	require_once("$IP/extensions/wikihow/keywordsearch/KeywordSearch.php");
	require_once("$IP/extensions/wikihow/rollouttool/RolloutTool.php");
	require_once("$IP/extensions/wikihow/samplepv/SamplePV.php");
	require_once("$IP/extensions/wikihow/domitian/Domitian.php");
	require_once("$IP/extensions/wikihow/turker/Turker.php");
	require_once("$IP/extensions/wikihow/turker/EditTurk.php");
	require_once("$IP/extensions/wikihow/keywordtool/Keywordtool.php");
	require_once("$IP/extensions/wikihow/aqrater/AQRater.php");
	require_once("$IP/extensions/wikihow/mmk/AdminMMKQueries.php");
	require_once("$IP/extensions/wikihow/classify_titles/ClassifyTitles.php");
	require_once("$IP/extensions/wikihow/mmk/MMKManager.php");
    require_once("$IP/extensions/wikihow/DupTitleChecker/DupTitleChecker.php");
    require_once("$IP/extensions/wikihow/TQualManager/TQualManager.php");
}

# REDESIGN 2013
require_once("$IP/extensions/wikihow/userloginbox/UserLoginBox.php");
require_once("$IP/extensions/wikihow/homepage/WikihowHomepage.php");
require_once("$IP/extensions/wikihow/homepage/WikihowHomepageAdmin.php");
require_once("$IP/extensions/wikihow/categories/WikihowCategoryPage.php");
require_once("$IP/extensions/wikihow/ArticleViewer/WikihowArticleStream.php");
require_once("$IP/extensions/wikihow/ArticleViewer/ArticleViewer.php");
require_once("$IP/extensions/wikihow/Notifications.class.php");
require_once("$IP/extensions/wikihow/userstaffwidget/UserStaffWidget.php");
require_once("$IP/extensions/wikihow/optimizely/OptimizelyPageSelector.php");
require_once("$IP/extensions/wikihow/accountcreationfilter/AccountCreationFilter.php");
require_once("$IP/extensions/wikihow/categories/CategoryNames.php");

# UPGRADE 1.23
require_once("$IP/extensions/wikihow/WikihowLogin/WikihowLogin.php");
require_once("$IP/extensions/wikihow/MassMessage/MassMessage.php");
require_once("$IP/extensions/Echo/Echo.php");
require_once("$IP/extensions/wikihow/EchoWikihow/EchoWikihow.php");
require_once("$IP/extensions/wikihow/WikihowPreferences/WikihowPreferences.php");

# API extensions
require_once("$IP/extensions/wikihow/api/ApiCategoryListing.php");
require_once("$IP/extensions/wikihow/api/ApiApp.php");
require_once("$IP/extensions/wikihow/api/ApiRDSlag.php");
require_once("$IP/extensions/wikihow/api/ApiGraphs.php");
require_once("$IP/extensions/wikihow/api/ApiSmsListing.php");
require_once("$IP/extensions/wikihow/api/ApiSchemaMarkup.php");
require_once("$IP/extensions/wikihow/api/ApiSummaryVideos.php");
require_once("$IP/extensions/wikihow/api/ApiSummarySection.php");
require_once("$IP/extensions/wikihow/api/ApiRelatedArticles.php");

if (in_array($wgLanguageCode, $wgActiveAlexaApiLanguages)) {
	require_once("$IP/extensions/wikihow/bots/Bots.php");
	require_once("$IP/extensions/wikihow/articletext/ArticleText.php");
	require_once("$IP/extensions/wikihow/api/ApiArticleText.php");
	require_once("$IP/extensions/wikihow/api/ApiTitleSearch.php");
}

require_once("$IP/extensions/wikihow/s3images/S3Images.php");
require_once("$IP/extensions/AbuseFilter/AbuseFilter.php");
require_once("$IP/extensions/wikihow/email/UnsubscribeEmails.php");
require_once("$IP/extensions/wikihow/SubscriptionManager/SubscriptionManager.php");
require_once("$IP/extensions/wikihow/PatrolThrottle/PatrolThrottle.php");
require_once("$IP/extensions/wikihow/common/ExternalModules.php");
require_once("$IP/extensions/wikihow/talkpages/Talkpage.php");
require_once("$IP/extensions/wikihow/ExternalRecommendedArticles/ExternalRecommendedArticles.php");
require_once("$IP/extensions/wikihow/instantarticles/AdminInstantArticles.php");

require_once("$IP/extensions/wikihow/socialstamp/SocialStamp.php");
require_once("$IP/extensions/wikihow/socialproof/SocialProof.php");
require_once("$IP/extensions/wikihow/tabs/Tabs.php");
require_once("$IP/extensions/wikihow/socialproof/ArticleVerifyReview.php");
require_once("$IP/extensions/wikihow/socialproof/CoauthorSheets/CoauthorSheets.php");
require_once("$IP/extensions/wikihow/socialproof/VerifyData.php");
require_once("$IP/extensions/wikihow/socialproof/AdminExpertDoc/AdminExpertDoc.php");
require_once("$IP/extensions/wikihow/socialproof/AdminExpertNameChange/AdminExpertNameChange.php");
require_once("$IP/extensions/wikihow/socialproof/AdminSocialProof/AdminSocialProof.php");
require_once("$IP/extensions/wikihow/socialproof/AdminVerifyReview/AdminVerifyReview.php");
require_once("$IP/extensions/wikihow/socialproof/AdminCoauthorIntl/AdminCoauthorIntl.php");

# Elastic search requirements
require_once "$IP/extensions/Elastica/Elastica.php";
require_once "$IP/extensions/CirrusSearch/CirrusSearch.php";
require_once("$IP/extensions/wikihow/finner/Finner.php");

require_once("$IP/extensions/wikihow/ArticleReviewers/ArticleReviewers.php");
require_once("$IP/extensions/wikihow/pagehelpfulness/PageHelpfulness.php");
require_once("$IP/extensions/wikihow/nab/AdminNabAtlasList.php");
require_once("$IP/extensions/Nuke/Nuke.php");

require_once("$IP/extensions/wikihow/NoScriptHomepage/Hello.php");
require_once("$IP/extensions/wikihow/ataglance/AtAGlance.php");

require_once("$IP/extensions/Math/Math.php");

require_once("$IP/extensions/wikihow/eoq/EndOfQueue.php");
require_once("$IP/extensions/wikihow/FastlyAction.php");
require_once("$IP/extensions/wikihow/category_contacts/CategoryContacts.php");
require_once("$IP/extensions/wikihow/GooglePresentationTag.php");
require_once("$IP/extensions/wikihow/ListDemotedArticles/ListDemotedArticles.php");
require_once("$IP/extensions/wikihow/VideoEmbedHelperTool/VideoEmbedHelperTool.php");
require_once("$IP/extensions/wikihow/googleamp/GoogleAmp.php");
require_once("$IP/extensions/wikihow/alternatedomain/AlternateDomain.php");
require_once("$IP/extensions/wikihow/UserTiming/UserTiming.php");
require_once("$IP/extensions/wikihow/SocialAuth/SocialAuth.php");
require_once("$IP/extensions/wikihow/SocialLogin/SocialLogin.php");
require_once("$IP/extensions/wikihow/pagepolicy/PagePolicy.php");

require_once("$IP/extensions/wikihow/MethodHelpfulness/MethodHelpfulness.php");
require_once("$IP/extensions/wikihow/imagecaption/ImageCaption.php");
require_once("$IP/extensions/wikihow/ReindexedPages/ReindexedPages.php");
require_once("$IP/extensions/wikihow/EditMapper/EditMapper.php");
require_once("$IP/extensions/wikihow/alternatedomain/TermsOfUse.php");
require_once("$IP/extensions/wikihow/specialtechfeedback/SpecialTechFeedback.php");
require_once("$IP/extensions/wikihow/specialarticlefeedback/SpecialArticleFeedback.php");
require_once("$IP/extensions/wikihow/specialtechverify/SpecialTechVerify.php");
require_once("$IP/extensions/wikihow/specialtechverify/SpecialTechVerifyAdmin.php");
require_once("$IP/extensions/wikihow/specialfred/SpecialFred.php");
require_once("$IP/extensions/wikihow/UserDisplayCache.class.php");
require_once("$IP/extensions/wikihow/userreview/UserReview.php");
require_once("$IP/extensions/wikihow/userreview/UserReviewForm/UserReviewForm.php");
require_once("$IP/extensions/wikihow/GDPR/GDPR.php");
require_once("$IP/extensions/wikihow/GreenBox/GreenBox.php");
require_once("$IP/extensions/wikihow/SocialFooter/SocialFooter.php");
require_once("$IP/extensions/wikihow/PressBoxes/PressBoxes.php");
require_once("$IP/extensions/wikihow/WikihowNamespacePages/WikihowNamespacePages.php");
require_once("$IP/extensions/wikihow/Summary/Summary.php");
require_once("$IP/extensions/wikihow/specialbotblockipwhitelist/BotBlockIPWhitelist.php");
