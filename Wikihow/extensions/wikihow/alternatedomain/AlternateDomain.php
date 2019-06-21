<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionMessagesFiles['AlternateDomain'] = __DIR__ . '/AlternateDomain.i18n.php';

$wgAutoloadClasses['AlternateDomain'] = __DIR__ . '/AlternateDomain.class.php';

$wgHooks['BeforePageDisplay'][] = 'AlternateDomain::onBeforePageDisplay';
$wgHooks['TitleSquidURLs'][] = array('AlternateDomain::onTitleSquidURLs');
$wgHooks['ImageHelperGetThumbnail'][] = 'AlternateDomain::onGetThumbnail';
$wgHooks['RelatedWikihowsBeforeLoadRelatedArticles'][] = 'AlternateDomain::onRelatedWikihowsBeforeLoadRelatedArticles';
$wgHooks['RelatedWikihowsAfterLoadRelatedArticles'][] = 'AlternateDomain::onRelatedWikihowsAfterLoadRelatedArticles';
$wgHooks['WikihowHomepageAfterGetTopItems'][] = 'AlternateDomain::onWikihowHomepageAfterGetTopItems';
$wgHooks['WikihowHomepageFAContainerHtml'][] = 'AlternateDomain::onWikihowHomepageFAContainerHtml';
$wgHooks['WikihowTemplateShowFollowWidget'][] = 'AlternateDomain::onWikihowTemplateShowFollowWidget';
$wgHooks['WikihowTemplateShowFeaturedArticlesSidebar'][] = 'AlternateDomain::onWikihowTemplateShowFeaturedArticlesSidebar';
$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = 'AlternateDomain::onWikihowTemplateShowTopLinksSidebar';
$wgHooks['BeforeInitialize'][] = 'AlternateDomain::onBeforeInitialize';
$wgHooks['BeforePageRedirect'][] = 'AlternateDomain::onBeforePageRedirect';
$wgHooks['RandomizerGetRandomTitle'][] = 'AlternateDomain::onRandomizerGetRandomTitle';
$wgHooks['LSearchRegularSearch'][] = 'AlternateDomain::onLSearchRegularSearch';
$wgHooks['LSearchAfterLocalizeUrl'][] = 'AlternateDomain::onLSearchAfterLocalizeUrl';
$wgHooks['LSearchYahooAfterGetCacheKey'][] = 'AlternateDomain::onLSearchYahooAfterGetCacheKey';
$wgHooks['LSearchBeforeYahooSearch'][] = 'AlternateDomain::onLSearchBeforeYahooSearch';
$wgHooks['WikihowAdsAfterGetTypeTag'][] = 'AlternateDomain::onWikihowAdsAfterGetTypeTag';
$wgHooks['SitemapOutputHtml'][] = 'AlternateDomain::onSitemapOutputHtml';
$wgHooks['WikihowAdsAfterGetMobileAdData'][] = 'AlternateDomain::onWikihowAdsAfterGetMobileAdData';
$wgHooks['WikihowAdsAfterGetCategoryAd'][] = 'AlternateDomain::onWikihowAdsAfterGetCategoryAd';
$wgHooks['MiscGetExtraGoogleAnalyticsCodes'][] = 'AlternateDomain::onMiscGetExtraGoogleAnalyticsCodes';
$wgHooks['WikihowCategoryViewerQueryBeforeProcessTitle'][] = 'AlternateDomain::onWikihowCategoryViewerQueryBeforeProcessTitle';
$wgHooks['WikihowTemplateAfterGetMobileLinkHref'][] = 'AlternateDomain::onWikihowTemplateAfterGetMobileLinkHref';
$wgHooks['WikihowTemplateAfterGetRssLink'][] = 'AlternateDomain::onWikihowTemplateAfterGetRssLink';
$wgHooks['WikihowTemplateAddAndroidAppIndexingLinkTag'][] = 'AlternateDomain::onWikihowTemplateAddAndroidAppIndexingLinkTag';
$wgHooks['WikihowTemplateAddIOSAppIndexingLinkTag'][] = 'AlternateDomain::onWikihowTemplateAddIOSAppIndexingLinkTag';
$wgHooks['ArticleMetaInfoShowTwitterMetaProperties'][] = 'AlternateDomain::onArticleMetaInfoShowTwitterMetaProperties';
$wgHooks['ArticleMetaInfoAddFacebookMetaProperties'][] = 'AlternateDomain::onArticleMetaInfoAddFacebookMetaProperties';
$wgHooks['WikihowTemplateAfterGetLogoLink'][] = 'AlternateDomain::onWikihowTemplateAfterGetLogoLink';
$wgHooks['OutputPageAfterGetHeadLinksArray'][] = 'AlternateDomain::onOutputPageAfterGetHeadLinksArray';
$wgHooks['TranslationLinkAddLanguageLink'][] = 'AlternateDomain::onTranslationLinkAddLanguageLink';
$wgHooks['MessageCache::get'][] = 'AlternateDomain::onMessageCacheGet';
$wgHooks['SetupAfterCache'][] = 'AlternateDomain::onSetupAfterCache';
$wgHooks['WikihowTemplateBeforeCreateLogoImage'][] = 'AlternateDomain::onWikihowTemplateBeforeCreateLogoImage';
$wgHooks['MinervaTemplateWikihowBeforeCreateHeaderLogo'][] = 'AlternateDomain::onMinervaTemplateWikihowBeforeCreateHeaderLogo';
$wgHooks['WikihowTemplateAfterGetMobileUrl'][] = 'AlternateDomain::onWikihowTemplateAfterGetMobileUrl';
$wgHooks['GetTabsArrayShowDiscussTab'][] = 'AlternateDomain::onGetTabsArrayShowDiscussTab';
$wgHooks['WikihowArticleBeforeProcessBody'][] = 'AlternateDomain::onWikihowArticleBeforeProcessBody';
$wgHooks['WikihowMobileSkinAfterPrepareDiscoveryTools'][] = 'AlternateDomain::onWikihowMobileSkinAfterPrepareDiscoveryTools';
$wgHooks['WikihowMobileSkinAfterPreparePersonalTools'][] = 'AlternateDomain::onWikihowMobileSkinAfterPreparePersonalTools';
$wgHooks['HeaderBuilderAfterGetTabsArray'][] = 'AlternateDomain::onHeaderBuilderAfterGetTabsArray';
$wgHooks['ResourceLoaderRegisterModules'][] = 'AlternateDomain::onResourceLoaderRegisterModules';
$wgHooks['HeaderBuilderGetCategoryLinksShowCategoryListing'][] = 'AlternateDomain::onHeaderBuilderGetCategoryLinksShowCategoryListing';
$wgHooks['PagePolicyShowCurrentTitle'][] = 'AlternateDomain::onPagePolicyShowCurrentTitle';

