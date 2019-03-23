<?php

abstract class Tabs {
	static $configList = null;
	static $configInfo = null;

	abstract protected static function modifyDOM();
	abstract public static function isTabArticle($title, $out);
	abstract protected static function initConfigLists();

	protected static function getTabInfo($title) {
		$listName = self::getListName($title);
		return self::$configInfo[$listName];
	}

	protected static function getListName($title) {
		if (!$title) {
			return false;
		}
		$articleId = $title->getArticleId();
		if ($articleId <= 0) {
			return false;
		}

		if ( !self::$configList ) {
			static::initConfigLists();
		}
		foreach (self::$configList as $listName) {
			if (ArticleTagList::hasTag($listName, $articleId)) {
				return $listName;
			}
		}
		return false;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if ($skin->getTitle()->inNamespace(NS_MAIN)) {
			$out->addModules('ext.wikihow.tabs');
		}

		return true;
	}
}
