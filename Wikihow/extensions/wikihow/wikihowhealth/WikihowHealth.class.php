<?php

class WikihowHealth {
	const SECTION_NAMES = "health_sections";

	public static function processHealthArticles() {

		//NOT DOING THIS YET
		//first do the intro
		//$introText = pq("#intro p:first-child")->html();
		//pq("#intro p:first-child")->html(self::uppercaseWords($introText, 4));

		if(pq(".steps_text")->length == 0) return;

		//now the steps
		foreach(pq(".steps_text") as $stepText) {
			$step = pq($stepText)->html();
			pq($stepText)->html(self::uppercaseWords($step, 6));
			pq($stepText)->addClass("healthintro");
		}

		//remove the clearall after the last healthintro
		pq(".healthintro:last")->siblings(".clearall")->remove();

		//now look for the final section
		$headers = explode("\n", trim(ConfigStorage::dbGetConfig(self::SECTION_NAMES)));
		foreach($headers as $header) {
			$headingId = WikihowArticleHTML::canonicalizeHTMLSectionName(Misc::getSectionName($header));
			$headingPQ = pq(".$headingId");
			if($headingPQ->length > 0) {
				$headingPQ->addClass("healtharticle")->find("h2")->prepend("<div class='altblock'></div>");
				$headingPQ->insertAfter(pq(".steps:last"));

				WikihowToc::setTakeaway($headingId, $header);
			}
		}

	}

	private static function uppercaseWords($text, $count) {
		$words = explode(" ", $text);
		$startWords = [];
		for($i = 0; $i < $count; $i++) {
			$startWords[] = strtoupper($words[$i]);
		}

		$newText = "<span class='bolded'>" . implode(" ", $startWords) . "</span> " . implode(" ", array_splice($words, $count));

		return $newText;
	}
}