// hooks for no branding site only
$wgHooks['WikihowTemplateAfterCreateNotices'][] = 'AlternateDomain::onWikihowTemplateAfterCreateNotices';
$wgHooks['RelatedWikihowsShowEditLink'][] = 'AlternateDomain::onRelatedWikihowsShowEditLink';
$wgHooks['QABoxAddToArticle'][] = 'AlternateDomain::onQABoxAddToArticle';
$wgHooks['HeaderBuilderAfterGenNavTabs'][] = 'AlternateDomain::onHeaderBuilderAfterGenNavTabs';
$wgHooks['MobileTemplateBeforeRenderFooter'][] = 'AlternateDomain::onMobileTemplateBeforeRenderFooter';
$wgHooks['WikihowTemplateAfterGetTopSearch'][] = 'AlternateDomain::onWikihowTemplateAfterGetTopSearch';
$wgHooks['RelatedWikihowsBeforeGetSectionHtml'][] = 'AlternateDomain::onRelatedWikihowsBeforeGetSectionHtml';
$wgHooks['AddMobileTOCItemData'][] = 'AlternateDomain::onAddMobileTOCItemData';
$wgHooks['GetLinkColours'][] = 'AlternateDomain::onGetLinkColours';
$wgHooks['OutputPageBeforeHTML'][] = 'AlternateDomain::onOutputPageBeforeHTML';
$wgHooks['SpecialPageBeforeExecute'][] = 'AlternateDomain::onSpecialPageBeforeExecute';
$wgHooks['SchemaMarkupAfterGetData'][] = 'AlternateDomain::onSchemaMarkupAfterGetData';
$wgHooks['MinvervaTemplateBeforeRender'][] = 'AlternateDomain::onMinvervaTemplateBeforeRender';
$wgHooks['MinervaTemplateWikihowBeforeRenderShareButtons'][] = 'AlternateDomain::onMinervaTemplateWikihowBeforeRenderShareButtons';
$wgHooks['GoogleAmpAfterGetSlotData'][] = 'AlternateDomain::onGoogleAmpAfterGetSlotData';
$wgHooks['WikihowSkinHelperAfterGetMainPageHtmlTitle'][] = 'AlternateDomain::onWikihowSkinHelperAfterGetMainPageHtmlTitle';
