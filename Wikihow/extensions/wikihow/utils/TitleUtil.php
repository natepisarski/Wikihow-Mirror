<?php

class TitleUtil
{
	/**
	 * Find templates used in the article. E.g. "{{Templ|param1}} lorem ipsum" -> ['templ']
	 */
	public static function getTemplates(Title $title): array
	{
		$revision = Revision::newFromTitle($title);
		if (!$revision)
			return [];

		$res = preg_match_all('/{{([^}]+)}}/', ContentHandler::getContentText( $revision->getContent() ), $matches);
		if (!$res)
			return [];

		$templates = [];
		foreach ($matches[1] as $template) {
			$template = str_replace(':', '|', $template);  // {{Video:Title of Video|}}
			$chunks = explode('|', $template);             // {{stub|date=2016-04-25}}
			$templateName = strtolower(trim($chunks[0]));
			$templates[$templateName] = 1;
		}
		ksort($templates);
		return array_keys($templates);
	}
}
