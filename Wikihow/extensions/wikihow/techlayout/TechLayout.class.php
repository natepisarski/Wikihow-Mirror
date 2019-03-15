<?php


class TechLayout {
	const CONFIG_LIST = "tech_layout_test";

	public static function isTechLayoutTest($title) {
		if (Misc::isMobileMode() || !$title || $title->getArticleId() <= 0) {
			return false;
		}

		return ArticleTagList::hasTag(self::CONFIG_LIST, $title->getArticleId());
	}

	public static function ModifyDom() {
		global $wgTitle;

		if (!self::isTechLayoutTest($wgTitle)) {
			return;
		}

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		$data = self::getStepInfo();

		$steps = $m->render('steps', $data);

		pq(".section.steps")->before($steps);

		//remove the 10 second summary
		pq(".10secondsummary")->remove();
	}

	public static function getStepInfo() {
		$data = ["steps" => [], "navigation" => []];

		$steps = pq(".steps_list_2 > li");
		foreach ($steps as $index => $step) {
			$stepText = pq($step)->find(".step");
			pq($stepText)->find("script")->remove();
			pq($stepText)->find(".reference")->remove();
			$stepInfo = [
				"step_image" => pq(".image", $step)->htmlOuter(),
				"step_num" => ($index+1)
			];
			$navigationInfo = [
				"navText" => pq(".whb", $step)->text(),
				"num" => ($index+1),
				"stepText" => pq($stepText)->text()
			];
			pq($stepText)->find(".whb")->remove();
			$navigationInfo["stepText"] = pq($stepText)->text();
			if ($index == 0) {
				$navigationInfo['class'] = "active";
			} else {
				$navigationInfo['class'] = "inactive";
			}
			$data["steps"][] = $stepInfo;
			$data["navigation"][] = $navigationInfo;
		}

		return $data;
	}

	public static function removeSideBarCallback(&$showSideBar) {
		global $wgTitle, $wgOut;

		if (self::isTechLayoutTest($wgTitle)) {
			$showSideBar = false;
			$wgOut->addModules("ext.wikihow.techlayout");
		}
		return true;
	}

	public static function addCSS(&$css, $title) {
		global $IP;

		if (self::isTechLayoutTest($title)) {
			$cssStr = Misc::getEmbedFiles('css', [$IP . "/extensions/wikihow/techlayout/techlayout.css"]);
			$cssStr = wfRewriteCSS($cssStr, true);
			$css .= HTML::inlineStyle($cssStr);
		}

		return true;
	}
}
