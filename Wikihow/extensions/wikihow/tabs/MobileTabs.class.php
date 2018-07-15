<?php

class MobileTabs extends Tabs {

	public static function modifyDom() {
		global $wgTitle, $wgOut, $wgRequest;
		if(!self::isTabArticle($wgTitle, $wgOut)) {
			return;
		}
		if($wgRequest->getVal('action', 'view') != 'view' || $wgRequest->getVal( 'diff' ) ) {
			return;
		}

		$listName = self::getListName($wgTitle);

		call_user_func("self::".$listName."_modifyDOM", $listName);
	}

	private static function originalModifyDOM() {
		global $wgTitle, $wgOut;
		if(!self::isTabArticle($wgTitle, $wgOut)) {
			return;
		}

		$tabInfo = self::getTabInfo($wgTitle);
		$listName = self::getListName($wgTitle);

		pq(".section:not('#intro'), #ur_mobile, #ur_h2, #social_proof_mobile, #sp_h2, .articleinfo")->addClass("tab_default tabbed_content");

		$tabHtml = "";
		$defaultIndex = -1;
		$minClasses = "mobile_tab tabs".count($tabInfo);
		foreach($tabInfo as $index => $info) {
			$class = $minClasses;
			if($info['classes'] != "default") {
				pq($info['classes'])->removeClass("tab_default")->addClass("tab_{$index} tabbed_content");
			} else {
				$class .= " mobile_tab_default";
				$defaultIndex = $index;
			}
			if($index == 0) {
				$class .= " first active";
			} else {
				$class .= " inactive";
			}

			$countString = "";
			if(array_key_exists("count", $info)) {
				$count = pq($info['count'])->length;
				$countString = wfMessage("mt_count", $count)->text();
			}

			$tabHtml .= "<a href='#' id='mobile_tab_{$index}' class='{$class}'>" . wfMessage($info['label'], $countString)->text() . "</a>";
		}
		if($defaultIndex != -1) {
			pq(".tab_default")->addClass("tab_{$defaultIndex}");
		}

		$tabHtml = "<div id='mobile_tab_outer'><p id='mobile_tab_cta'>" . wfMessage('mt_cta')->text() . "</p><div id='mobile_tab_container' class='{$listName}'>{$tabHtml}</div></div>";
		pq("#intro")->after($tabHtml);
		pq(".mobile_tab:last")->addClass("last");
	}

	function mobile_tag_2_modifyDOM($listName) {
		self::originalModifyDOM($listName);
	}

	function mobile_tag_4_modifyDOM($listName) {
		foreach(pq(".steps:not(.sample) h3 .mw-headline") as $index => $header) {
			$headerHtml = pq($header)->html();
			if(strpos($headerHtml, "Part") !== false) {
				$newText = "Part " . ($index+1) . ":";
			} else {
				$newText = "Method " . ($index+1) . ":";
			}
			$headerHtml = $newText . substr($headerHtml, strpos($headerHtml, ":")+1);
			pq($header)->html($headerHtml);
		}

		pq(".steps .edit-page")->before("<a href='#' class='chevron'></a>");
		pq(".section:not(.steps) h2")->prepend("<a href='#' class='chevron'></a>");
		pq(".edit-page")->remove();
		pq(".chevron")->parents(".section")->addClass("closed")->addClass($listName);
		$firstSteps = pq(".steps:first");
		$firstSteps->addClass("open")->removeClass("closed");

		//move the summary sections up
		//move the summary sections down
		$headingsList = ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY);
		$headings = explode("\n", $headingsList);

		if($headingsList != "") { //we only want to do this if we've defined the summary sections for this language
			foreach ($headings as $heading) {
				$headingText = WikihowArticleHTML::canonicalizeHTMLSectionName(Misc::getSectionName($heading));

				if(pq(".".$headingText)->length > 0) {
					//move the summary section to the bottom of the steps
					pq(".steps:first")->before(pq("." . $headingText));
					pq("." . $headingText . " .mw-headline")->html("Quick Summary");
					pq("." . $headingText)->addClass("quicksummary");
					break;
				}
			}
		}

		pq(".section.donate")->remove();
	}

	public static function isTabArticle($title, $out) {
		if(!Misc::isMobileMode() || GoogleAmp::isAmpMode($out)) {
			return false;
		}

		$listName = self::getListName($title);
		return $listName !== false;
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

	public static function addMobileCSS(&$stylePath, $title) {
		global $IP, $wgOut;

		if(!self::isTabArticle($title, $wgOut)) {
			return true;
		}

		$listName = self::getListName($title);
		if($listName !== false) {
			$stylePath[] = $IP . "/extensions/wikihow/tabs/styles/" . $listName . ".css";
			/*$cssStr = Misc::getEmbedFiles('css', []);
			$cssStr = wfRewriteCSS($cssStr, true);
			$css .= HTML::inlineStyle($cssStr);*/
		}

		return true;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$title = $skin->getTitle();

		if(!$title) return;
		if(!Misc::isMobileMode()) return;

		$listName = self::getListName($title);
		if($listName !== false) {
			$out->addModules('ext.wikihow.' . $listName);
		}

		return true;
	}
}
