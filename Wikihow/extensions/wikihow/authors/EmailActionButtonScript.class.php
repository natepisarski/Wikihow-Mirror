<?php
/********************************************
 * Class that returns the script txt for 	*
 * Google action buttons. Requires the $url	*
 * and/or title obj as a parameter          *
 ********************************************/
class EmailActionButtonScript {

	//returns a script for insertion of an action button in gmail emails. The button's text says 'See my Article"
	public static function getSeeMyArticleScript($url, $title) {
		global $IP;
		require_once("$IP/skins/WikiHowSkin.php");
		$text = wfMessage('aen_btn_view')->text();
		$desc = wfMessage('aen_btn_art_desc')->text();
		$image = ImageHelper::getGalleryImage($title, 300, 200);

		// For testing on dev
		// $image = "http://pad3.whstatic.com" . $image;

		return EmailActionButtonScript::getArticleButtonScript($url, $desc, $title->getText(), $image);
	}

	//returns a script for insertion of an action button in gmail emails. The button's text says 'View Edits"
	public static function getArticleEditedScript($url) {
		$text = wfMessage('aen_btn_edit')->text();
		$desc = wfMessage('aen_btn_diff_desc')->text();
		return EmailActionButtonScript::getActionButtonScript($text, $url, $desc);
	}

	//returns a script for insertion of an action button in gmail emails. The button's text says 'View My Edits"
	public static function getThumbsUpScript($url) {
		$text = wfMessage('aen_btn_thumb')->text();
		$desc = wfMessage('aen_btn_diff_desc')->text();
		return EmailActionButtonScript::getActionButtonScript($text, $url, $desc);
	}

	//returns a script for inserting an action button saying "View my talk page" in gmail.
	public static function getTalkPageScript($url) {
		$text = wfMessage('aen_btn_talk')->text();
		$desc = wfMessage('aen_btn_talk_desc')->text();
		return EmailActionButtonScript::getActionButtonScript($text, $url, $desc);
	}

	//Private function that assembles the script for action buttons.
	private function getActionButtonScript($buttonText, $url, $desc = NULL){
		if ($desc == NULL) {
			$desc = wfMessage('aen_btn_default_desc')->text();
		}
		$text = '<script type="application/ld+json">
		{
			"@context": "http://schema.org",
			"@type": "EmailMessage",
			"potentialAction": {
				"@type": "ViewAction",
				"target": "'.$url.'",
				"name": "'.$buttonText.'",
				"url": "'.$url.'"
			},
			"description": "'.$desc.'"
		}
		</script>';

		return $text;
	}

	//Private function that assembles the script for article buttons
	private function getArticleButtonScript ($url, $desc, $name, $image){
		$text = '<script type="application/ld+json">
		[
		{
			"@context": "http://schema.org",
			"@type": "Article",
			"name": "'.$name.'",
			"url": "'.$url.'",
			"description": "'.$desc.'",
			"image": "'.$image.'",
			"publisher": {
				"@type": "Organization",
				"name": "WikiHow",
				"url": "www.wikihow.com"
			}
		},
		{
			"@context": "http://schema.org",
			"@type": "EmailMessage",
			"action": {
				"@type": "ViewAction",
				"url": "'.$url.'",
				"name": "See My Article"
			}
		}
		]
		</script>';

		return $text;
	}

}

