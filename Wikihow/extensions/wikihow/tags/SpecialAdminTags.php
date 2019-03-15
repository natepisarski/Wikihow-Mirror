<?php

if (!defined('MEDIAWIKI')) die();

class AdminTags extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		$this->specialPage = 'AdminTags';
		parent::__construct($this->specialPage);
	}

	private static function validateInput($key, $val) {
		$err = '';
		if (('wikiphoto-article-exclude-list' == $key)
			|| ('wikihow-watermark-article-list' == $key)
			|| ('editfish-article-exclude-list' == $key)) {
			$list = self::parseURLlist($val);
			foreach ($list as $item) {
				if (!$item['title'] || !$item['title']->isKnown()) {
					$err .= $item['url'] . "\n";
				}
			}
		}
		return $err;
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist($pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$urls = [];
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$title = WikiPhoto::getArticleTitleNoCheck(urldecode($url));
				$urls[] = ['url' => $url, 'title' => $title];
			}
		}
		return $urls;
	}

	private static function translateValues($values,$list_type) {
		$result = '';
		$list = self::parseURLlist($values);

		foreach ($list as $item) {
			$value = '';

			if ($item['title']) {
				if ('id' == $list_type) {
					$value = $item['title']->getArticleID();
					if (!empty($value))  $result .= $value . "\r\n";
				}
				elseif ('url' == $list_type) {
					$value = $item['title']->getDBkey();
					if (!empty($value)) {
						$artid = $item['title']->getArticleID();
						$result .= '<tr>
									  <td>http://www.wikihow.com/'.$value.'</td>
									  <td class="x"><a href="#" class="remove_link" id="'.$artid.'">x</a></td>
									</tr>';
					}
				}
			}
		}

		if ('url' == $list_type) {
			$result = '<table>'.$result.'</table>';
		}
		return $result;
	}

