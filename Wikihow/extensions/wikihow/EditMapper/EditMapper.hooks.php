<?php

namespace EditMapper;

use Content;
use RequestContext;
use Revision;
use Status;
use User;
use WikiPage;

use EditMapper\PortalEditMapper;
use EditMapper\TranslatorEditMapper;

/**
 * Provide hooks to map article edits to different users
 */
class EditMapperHooks {

	protected static $mapper = null;      // string  The subclass that is doing the mapping
	protected static $mappers = null;  	  // array   EditMapper subclasses that may handle the mapping

	/**
	 * Triggered when the software receives a request to save an article
	 */
	public static function onPageContentSave(WikiPage $article, User &$user, Content $content,
			string $comment, int $minor, $null1, $null2, int $flags, Status $status=null) {

		if (!isset($user) || $user->isAnon()) {
			return true;
		}

		$title = $article->getTitle();

		$isNew = $flags & EDIT_NEW;

		list($mapper, $destUser) = static::getActiveMapper($title, $user, $isNew, $comment);
		if ($mapper && $destUser) {
			static::$mapper = $mapper;
			$mapper->doMapping($user, $destUser);
		}

		return true;
	}

	/**
	 * Triggered after the save article request has been processed
	 */
	public static function onPageContentSaveComplete(WikiPage $article, User &$user,
			Content $content, string $summary, int $minor, $null1, $null2, int $flags,
			Revision $revision=null, Status $status=null) {

		if (isset(static::$mapper)) {
			static::$mapper->undoMapping($article, $user);
			static::$mapper = null;
		}

		return true;
	}

 	/**
 	 * If the edit will be/is being mapped, it returns the responsible EditMapper instance
 	 * and the destination User.
 	 *
	 * @return array [EditMapper, User] or [false, false]
	 */
 	public static function getActiveMapper($title, $user, bool $isNew, string $comment = ''): array {
		$mappers = static::getAllEditMappers();
		foreach ($mappers as $mapper) {
			if ($mapper->shouldMapEdit($title, $user, $isNew, $comment)) {
				$destUser = $mapper->getDestUser($title, $isNew);
				if ($destUser) {
					return [ $mapper, $destUser ];
				}
			}
		}
		return [ false, false ];
	}

	/**
	 * Multiple EditMapper instances can be added to map edits under different conditions.
	 * Note that the order of the instances in the array determine their priority.
	 */
	private static function getAllEditMappers(): array {
		static::$mappers = static::$mappers ?? [
			new TranslateSummariesEditMapper(),
			new TranslatorEditMapper(),
			new PortalEditMapper()
		];
		return static::$mappers;
	}

}
