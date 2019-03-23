<?php

class AdminCategoryDescriptions extends UnlistedSpecialPage {
	const CATEGORY_DESCRIPTIONS = "category_descriptions";
	const CACHE_KEY_DESCRIPTION = "cat_desc6";
	const CACHE_LENGTH = 60*60*24*14; //2 weeks

	const MESSAGE_NO_ARTICLES = "Category_meta_description_noarticles";
	const MESSAGE_DESCRIPTION_DEFAULT = "Category_page_description";
	const MESSAGE_DESCRIPTION_WIKIHOW = "Category_meta_description_wikihow";

	public function __construct() {
		global $wgHooks;
		parent::__construct('AdminCategoryDescriptions');
		$wgHooks['ShowSideBar'][] = ['AdminCategoryDescriptions::removeSideBarCallback'];
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		// Check permissions
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$req = $this->getRequest();
		if ($req->wasPosted()) {
			set_time_limit(0);
			$out->setArticleBodyOnly(true);
			$error = "";
			$action = $req->getVal('action');
			if ($action == 'save-list') {
				$filename = $req->getFileTempName('acd_file');
				$ret = $this->processChangesUpload($filename);
				print json_encode( ['results' => $ret] );
			} elseif ($action == 'retrieve-list') {
				$this->downloadCategories();
			} else {
				$error = 'unknown action';
			}
			if ($error) {
				print json_encode(array('error' => $error));
			}
			return;
		}

		$out->addModules( ['wikihow.admincategorydescriptions'] );
		$out->setPageTitle('Admin Category Descriptions');
		$this->displayPage();
	}

	// Callback indicating to remove the right rail
	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	private static function httpDownloadHeaders($filename) {
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="' . $filename . '"');
	}

	private function processChangesUpload($filename) {
		$user = $this->getUser();
		$userId = $user->getId();
		$content = file_get_contents($filename);
		if ($content === false) {
			$error = 'internal error opening uploaded file';
			return array('error' => $error);
		}
		$lines = preg_split('@(\r|\n|\r\n)@m', $content);
		$changes = array();
		$stats = ['badcats' => []];
		foreach ($lines as $line) {
			$fields = explode("\t", $line);
			// skip any line that doesn't have at least a pageid and a custom title/desc
			if (count($fields) < 2) continue;
			$fields = array_map(trim, $fields);
			// skip first line if it's the pageid\t... header
			$categoryUrl = $fields[0];
			$title = Title::newFromText(Misc::fullUrlToPartial($categoryUrl), NS_CATEGORY);
			if (!$title) {
				$stats['badcats'][] = $categoryUrl;
				continue;
			}

			$pageId = $title->getArticleID();
			if ($pageId <= 0) {
				$stats['badcats'][] = $categoryUrl;
				continue;
			}
			$description = trim($fields[1]); // can be the empty string
			$custom_note = count($fields) > 2 ? $fields[2] : '';
			$changes[$pageId] = [
				'cd_page_id' => $pageId,
				'cd_description' => $description,
				'cd_custom_note' => $custom_note,
				'cd_user_id' => $userId
			];
		}
		if (!$changes) {
			return array('error' => 'No lines to process in upload');
		} else {
			return $this->processChanges($changes, $stats);
		}
	}

	private function displayPage() {
		$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$this->getOutput()->addHtml(
			$m->render(
				'admin'
			)
		);
	}

	public function processChanges($changes, &$stats) {
		$dbw = wfGetDB( DB_MASTER );

		$res = $dbw->select( self::CATEGORY_DESCRIPTIONS, array( 'cd_page_id' ), '', __METHOD__ );
		$toDelete = [];
		$cacheClear = [];
		foreach ( $res as $row ) {
			if ( !$changes[$row->cd_page_id] ) {
				$toDelete[] = $row->cd_page_id;
				$cacheClear[] = $row->cd_page_id;
			}
		}

		$stats['update'] = count($changes);
		$stats['delete'] = count($toDelete);
		if (count($toDelete) > 0) {
			$dbw->delete(self::CATEGORY_DESCRIPTIONS, ['cd_page_id  in (' . $dbw->makeList($toDelete) . ')'], __METHOD__);
		}


		$toInsert = [];
		foreach ($changes as $row) {
			$toInsert[] = $row;
			$cacheClear[] = $row['cd_page_id'];
		}
		$dbw->upsert(
			self::CATEGORY_DESCRIPTIONS,
			$toInsert, [],
			[
				'cd_page_id = values(cd_page_id)',
				'cd_description = values(cd_description)',
				'cd_custom_note = values(cd_custom_note)',
				'cd_user_id = values(cd_user_id)'
			],
			__METHOD__
		);

		foreach ($cacheClear as $id) {
			$this->clearMemc($id);
		}

		return ['stats' => $stats, 'summary' => ''];
	}

