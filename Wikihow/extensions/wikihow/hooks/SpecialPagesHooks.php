<?php

if (!defined('MEDIAWIKI')) die();

class SpecialPagesHooks {

	// This is a general hook used by any special special page that
	// needs to define itself as a tool.
	public static function defineAsTool(&$isTool) {
		global $wgTitle;
		$isTool = true;
		return true;
	}

	public static function getDeleteReasonFromCode($article, $outputPage, &$defaultReason) {

		$whArticle = WikihowArticleEditor::newFromTitle($article->getTitle());
		$intro = $whArticle->getSummary();
		$matches = array();
		preg_match('/{{nfd.*}}/i', $intro, $matches);

		if (count($matches) && $matches[0] != null) {
			$loc = stripos($matches[0], "|", 4);
			if ($loc) { // there is a reason
				$loc2 = stripos($matches[0], "|", $loc + 1);
				if (!$loc2) {
					$loc2 = stripos($matches[0], "}", $loc + 1);
				}

				// ok now grab the reason
				$nfdreason = substr($matches[0], $loc + 1, $loc2 - $loc - 1);
				$reasons = array(
					'acc' => 'Accuracy',
					'adv' => 'Advertising',
					'cha' => 'Character',
					'dan' => 'Extremely dangerous',
					'dru' => 'Drug-focused',
					'hat' => 'Hate/racist',
					'imp' => 'Impossible',
					'inc' => 'Incomplete',
					'jok' => 'Joke',
					'mea' => 'Mean-spirited',
					'not' => 'Not a how-to',
					'pol' => 'Political opinion',
					'pot' => 'Potty humor',
					'sar' => 'Sarcastic',
					'sex' => 'Sexually explicit',
					'soc' => 'Societal instructions',
					'ill' => 'Universally illegal',
					'van' => 'Vanity pages',
					'dup' => 'Duplicate',
				);
				if ( isset( $reasons[$nfdreason] ) ) {
					$defaultReason = $reasons[$nfdreason];
				}
			}
		}

		return true;
	}

	/*
	 * Styling for the default EditForm
	 */
	public static function onShowEditFormFields(&$editform, &$wgOut) {
		$editform->editFormTextBeforeContent = Html::openElement( 'div', array( 'class' => 'minor_section') );
		$editform->editFormTextAfterContent = Html::closeElement( 'div' );

		if (class_exists('TechArticle\TechArticleWidgetHooks')) { // EN-only
			TechArticle\TechArticleWidgetHooks::addWidgetToEditForm($editform, $wgOut);
		}

		$editform->editFormTextAfterContent .= Html::openElement( 'div', array( 'class' => 'minor_section') );
		$editform->editFormTextAfterTools = Html::closeElement( 'div' );
		$editform->editFormTextAfterTools .= Html::closeElement( 'div' ); //Bebeth adding, not sure exactly why it's needed (seems like an extra </div> but it fixes it.
		$editform->editFormTextAfterTools .= Html::openElement( 'div', array( 'class' => 'minor_section') );
		$editform->editFormTextBottom = Html::closeElement( 'div' );

		return true;
	}

	public static function onBeforeWelcomeCreation(&$welcome_creation_msg, &$injected_html) {
		global $wgRedirectOnLogin, $wgLanguageCode, $wgCookiePrefix, $wgCookiePath, $wgCookieDomain;

		// Mobile does the default
		if (Misc::isMobileMode()) return true;

		if ($wgLanguageCode != "en") {
			$dashboardPage = Title::makeTitle(NS_PROJECT, wfMessage("community")->text());
			$wgRedirectOnLogin = $dashboardPage->getFullText();
		} else {
			$ctx = RequestContext::getMain();
			$returnto = $ctx->getRequest()->getText('returnto');

			if ($returnto) {
				$wgRedirectOnLogin = urldecode($returnto);
			}
			else {
				//set cookie for the expertise modal
				setcookie($wgCookiePrefix.'_exp_modal', '1', time()+3600, $wgCookiePath, $wgCookieDomain);
				$wgRedirectOnLogin = 'Special:CommunityDashboard';
			}
		}

		return true;
	}

	/**
	 * Add 'reverse' option to traditional Special:RecentChanges page.
	 */
	public static function onSpecialRecentChangesPanel(&$extraOpts, $opts) {
		global $wgRequest;
		$reverse = $wgRequest->getInt('reverse');
		$labelText = wfMessage('reverseorder')->text();
		$description = 'Check this box to show recent changes in reverse order';
		$extraOpts['reverse'] = array(
			'<input name="reverse" type="checkbox" value="1" id="nsreverse" title="' . $description . '"' . ($reverse ? ' checked=""' : '') . '>',
			'<label for="nsreverse" title="' . $description . '">' . $labelText . '</label>');
		return true;
	}

	/**
	 * Use 'reverse' option in RecentChanges queries
	 */
	public static function onSpecialRecentChangesQuery($conds, $tables, $join_conds, $opts, $query_options, $fields, &$reverse)
	{
		global $wgRequest;
		$reverseOpt = $wgRequest->getInt('reverse');
		if ($reverseOpt == 1) $reverse = 1;
		return true;
	}

	// Reuben, upgrade 1.21: Special:Mostlinked is expensive, so we make the
	// page contain at most 1000 cached results
	public static function onPopulateWgQueryPages(&$wgQueryPages) {
		foreach ($wgQueryPages as &$page) {
			if ($page[0] == 'MostlinkedPage') {
				$page[2] = 1000;
				break;
			}
		}
		return true;
	}

	// Reuben 3/20: Jenn, through Anna, asked that Special:WantedPages would only
	// contain links from main namespace articles to redlinks in other main
	// namespace articles. This hook accomplishes that.
	public static function onWantedPagesGetQueryInfo(&$specialPage, &$query) {
		$query['conds'] = array(
			'pg1.page_namespace IS NULL',
			"pl_namespace" => NS_MAIN,
			"pg2.page_namespace" => NS_MAIN
		);

		return true;
	}

	// AG - styling for logout page
	public static function onUserLogoutComplete( &$user, &$injected_html, $oldName) {

		//mobile redirects on logout to the previous page
		$ctx = MobileContext::singleton();
		$isMobileMode = $ctx->shouldDisplayMobileView();
		if ($isMobileMode) {
			global $wgRequest, $wgOut;

			$returnToTitle = Title::newFromText( $wgRequest->getVal( 'returnto', '' ) );

			if ( !$returnToTitle ) {
				$returnToTitle = Title::newMainPage();
			}
			$wgOut->redirect($returnToTitle->getFullURL() . '#/loggedout');
		}

		$injected_html.= "
		<style type='text/css'>
		#bodycontents pre {
			font-family: Helvetica, arial, sans-serif;
			-webkit-font-smoothing: antialiased;
			margin-top: 3px;
			margin-bottom: 25px;
		}
		</style>
		";
		return true;
	}

	public static function onWebRequestPathInfoRouter( $router ) {
		$router->addStrict( 'TopicGreenhouse-$1', array( 'title' => 'Special:EditFinder', 'target' => 'Topic', 'topic' => "$1") );
		$router->addStrict( 'wikiHow:Gives-Back', array( 'title' => 'Special:Charity') );
		$router->addStrict( 'wikiHow:Contribute', array( 'title' => 'Special:Contribute') );
		if (QADomain::isQADomain()) {
			$router->addStrict('$1', array('title' => 'Special:QADomain/$1'));
		}
		return true;
	}

}

