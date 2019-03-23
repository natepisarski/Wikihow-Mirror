<?php

class RecommendedArticles extends SpecialPage {

	public function __construct() {
        parent::__construct( 'RecommendedArticles' );
    }

    public function execute($par) {
		global $wgHooks;

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();
		$title = $this->getTitle();
		$langCode = $this->getLanguage()->getCode();

		if ($langCode != 'en') {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$map = SuggestCategories::getCatMap(true);
		$cats = SuggestCategories::getSubscribedCats();
		$dbr = wfGetDB(DB_REPLICA);
		$out->setRobotPolicy('noindex,nofollow');
		$out->setHTMLTitle('Manage Suggested Topics - wikiHow');

		$target = isset( $par ) ? $par : $req->getVal( 'target' );

		if ($target == 'TopRow') {
			$out->setArticleBodyOnly(true);
			$out->addHTML(self::getTopLevelSuggestions($map, $cats));
			return;
		}
		$out->addModules( ['ext.wikihow.SuggestedTopics'] );

		ListRequestedTopics::setActiveWidget();
		ListRequestedTopics::setTopAuthorWidget($target);
		ListRequestedTopics::getNewArticlesWidget();

		$wgHooks["pageTabs"][] = ["SuggestedTopicsHooks::requestedTopicsTabs"];

		//heading with link
		$request = Html::element('a', ['href' => '/Special:RequestTopic', 'class' => 'editsection'], wfMessage('requesttopic')->text());
		$heading = $request . Html::element('h2', [], wfMessage('suggestedarticles_header')->text());

		$out->addHTML($heading);

		$suggestions = "";

		if (count($cats) > 0) {
			foreach ($cats as $key) {
				$cat = $map[$key];
				$suggestionsArray = [];

				// grab some suggestions
				$randstr = wfRandom();
				$headerDone = false;
				$suggCount = 0;
				// grab 2 suggested articles that are NOT by ANON
				$resUser = $dbr->select('suggested_titles', ['st_title', 'st_user', 'st_user_text'],
					['st_category' => $cat, 'st_used=0', "st_user > 0"],
					__METHOD__,
					["ORDER BY" => "st_random", "LIMIT"=>2]
				);
				foreach ($resUser as $userRow) {
					$randSpot = mt_rand(0, 4);
					while (!empty($suggestionsArray[$randSpot]))
						$randSpot = mt_rand(0, 4);
					$suggestionsArray[$randSpot] = new stdClass;
					$suggestionsArray[$randSpot]->title = $userRow->st_title;
					$suggestionsArray[$randSpot]->user = $userRow->st_user;
					$suggCount++;
				}

				$res = $dbr->select('suggested_titles', ['st_title', 'st_user', 'st_user_text'],
					['st_category' => $cat, 'st_used' => 0, 'st_traffic_volume' => 2, "st_random >= $randstr"],
					__METHOD__,
					["ORDER BY" => "st_random", "LIMIT"=>5]
				);
				if ($dbr->numRows($res) > 0) {
					foreach ($res as $row) {
						if ($suggCount >= 5)
							break;
						$randSpot = mt_rand(0, 4);
						while (!empty($suggestionsArray[$randSpot]))
							$randSpot = mt_rand(0, 4);
						$suggestionsArray[$randSpot] = new stdClass;
						$suggestionsArray[$randSpot]->title = $row->st_title;
						$suggestionsArray[$randSpot]->user = $row->st_user;
						$suggCount++;
					}
				}

				if ($cat != 'Other') {
					$cat_class = 'cat_' . strtolower(str_replace(' ', '', $cat));
					$cat_class = preg_replace('/&/', 'and', $cat_class);
					$cat_class = htmlspecialchars($cat_class);
					$cat_icon = "<div class='cat_icon $cat_class'></div>";
				}
				else {
					$cat_icon = '';
				}

				if ($suggCount > 0) {
					$cat = htmlspecialchars($cat);
					$suggestions .= "<table class='suggested_titles_list wh_block'>";
					$suggestions .= "
					<tr class='st_top_row'>
						<th class='st_icon'>{$cat_icon}</th>
						<th class='st_title'>
							<strong>{$cat}</strong>
						</th>
						<th>Requested By</th>
					</tr>";

					foreach ($suggestionsArray as $suggestion) {
						if (!empty($suggestionsArray)) {
							$t = Title::newFromText(GuidedEditorHelper::formatTitle($suggestion->title));
							if ($suggestion->user > 0) {
								$user = User::newFromId($suggestion->user);
								$userLink = Html::element('a', ['href' => $user->getUserPage()->getFullURL()], $user->getName());
							}
							else {
								$userLink = "Anonymous";
							}
							$href = '/Special:CreatePage?target=' . $t->getPartialURL();
							$titleTxt = htmlspecialchars($t->getText());
							$suggestions .= "
							<tr>
								<td class='st_write'>
									<a href='{$href}'>Write</td>
									<td class='st_title'>{$titleTxt}
								</td>
								<td class='st_requestor'>{$userLink}</td>
							</tr>";

						}
					}

					$suggestions .= "</table>";
				}
			}
		}

		if ($req->getInt('surprise') == 1 || $suggestions == "")
			$out->addHTML("<div id='top_suggestions'>" . self::getTopLevelSuggestions($map, $cats) . "</div>");

		$clearLink = Html::element('a',
			['href' => '/Special:RecommendedArticles?surprise=1', 'class' => 'button secondary'],
			wfMessage('suggested_list_button_surprise')->text()
		);
		$out->addHTML("<br class='clearall' /><div id='suggested_surprise_big'>$clearLink</div><br class='clearall' />");

		if (sizeof($cats) == 0) {
			$out->addHTML(wfMessage('suggested_nocats')->escaped());
			$out->addHTML("<a href='#' id='choose_cats'>Choose which categories to display</a>");
			return;
		}

		if ($user->getID() > 0) {
			$out->addHTML($suggestions);
			$out->addHTML("<a href='#' id='choose_cats'>Choose which categories to display</a>");
		} else {
			$rt = $title->getPrefixedURL();
			$q = "returnto={$rt}";
			$out->addHTML(wfMessage('recommend_anon', $q)->text());
		}
    }

	// for the two little boxes at the top
	private static function getTopLevelSuggestions($map, $cats) {
		self::dbTopLevelSuggestions($map, $cats, $suggests, $users, $userids, $catresults);
		return self::htmlTopLevelSuggestions($map, $cats, $suggests, $users, $userids, $catresults);
	}

	private static function dbTopLevelSuggestions($map, $cats, &$suggests, &$users, &$userids, &$catresults) {
		$dbr = wfGetDB(DB_REPLICA);
		$suggests = [];
		$users = [];
		$userids = [];
		$catresults = [];

		$catarray = [];
		for ($i = 0; $i < count($cats); $i++) {
			$catarray[] = $map[$cats[$i]];
		}

		$randstr = wfRandom();
		$conds = ['st_used' => 0, 'st_traffic_volume' => 2, "st_random >= $randstr"];

		if ($catarray) {
			$conds['st_category'] = $catarray;
		}

		$rows = $dbr->select('suggested_titles',
			['st_title', 'st_user', 'st_user_text', 'st_category'],
			$conds,
			__METHOD__,
			['ORDER BY'=>'st_random', 'GROUP BY' => 'st_category']);

		if ($dbr->numRows($rows) == 0) {
			$conds = ['st_used=0', 'st_traffic_volume'=>2, "st_random >= $randstr"];
			$rows = $dbr->select('suggested_titles',
				['st_title', 'st_user', 'st_user_text', 'st_category'],
				$conds,
				__METHOD__,
				['ORDER BY' => 'st_random', 'GROUP BY' => 'st_category']);
			for ($i = 0; $i < 2; $i++) {
				$row = $dbr->fetchRow($rows);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}
		} elseif ($dbr->numRows($rows) == 1) {
			$row = $dbr->fetchRow($rows);
			$t = Title::makeTitle(NS_MAIN, $row['st_title']);
			$suggests[] = $t;
			$users[] = $row['st_user_text'];
			$userids[] = $row['st_user'];
			$catresults[] = $row['st_category'];

			$randstr = wfRandom();
			$conds = [
				'st_used' => 0,
				'st_traffic_volume' => 2,
				"st_random >= $randstr",
				"st_category" => $catarray,
				"st_title != " . $dbr->addQuotes($row['st_title'])
			];
			$rows2 = $dbr->select('suggested_titles',
				['st_title', 'st_user', 'st_user_text', 'st_category'],
				$conds,
				__METHOD__,
				['ORDER BY'=>'st_random', 'GROUP BY' => 'st_category']);
			if ($dbr->numRows($rows2) >= 1) {
				$row = $dbr->fetchRow($rows2);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			} else {
				$conds = ['st_used'=>0, 'st_traffic_volume'=>2, "st_random >= $randstr"];
				$rows = $dbr->select('suggested_titles',
					['st_title', 'st_user', 'st_user_text', 'st_category'],
					$conds,
					__METHOD__,
					['ORDER BY'=>'st_random', 'GROUP BY' => 'st_category']);
				$row = $dbr->fetchRow($rows);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}

		} else {
			for ($i = 0; $i < 2; $i++) {
				$row = $dbr->fetchRow($rows);
				$t = Title::makeTitle(NS_MAIN, $row['st_title']);
				$suggests[] = $t;
				$users[] = $row['st_user_text'];
				$userids[] = $row['st_user'];
				$catresults[] = $row['st_category'];
			}
		}
	}

	private static function htmlTopLevelSuggestions($map, $cats, $suggests, $users, $userids, $catresults) {
		$html = '';
		for ($i = 0; $i < 2; $i++) {
			if ($i == 1) {
				// add "OR": <div class="top_suggestion_or">OR</div>
				$html .= Html::element('div', ['class' => 'top_suggestion_or'], 'OR');
			}

			if ($userids[$i] > 0) {
				$u = User::newFromName($users[$i]);
				$user_line = Html::element('a', ['href' => $u->getUserPage()->getFullURL()], $u->getName());
			} else {
				$user_line = wfMessage('anonymous')->text();
			}

			$href = "/Special:CreatePage?target=" . urlencode($suggests[$i]->getPartialURL());
			$html .=
				Html::openElement('div', ['class' => 'top_suggestion_box']) .
					Html::element('div', ['class' => 'category'], $catresults[$i]) .
					Html::element('div', ['class' => 'title'], $suggests[$i]->getText()) .
					Html::openElement('div', ['class' => 'requestor']) .
						Html::element('img', ['src' => Avatar::getAvatarURL($users[$i])]) .
						Html::element('a', ['href' => $href, 'class' => 'button secondary'], 'Write') .
						'Requested By' . Html::element('br') . htmlspecialchars($user_line) .
					Html::closeElement('div') .
				Html::closeElement('div');
		}
		$html .= Html::element('br', ['class' => 'clearall']);

		return $html;
	}

}
