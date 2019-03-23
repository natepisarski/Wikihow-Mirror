<?php

class MobileTabs extends Tabs {

	public static function modifyDom() {

	}

	public static function addTabsToArticle(&$data) {
		global $wgTitle;

		if(!WHVid::hasSummaryVideo($wgTitle)) return;

		$tabInfo = self::getTabInfo($wgTitle);
		$listName = self::getListName($wgTitle);
		$minClasses = "mobile_tab tabs".count($tabInfo);

		$tabHtml = "<a href='#steps_1' id='mobile_tab_0' class='$minClasses active'>ARTICLE</a><a href='#" . self::getSummarySectionAnchorName() . "' id='mobile_tab_1' class='$minClasses'>VIDEO</a> ";
		$tabHtml = "<div id='mobile_tab_container' class='{$listName}'>{$tabHtml}</div>";

		$data['prebodytext'] .= $tabHtml;
	}

	public static function getSummarySectionAnchorName() {
		global $wgOut;

		if(GoogleAmp::isAmpMode($wgOut)) {
			return "summarysection_anchor";
		} else {
			return "Quick_Summary";
		}
	}

	protected static function initConfigLists() {
		self::$configList = ["mobile_tag_1", "mobile_tag_2", "mobile_tag_3", "mobile_tag_4"];
		self::$configInfo = [
			"mobile_tag_1" => [
				0 => [
					"classes" => "default",
					"label" => "mt_steps_1",
					"count" => ".steps_list_2 > li"
				],
				1 => [
					"classes" => ".qa",
					"label" => "mt_about",
					"count" => ".ur_review"
				]
			],
			"mobile_tag_2" => [
				0 => [
					"classes" => "default",
					"label" => "mt_steps_1",
					"count" => ".steps_list_2 > li"
				],
				1 => [
					"classes" => "", //this is handled below
					"label" => "mt_summary"
				],
				2 => [
					"classes" => ".qa",
					"label" => "mt_qa",
					"count" => "#qa_article_questions > li"
				]
			],
			"mobile_tag_3" => [
				0 => [
					"classes" => "default",
					"label" => "mt_firsttabb",
					"count" => ".steps_list_2 > li"
				],
				1 => [
					"classes" => ".10secondsummary, .inahurry, .video",
					"label" => "mt_secondtabb"
				],
				2 => [
					"classes" => ".qa",
					"label" => "mt_thirdtabb",
					"count" => "#qa_article_questions > li"
				]
			],
			"mobile_tag_4" => []
		];

		//do the video section headers differently
		$classArray = explode("\n", ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY));
		foreach($classArray as &$sectionName) {
			$sectionName = ".".WikihowArticleHTML::canonicalizeHTMLSectionName($sectionName);
		}
		$classArray[] = ".video";

		self::$configInfo["mobile_tag_2"][1]["classes"] = implode(", ", $classArray);
	}

	public static function isTabArticle($title, $out) {
		return WHVid::hasSummaryVideo($title);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$title = $skin->getTitle();

		if (!$title) return;
		if (!Misc::isMobileMode()) return;

		if(!WHVid::hasSummaryVideo($title)) return;

		$out->addModules('ext.wikihow.mobile_tabs');

		return true;
	}
}