	private function downloadCategories() {
		self::httpDownloadHeaders("cat_descriptions_".date('Ymd') . '.tsv');

		$headers = ["category_url", "custom_description", "custom_note"];
		print join("\t", $headers) . "\n";

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select( self::CATEGORY_DESCRIPTIONS, ['*'], '', __METHOD__ );
		foreach ($res as $row) {
			$title = Title::newFromId($row->cd_page_id);
			print $title->getFullURL() . "\t" . $row->cd_description . "\t" . $row->cd_custom_note . "\n";
		}
	}

	private function clearMemc($articleId) {
		global $wgMemc;

		$cacheKey = wfMemcKey(self::CACHE_KEY_DESCRIPTION, $articleId, true);
		$wgMemc->delete($cacheKey);
		$cacheKey = wfMemcKey(self::CACHE_KEY_DESCRIPTION, $articleId, false);
		$wgMemc->delete($cacheKey);
	}

	public static function getCategoryMetaDescription(Title $title) {
		return self::getCategoryDescription($title, false);
	}

	public static function getCategoryDescription(Title $title, $useLinks = true) {
		global $wgMemc, $wgParser, $wgLanguageCode;

		$cacheKey = wfMemcKey(self::CACHE_KEY_DESCRIPTION, $title->getArticleID(), $useLinks);
		$val = $wgMemc->get($cacheKey);
		if (is_string($val)) {
			return $val;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$description = $dbr->selectField(AdminCategoryDescriptions::CATEGORY_DESCRIPTIONS, "cd_description", ['cd_page_id' => $title->getArticleID()], __METHOD__);
		if ($description !== false && $description != "") {
			$options = new ParserOptions;
			$options->setTidy( true );
			$out = $wgParser->parse($description, $title, $options);
			$description = $out->getText();
			if (!$useLinks) {
				$description = trim(strip_tags($description));
			}
			$wgMemc->set($cacheKey, $description, self::CACHE_LENGTH);
			return $description;
		} else {
			$topCatKey = CategoryHelper::getTopCategoryIncludingWikiHow($title);
			if ($topCatKey == "WikiHow") {
				$description = wfMessage(self::MESSAGE_DESCRIPTION_WIKIHOW, $title->getText())->text();
			} else {
				//get the top 3 articles in the current category
				$titus_copy = WH_DATABASE_NAME_EN . '.titus_copy';
				$res = $dbr->select(
					['categorylinks', 'index_info', $titus_copy],
					['ti_page_id'],
					['ii_policy IN (1, 4)', 'cl_to' => $title->getDBkey()],
					__METHOD__,
					['ORDER BY' => 'ti_30day_views DESC', 'LIMIT' => 3],
					[
						'index_info' => ['INNER JOIN', 'cl_from = ii_page'],
						$titus_copy => ['LEFT JOIN', 'cl_from = ti_page_id AND ti_language_code = "'. $wgLanguageCode . '"']
					]
				);

				$titles = [];
				if ($dbr->numRows($res) > 0) {
					foreach ($res as $row) {
						$newTitle = Title::newFromID($row->ti_page_id);
						if ($newTitle) {
							if ($useLinks) {
								$titles[] = Linker::link($newTitle, wfMessage("howto", $newTitle->getText())->text());
							} else {
								$titles[] = wfMessage("howto", $newTitle->getText())->text();
							}
						}
					}
					$description = wfMessage(self::MESSAGE_DESCRIPTION_DEFAULT, $title->getText(), implode(", ", $titles))->text();
				} else {
					$description = wfMessage(self::MESSAGE_NO_ARTICLES, $title->getText())->text();
				}
			}
			$wgMemc->set($cacheKey, $description, self::CACHE_LENGTH);
			return $description;
		}
	}
}






/*****
CREATE TABLE `category_descriptions` (
`cd_page_id` int(10) unsigned NOT NULL default '0',
`cd_description` blob not null default '',
`cd_meta_description` blob not null default '',
`cd_custom_note` blob not null default '',
`cd_user_id` int(10) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY  (`cd_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*****/
