<?
class WAPLinker {
	private $dbType = null;
	private $config = null;

	public function __construct($dbType) {
		$this->dbType = $dbType;
		$this->config = WAPDB::getInstance($dbType)->getWAPConfig();
	}
	public function linkTags(&$tags) {
		$userPage = $this->config->getUserPageName();
		foreach ($tags as $i => $tag) {
			$encoded = urlencode($tag['raw_tag']);
			$tag = htmlspecialchars($tag['raw_tag'], ENT_QUOTES);
			$tag = "<a href='/Special:$userPage/tag/$encoded'>$tag</a>";
			$tags[$i] = $tag;
		}	
		return $tags;
	}

	public function linkUsers(&$users) {
		foreach ($users as $i => $user) {
			$users[$i] = $this->linkUser($user);
		}
		return $users;
	}

	public function linkUser(&$user) {
		$name = $user->getName();
		$realName = $user->getRealName();
		if (!empty($realName)) {
			$name .= " ($realName)";
		}
		$userPage = $this->config->getUserPageName();	
		return "<a href='/Special:$userPage/user/{$user->getId()}'>" . htmlspecialchars($name, ENT_QUOTES) . "</a>";
	}

	public function linkUserByUserText(&$userText) {
		$userClass = $this->config->getUserClassName();
		$cu = $userClass::newFromName($userText, $this->dbType);
		return $this->linkUser($cu, $this->dbType);
	}

	public function makeUserSelectOptions(&$users) {
		array_walk($users, function(&$user) {
			$user = "<option value='{$user->getId()}'>" . htmlspecialchars($user->getName(), ENT_QUOTES) . "</option>";
		});
		return implode("\n", $users);
	}

	public function makeLanguageSelectOptions($languages) {
		array_walk($languages, function(&$lang) {
			$lang = "<option value='{$lang}'>" . htmlspecialchars($lang, ENT_QUOTES) . "</option>";
		});
		return implode("\n", $languages);
	}

	public function makeCategoriesSelectOptions(&$cats) {
		array_walk($cats, function(&$cat, $key) {
			$cat = "<option value='{$key}'>" . htmlspecialchars($cat, ENT_QUOTES) . "</option>";
		});
		$options = "<option></option>" . implode("\n", $cats);
		return $options;
	}

	public function makeTagSelectOptions(&$tags) {
		array_walk($tags, function(&$tag) {
			$optVal = htmlspecialchars($tag['tag_id'] . "," . $tag['raw_tag']);
			$tag = "<option value='$optVal'>" . $tag['raw_tag'] . "</option>\n";
		});
		return implode("\n", $tags);
	}

	public static function linkSystemUrl(&$url) {
		$url = htmlspecialchars($url, ENT_QUOTES);
		return "<a href='$url'>$url</a>";
	}

	public static function linkWikiHowUrl(&$url) {
		$url = htmlspecialchars($url, ENT_QUOTES);
		return "<a href='$url' target='_blank'>$url</a>";
	}

	public static function makeWikiHowUrl(&$pageTitle) {
		return Misc::makeUrl($pageTitle);
	}
}
