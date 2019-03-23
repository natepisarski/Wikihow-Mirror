<?php
/*
CREATE TABLE `amazon_affiliates` (
	`aa_id` INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	`aa_page_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	`aa_keywords` VARBINARY(255) NOT NULL DEFAULT '',
	`aa_link_id` VARBINARY(255) NOT NULL DEFAULT '',
	`aa_search_term` VARBINARY(255) NOT NULL DEFAULT '',
	KEY (`aa_page_id`)
);
*/

class AmazonAffiliates {
	const AMAZON_AFFILIATES_TABLE = 'amazon_affiliates';
	const AMAZON_AFFILIATES_TAG = 'amazon_affiliates';
	const MAXIMUM_LINKS = 2;
	const TOKEN_STRING = 'TOKE_';

	const ONELINK_TAG_US = 'wikihow0b-20';

	private static $links_added = false;
	private static $is_valid = null;

	public static function addLinks(Title $title) {
		$page_id = !empty($title) ? $title->getArticleId() : null;
		if (empty($page_id)) return;

		$res = wfGetDB(DB_REPLICA)->select(
			self::AMAZON_AFFILIATES_TABLE,
			[
				'aa_keywords',
				'aa_link_id',
				'aa_search_term'
			],
			[ 'aa_page_id' => $page_id ],
			__METHOD__
		);

		foreach ($res as $row) {
			self::replaceWordWithLink($row->aa_keywords, $row->aa_link_id, $row->aa_search_term);
		}
	}

	//uses phpQuery object
	private static function replaceWordWithLink(string $keywords, string $link_id, string $search_term) {
		$count = 0;

		foreach (pq('.step') as $step) {
			$tokens = [];

			if (stripos(pq($step), $keywords) !== false) {
				$new_step = self::tokenize(pq($step)->html(), $tokens);

				//did we lose our keywords after tokenizing?
				$pos = stripos($new_step, $keywords);

				if ($pos !== false) {
					preg_match('/\b'.preg_quote($keywords).'\b/i', $new_step, $kw);
					if (empty($kw[0])) continue;

					$keywords_in_step = $kw[0];
					$one_link = self::oneLinkLink($keywords_in_step, $link_id, $search_term);

					$new_step = preg_replace('/\b'.$keywords_in_step.'\b/', $one_link, $new_step, $limit = 1);
					$new_step = self::detokenize($new_step, $tokens);

					pq($step)->html($new_step);
					self::$links_added = true;
					$count++;
				}
			}

			if ($count >= self::MAXIMUM_LINKS) break;
		}
	}

	private static function tokenize(string $step_text, array &$tokens): string {
		$ignorables = [
			'(<a.*?a>)',
			'(<sup.*?sup>)'
		];

		return preg_replace_callback(
			'/'.implode('|',$ignorables).'/i',
			function ($m) use (&$tokens) {
				$token = self::TOKEN_STRING . Wikitext::genRandomString();
				$tokens[] = ['token' => $token, 'tag' => $m[0]];
				return $token;
			},
			$step_text
		);
	}

	private static function detokenize(string $step_text, array $tokens): string {
		foreach ($tokens as $t) {
			$step_text = str_replace($t['token'], $t['tag'], $step_text);
		}

		return $step_text;
	}

	public static function oneLinkLink(string $keywords, string $link_id, string $search_term): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets')
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'tag' => self::ONELINK_TAG_US,
			'keywords' => $keywords,
			'link_id' => $link_id,
			'search_term' => $search_term
		];

		return $m->render('one_link_link', $vars);
	}

	public static function oneLinkCodeBlock(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets')
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render('one_link_code_block', []);
	}

	private static function disclaimerHtml(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets')
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'disclaimer' => wfMessage('amazon_affiliates_disclaimer')->parse()
		];

		return $m->render('sidebar_disclaimer', $vars);
	}


	public static function validPage(OutputPage $out): bool {
		if (!is_null(self::$is_valid)) return self::$is_valid;

		self::$is_valid = false;
		$action = Action::getActionName($out->getContext());
		$diff_num = $out->getRequest()->getInt('diff', 0);

		if ($out->getUser()->isAnon() &&
			$out->getTitle()->inNamespace( NS_MAIN ) &&
			$action === 'view' &&
			$diff_num == 0 &&
			!Misc::isAltDomain() &&
			!Misc::isMobileMode())
		{
			$aid = $out->getTitle()->getArticleID();
			self::$is_valid = ArticleTagList::hasTag( self::AMAZON_AFFILIATES_TAG, $aid );
		}

		return self::$is_valid;
	}

	public static function getSidebarDisclaimer(RequestContext $context): string {
		return self::validPage($context->getOutput()) ? self::disclaimerHtml() : '';
	}

	//this uses the phpQuery object already started in WikihowArticleHTML::processArticleHTML()
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		if (self::validPage($out)) {
			//parse the article to add the links
			self::addLinks($out->getTitle());

			if (self::$links_added) {
				//add style
				$out->addModules('ext.wikihow.amazon_affiliates');

				//get amazon's oneLink block of code
				$oneLink_code = self::oneLinkCodeBlock();
				$out->addHtml($oneLink_code);
			}
		}
	}

}