/*
	// this method was disabled because it would need to be tested again with new article tags.
	private function removeLine($key, $id) {
		$err = '';
		if (!empty($id)) {
			$val = ConfigStorage::dbGetConfig($key);
			$pageList = preg_split('@[\r\n]+@', $val);

			$id_pos = array_search($id, $pageList);
			if ($id_pos === false) {
				$err = 'Article not found in list';
			}
			else {
				unset($pageList[$id_pos]);
				$val = implode("\r\n",$pageList);
				$isArticleList = true;
				$err = '';
				ConfigStorage::dbStoreConfig($key, $val, $isArticleList, $err);

				//now let's return the whole thing back
				$result = $this->translateValues($val,'url');
			}
		}
		else {
			$err = 'Bad article id';
		}
		return ['result' => $result, 'error' => $err];
	}
/*

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		$style = strtolower($par) == 'url' ? 'url' : '';

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {

			$action = $req->getVal('action');
			$style = $req->getVal('style', '');
			$out->setArticleBodyOnly(true);

			if ('load-config' == $action) {
				$key = $req->getVal('config-key', '');
				$val = ConfigStorage::dbGetConfig($key);
				if (!$val) $val = '';

				if ($style == 'url' && $val) {
					//translate ids to readable urls
					$val = $this->translateValues($val, $style);
				}

				$result = ['result' => $val];

				if ($val) {
					$isArticleList = (int)ConfigStorage::dbGetIsArticleList($key);
					if ($isArticleList) {
						$tag = new ArticleTag($key);
						if ($tag) {
							$result['prob'] = $tag->getProbability();
						}
					}
					$result['article-list'] = $isArticleList;
				}

				$allowed = ConfigStorage::hasUserRestrictions($key);
				if (!$allowed) {
					$result['restriction'] = 'Please contact Elizabeth to be able to edit this key';
				}
			} elseif ('save-config' == $action) {
				$errors = '';
				$key = $req->getVal('config-key', '');
				$val = $req->getVal('config-val', '');
				$prob = $req->getInt('prob');

				if ($style == 'url') {
					//validate for errors
					$errors = self::validateInput($key, $val);

					//grab the existing values from the db
					$val_db = ConfigStorage::dbGetConfig($key);

					//translate the good urls back to ids for storage purposes
					$val = $this->translateValues($val,'id');

					//add the new and old values together
					$val = $val_db ."\r\n". $val;
				}

				$err = '';
				$isArticleList = ConfigStorage::dbGetIsArticleList($key);
				$allowArticleErrors = true; // allow articles not to exist when saving
				ConfigStorage::dbStoreConfig($key, $val, $isArticleList, $err, $allowArticleErrors);
				if (!$err && $isArticleList) {
					$tag = new ArticleTag($key);
					$res = $tag->updateProbability($prob);
					if (!$res) {
						$err = 'Unabled to update probability';
					}
				}
				if ($err) {
					$errors = $err . "\n" . $errors;
				}
				$errors .= self::validateInput($key, $val);
				if ($errors) {
					$output .= 'WARNINGS:<br/>' . str_replace("\n", "<br/>\n", $errors);
				} else {
					$output = 'saved and checked input<br/><br/>';
					$output .= "no errors or warnings.";
				}

				if ($style == 'url') {
					// ** commentting out because it times out if the input was too big
					//translate back to urls for updated display
					//$val = $this->translateValues($val,'url');
					$val = '';
				}

				$result = ['result' => $output, 'val' => $val];
			} elseif ('delete-config' == $action) {
				$key = $req->getVal('config-key', '');
				$isArticleList = ConfigStorage::dbGetIsArticleList($key);
				if ($isArticleList) {
					$tag = new ArticleTag($key);
					$tag->deleteTag();
				}
				$res = ConfigStorage::dbDeleteConfig($key);
				if (!$res) {
					$result = 'Error: Unable to delete key: ' . $key;
				} else {
					$result = 'Deleted key: ' . $key;
				}
				$result = ['result' => $result];
			} elseif ('create-config' == $action) {
				$err = '';
				$prob = $req->getInt('new-prob');
				if (!$prob) {
					$prob = 0;
				} else {
					// this is a safety check that should be caught at frontend first
					if ($prob < 1 || $prob > 99) {
						$err = 'Invalid probability';
					}
				}
				if (!$err) {
					$newKey = $req->getVal('new-key', '');
					// safety checks done on front end as well
					if (strlen($newKey) < 2 || strlen($newKey) > 64) {
						$err = 'Invalid tag length: ' . strlen($newKey);
					}
					if (!$err) {
						$res = ConfigStorage::dbGetConfig($newKey);
						if ($res) {
							$err = 'New key you specified already exists: ' . $newKey;
						}
					}
				}
				if (!$err) {
					$newVal = $req->getVal('config-val-new', '');
					$isArticleList = $req->getVal('is-article-list') == 'true';
					$allowArticleErrors = false; // force an error if articles don't exist etc
					$res = ConfigStorage::dbStoreConfig($newKey, $newVal, $isArticleList, $err, $allowArticleErrors, $prob);
					if (!$res) {
						$result = 'Key was not saved';
					} else {
						$result = "Key was saved: $newKey";
					}
				}
				$result = ['result' => $result, 'error' => $err, 'debug' => print_r($_POST, true)];
			} elseif ('csh-history' == $action) {
				$csh_id = $req->getInt('cshid', 0);
				$data = ConfigStorageHistory::dbGetDetails($csh_id);
				if (!$data) {
					$result = ['error' => 'not found'];
				} else {
					$result = [
						'csh_key' => $data['csh_key'],
						'csh_summary' => $data['csh_log_short'],
						'csh_changes' => $data['csh_log_full'],
					];
				}
/*
			} elseif ('remove-line' == $action) {
				$key = $req->getVal('config-key', '');
				$id = $req->getVal('id', '');
				$result = $this->removeLine($key, $id);
				$result = [ 'result' => $result['result'],'error' => $result['error'] ];
*/
			} else {
				$result = ['error' => 'bad action'];
			}

			$out->addHTML( json_encode($result) );

		} else {

			$out->addModules( ['jquery.ui.dialog', 'ext.wikihow.AdminTags'] );
			$out->setHTMLTitle(wfMessage('pagetitle', 'Admin - Article Tag Editor'));
			$listConfigs = ConfigStorage::dbListConfigKeys();

			$tmpl = $this->getGuts($listConfigs, $style);

			$out->addHTML($tmpl);

		}
	}

	private function getGuts($configs, $style) {
		EasyTemplate::set_path(__DIR__ . '/');

		$params['style'] = $style;
		$params['configs'] = $configs;
		$params['bURL'] = $style == 'url';
		$params['specialPage'] = $this->specialPage;
		$params['history'] = ConfigStorageHistory::dbListHistory();
		$html = EasyTemplate::html('admin-config-guts.tmpl.php', $params);
		return $html;
	}
}
