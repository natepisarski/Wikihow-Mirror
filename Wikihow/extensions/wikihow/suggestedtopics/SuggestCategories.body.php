<?php

/*
CREATE TABLE `suggest_cats` (
  `sc_user` int(10) unsigned NOT NULL DEFAULT '0',
  `sc_cats` varchar(512) DEFAULT NULL,
  UNIQUE KEY `sc_user` (`sc_user`)
);
*/

class SuggestCategories extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'SuggestCategories' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		$dbr = wfGetDB(DB_REPLICA);

		// just getting cats?
		if ($req->getVal('getusercats')) {
			$catmap = self::getCatMap();
			$cats = self::getSubscribedCats();

			$out->setArticleBodyOnly(true);

			if ((count($catmap) == count($cats)) || empty($cats)) {
				$out->addHTML('All');
				return;
			}

			foreach ($catmap  as $cat) {
				$key = strtolower(str_replace(' ', '-', $cat));
				$safekey = str_replace('&', 'and', $key);

				// hack for the ampersand in our db
				$checkkey = ($safekey == 'cars-and-other-vehicles' ? 'cars-&-other-vehicles' : $safekey);

				// are we selecting it?
				if ($cats && in_array($checkkey, $cats)) {
					$usercats[] = $cat;
				}
			}
			$out->addHTML(implode($usercats, ', '));

			return;
		}

		// process any postings, saving the categories
		if ($req->wasPosted()) {
			$field = preg_replace('@ @', '', $req->getVal('cats'));
			// hack for ampersand in "cars & other vehicles" category
			$field = str_replace('cars-and-other-vehicles','cars-&-other-vehicles', $field);

			$cats = preg_split('@,@', $field, 0, PREG_SPLIT_NO_EMPTY);
			$cats = array_unique($cats);
			sort($cats);

			$dbw = wfGetDB(DB_MASTER);

			$uid = $user->getID();
			$cats = implode($cats, ',');
			$row = [ 'sc_user' => $uid, 'sc_cats' => $cats ];
			$res = $dbw->upsert('suggest_cats', $row, ['sc_user'], ['sc_cats' => $cats]);

			$out->addHTML('<br/><br/>Categories updated.<br/><br/>');

			$type = $req->getVal('type');
			if ($type) {
				$out->redirect('/Special:EditFinder/' . urlencode($type));
			} else {
				$out->redirect('/Special:RecommendedArticles');
			}
		}

		$out->setArticleBodyOnly(true);

		$catmap = self::getCatMap();
		$cats = self::getSubscribedCats();

		$hiddencats = implode($cats, ',');
		$hiddencats = str_replace('&','and', $hiddencats);
		$hiddencats = htmlspecialchars($hiddencats);

		// get top categories
		$theHTML .= "<form method='post' action='/Special:SuggestCategories' id='suggest_cats'
						name='suggest_cats'><input type='hidden' name='cats' value='$hiddencats'/>";
		$theHTML .= "<table width='100%' class='categorytopics selecttopics'><tr>";
		$index = 0;
		$select_count = 0;

		foreach ($catmap  as $cat) {
			$key = strtolower(str_replace(' ', '-', $cat));
			$safekey = str_replace('&', 'and', $key);
			// hack for the ampersand in our db
			$checkkey = ($safekey == 'cars-and-other-vehicles') ? 'cars-&-other-vehicles' : $safekey;

			// are we selecting it?
			if ($cats && in_array($checkkey, $cats)) {
				$c = 'chosen';
				$s = 'checked="checked"';
				$select_count++;
			}
			else {
				$c = 'not_chosen';
				$s = '';
			}

			$categoryImg = ListRequestedTopics::getCategoryImage($cat);
			$safeCat = htmlspecialchars($cat);
			$theHTML .= "
			<td id='{$safekey}' class='{$c} categorylink'>
				<a><input type='checkbox' id='check_{$safekey}' {$s} />{$categoryImg}<br>{$safeCat}</a>
			</td>";
			$index++;
			if ($index % 6 == 0)
				$theHTML .= '</tr><tr>';

		}
		$actual_count = $index;
		if ($index % 6 <= 5) {
			while ($index % 6 != 0) {
				$theHTML .= '<td></td>';
				$index++;
			}
		}

		$save = wfMessage('save')->escaped();
		$theHTML .= "</tr></table>
			<a style='float: right; background-position: 0% 0pt;' class='button primary'
				onclick='document.suggest_cats.submit();' id='the_save_button'>{$save}</a>";

		// selected all?
		$s = $select_count == $actual_count ? "checked='checked'" : "";
		// add checkbox at the top
		$theHTML = "<input type='checkbox' id='check_all_cats' {$s} />
			<label for='check_all_cats'>All categories</label>{$theHTML}</form>";

		$out->addHTML($theHTML);
	}

	// returns a set of keys for the top level categories
	public static function getCatMap($associative=false) {
		// get it? cat-map? instead of cat-nap? hahah.
		$cat_title = Title::makeTitle(NS_PROJECT, "Categories");
		$rev = Revision::newFromTitle($cat_title);
		$text = preg_replace("@\*\*.*@im", "", ContentHandler::getContentText( $rev->getContent() ));
		$text = preg_replace("@\n[\n]*@im", "\n", $text);
		$lines = explode("\n", $text);
		$map = [];
		foreach ($lines as $l) {
			if (strpos($l, "*") === false) continue;
			$cat = trim(preg_replace("@\*@", "", $l));
			if ($associative) {
				$key = strtolower(str_replace(" ", "-", $cat));
				$map[$key] = $cat;
			} else {
				$map[] = $cat;
			}
		}
		return $map;
	}

	public static function getSubscribedCats() {
		global $wgUser;
		$dbr = wfGetDB(DB_REPLICA);
		$row = $dbr->selectRow('suggest_cats', '*', ['sc_user' => $wgUser->getID()]);
		if ($row) {
			$field = $row->sc_cats;
			if ($field == '')
				return array();
			$cats = preg_split("@,@", $field, 0, PREG_SPLIT_NO_EMPTY);
			return $cats;
		}
		$catmap = self::getCatMap();

		foreach ($catmap as $cat) {
			$cats[] = strtolower(str_replace(' ', '-', $cat));
		}

		return $cats;
	}
}
