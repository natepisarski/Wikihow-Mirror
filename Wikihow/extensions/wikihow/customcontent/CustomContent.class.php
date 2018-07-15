<?php

class CustomContent {
	const TAG_NAME = "custom_content_hide";
	
	public static function getPageClass($title) {
		if($title && $title->getArticleId() > 0 && ArticleTagList::hasTag(self::TAG_NAME, $title->getArticleId())) {
			return "customcontent";
		} else {
			return "";
		}
	}
}