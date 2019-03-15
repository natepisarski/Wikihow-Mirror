<?php

class DesktopTabs extends Tabs {

	public static function modifyDOM() {
		global $wgTitle, $wgOut, $wgRequest;
		if (!self::isTabArticle($wgTitle, $wgOut)) {
			return;
		}
		if ($wgRequest->getVal('action', 'view') != 'view' || $wgRequest->getVal( 'diff' ) ) {
			return;
		}

		$listName = self::getListName($wgTitle);

		call_user_func("self::".$listName."_modifyDOM", $listName);
	}

	function desktop_tag_2_modifyDOM($listName) {
		self::originalModifyDOM($listName);
	}

	function originalModifyDOM($listName) {
		global $wgTitle;

		$tabInfo = self::getTabInfo($wgTitle);
		pq(".section:not('#intro'), #ur_h2, #sp_h2, .articleinfo")->addClass("tab_default tabbed_content");

		$tabHtml = "";
		$defaultIndex = -1;
		$minClasses = "desktop_tab tabs".count($tabInfo);
		foreach ($tabInfo as $index => $info) {
			$class = $minClasses;
			if ($info['classes'] != "default") {
				pq($info['classes'])->removeClass("tab_default")->addClass("tab_{$index} tabbed_content");
			} else {
				$class .= " desktop_tab_default";
				$defaultIndex = $index;
			}
			if ($index == 0) {
				$class .= " first active";
			} else {
				$class .= " inactive";
			}

			$countString = "";
			if (array_key_exists("count", $info)) {
				$count = pq($info['count'])->length;
				$countString = wfMessage("dt_count", $count)->text();
			}

			$tabHtml .= "<a href='#' id='desktop_tab_{$index}' class='{$class}'>" . wfMessage($info['label'], $countString)->text() . "</a>";
		}
		if ($defaultIndex != -1) {
			pq(".tab_default")->addClass("tab_{$defaultIndex}");
		}

		$tabHtml = "<div id='desktop_tab_outer'><p id='desktop_tab_cta'>" . wfMessage('dt_cta')->text() . "</p><div id='desktop_tab_container' class='{$listName}'>{$tabHtml}</div></div>";
		pq("#intro")->after($tabHtml);
		pq(".desktop_tab:last")->addClass("last");
		pq("#method_toc")->remove();
	}

	function desktop_tag_3_modifyDOM($listName) {
		Mustache_Autoloader::register();
		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__."/templates"),
		);
		$m = new Mustache_Engine($options);

		//move the summary sections down
		$headingsList = ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY);
		$headings = explode("\n", $headingsList);

		$summaryId = "";
		if ($headingsList != "") { //we only want to do this if we've defined the summary sections for this language
			foreach ($headings as $heading) {
				$headingText = WikihowArticleHTML::canonicalizeHTMLSectionName(Misc::getSectionName($heading));

				if (pq(".".$headingText)->length > 0) {
					//move the summary section to the bottom of the steps
					pq(".steps:last")->after(pq("." . $headingText));
					//put an anchor tag before it
					pq(".".$headingText)->before("<a name='" . $headingText . "_sub' class='anchor'></a>");
					//change the section name to match the link at the top
					pq(".".$headingText." .mw-headline")->html(wfMessage("dt_three_firsttab")->text());
					pq(".".$headingText." .m-video-intro-text:first")->html(wfMessage("dt_three_firsttab")->text());

					$summaryId = "#".$headingText."_sub";
					break;
				}
			}
		}

		//get link to first step
		if (pq("#method_toc")->length > 0) {
			$href = pq(".anchor:first")->attr("name");
		} else {
			$href = "steps";
		}
		$data = [
			'id' => $listName,
			'summaryId' => $summaryId,
			'qa' => "#Questions_and_Answers_sub",
			'full' => '#'.$href,
			'firsttab' => wfMessage("dt_three_firsttab")->text(),
			'secondtab' => wfMessage("dt_three_secondtab")->text(),
			'thirdtab' => wfMessage("dt_three_thirdtab")->text(),
			'header' => wfMessage("dt_header")->text()
		];
		$html = $m->render("desktop_tag_3", $data);
		pq("#intro > p:not(#method_toc):first")->before($html);
	}

	public static function isTabArticle($title, $out) {
		if (Misc::isMobileMode() || GoogleAmp::isAmpMode($out)) {
			return false;
		}

		$listName = self::getListName($title);
		return $listName !== false;
	}

	protected static function initConfigLists() {
		self::$configList = ["desktop_tag_1", "desktop_tag_2", "desktop_tag_3"];
		self::$configInfo = [
			"desktop_tag_1" => [
				0 => [
					"classes" => "default",
					"label" => "dt_steps_1",
					"count" => ".steps_list_2 > li"
				],
				1 => [
					"classes" => "#ur_h2, #sp_h2, .articleinfo",
					"label" => "dt_about",
					"count" => ".ur_review"
				]
			],
			"desktop_tag_2" => [
				0 => [
					"classes" => "default",
					"label" => "dt_steps_1",
					"count" => ".steps_list_2 > li"
				],
				1 => [
					"classes" => "", //this is handled below
					"label" => "dt_summary"
				],
				2 => [
					"classes" => ".qa",
					"label" => "dt_qa",
					"count" => "#qa_article_questions > li"
				]
			],
			"desktop_tag_3" => [] //not used for this kind of tabs
		];

		//do the video section headers differently
		$classArray = explode("\n", ConfigStorage::dbGetConfig(Wikitext::SUMMARIZED_HEADINGS_KEY));
		foreach ($classArray as &$sectionName) {
			$sectionName = ".".WikihowArticleHTML::canonicalizeHTMLSectionName($sectionName);
		}
		$classArray[] = ".video";

		self::$configInfo["desktop_tag_2"][1]["classes"] = implode(", ", $classArray);
	}

	public static function addDesktopCSS(&$css, $title) {
		global $IP;

		$listName = self::getListName($title);
		if ($listName !== false) {
			$cssStr = Misc::getEmbedFiles('css', [$IP . "/extensions/wikihow/tabs/styles/" . $listName . ".css"]);
			$cssStr = wfRewriteCSS($cssStr, true);
			$css .= HTML::inlineStyle($cssStr);
		}

		return true;
	}
}
