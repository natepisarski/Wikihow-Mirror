<?php

class ListRequestedTopics extends SpecialPage {
	const CAT_WIDTH = 201;
	const CAT_HEIGHT = 134;

	public function __construct() {
		parent::__construct( 'ListRequestedTopics' );
	}

	public function execute($par) {
		global $wgHooks, $wgLanguageCode;

		$out = $this->getOutput();
		$req = $this->getRequest();

		$out->setHTMLTitle('List Requested Topics - wikiHow');
		$out->setRobotPolicy('noindex,nofollow');

		self::setActiveWidget();
		self::setTopAuthorWidget($par);
		self::getNewArticlesWidget();

		list( $limit, $offset ) = $req->getLimitOffset(50, 'rclimit');
		$dbr = wfGetDB(DB_REPLICA);

		$out->addModules( ['ext.wikihow.SuggestedTopics'] );
		$out->addModules( ['ext.wikihow.leaderboard'] );

		$wgHooks["pageTabs"][] = array("SuggestedTopicsHooks::requestedTopicsTabs");

		$category = $req->getVal('category');
		$st_search = $req->getVal('st_search');

		//heading with link
		$request = Html::element('a',
			['href' => '/Special:RequestTopic', 'class' => 'editsection'],
			wfMessage('requesttopic')->text()
		);
		$heading = $request . '<h2>';

		//add altoblock only to headers on opening page
		if (!$st_search && !$category){
			$heading .= '<div class="altblock"></div>';
		}

		$heading .= wfMessage('suggested_list_topics_title')->escaped() . '</h2>';
		//add surpise button
		$heading .= Html::element('a', [
				'href' => '/Special:RecommendedArticles?surprise=1',
				'class' => 'button buttonright secondary',
				'id' => 'suggested_surprise'
			],
			wfMessage('suggested_list_button_surprise')->text()
		);

		if (!$st_search && !$category) {
			//add search box
			$heading .= self::getSearchBox();
			$heading .= '</div> <!-- end div for bodycontents-->';
		}

		$out->addHTML($heading);

		if (!$st_search && !$category) {
			//add sticking second heading
			$pickCateg = wfMessage('Pick-category')->escaped();
			$out->addHTML("<div class='minor_section section steps sticky'>
				<h2> <div class='altblock'></div> <span class='mw-headline'>{$pickCateg}</h2>
				<div class='section_text'>");

			$link = '/Special:ListRequestedTopics';

			$catmap = CategoryHelper::getIconMap();
			ksort($catmap);

			// additional cats added to the end of the list
			$catmap[wfMessage("suggested_list_cat_all")->text()] = "Image:Have-Computer-Fun-Step-22.jpg";
			$catmap[wfMessage("suggested_list_cat_other")->text()] = "Image:Make-a-Light-Bulb-Vase-Step-14.jpg";

			foreach ($catmap as $cat => $image) {

				$title = Title::newFromText($image);
				if ($title) {
					$file = wfFindFile($title, false);
					if (!$file) continue;

					$sourceWidth = $file->getWidth();
					$sourceHeight = $file->getHeight();
					$heightPreference = false;
					if (self::CAT_HEIGHT > self::CAT_WIDTH && $sourceWidth > $sourceHeight) {
						//desired image is portrait
						$heightPreference = true;
					}
					$thumb = $file->getThumbnail(self::CAT_WIDTH, self::CAT_HEIGHT, true, true, $heightPreference);

					$category = urldecode(str_replace("-", " ", $cat));

					$catTitle = Title::newFromText("Category:" . $category);
					if ($catTitle) {
						//'all' category has a different URL
						if ($category == wfMessage("suggested_list_cat_all")->text()) {
							$href = $link . "?st_search=all";
						} else {
							$href = $link . '?category=' . urlencode($category);
						}
						$out->addHTML(
						Html::openElement('div', ['class' => 'thumbnail']) .
							Html::openElement('a', ['href' => $href]) .
								Html::element('img', ['src' => wfGetPad($thumb->getUrl())]) .
								Html::openElement('div', ['class' => 'text']) .
									Html::openElement('p') .
										Html::element('span', [], $category) .
									Html::closeElement('p') .
								Html::closeElement('div') .
							Html::closeElement('a') .
						Html::closeElement('div'));
					}
				}
			}

			$out->addHTML("</div><!-- end section steps sticky -->");
			$out->addHTML("<div class='clearall'></div>");
			$out->addHTML("</div><!-- end section_text -->");

		} else { //if the user clicks on one of the icons
			$tables = 'suggested_titles';
			$fields = ['st_title', 'st_user_text', 'st_user'];
			$where = [ 'st_used' => 0 ];
			if ($category) {
				$where['st_category'] = $category;
			}
			$options = [
				'OFFSET' => $offset,
				'LIMIT' => $limit
			];

			if ($st_search && $st_search != "all") {
				$key = TitleSearch::generateSearchKey($st_search);
				$keyLike = '%' . str_replace(' ', '%', $key) . '%';
				$where[] = 'st_key LIKE ' . $dbr->addQuotes($keyLike);
			} else {
				$where['st_patrolled'] = 1;
				$options['ORDER BY'] = 'st_suggested DESC';
			}

			$res = $dbr->select($tables, $fields, $where, __METHOD__, $options);
			$out->addHTML(self::getSearchBox($key, $category));

			if ($dbr->numRows($res) > 0) {

				if ($key) {
					$col_header = 'Requests for ' . Html::element('strong', [], $key);
				} elseif ($category) {
					$col_header = htmlspecialchars(str_replace(" and ", " & ", $category));
				} else {
					$col_header = wfMessage('suggested_list_all')->escaped();
				}

				if ($category && $category != 'Other') {
					$cat_class = preg_replace('/&/','and', $category);
					$cat_class = 'cat_' . strtolower(preg_replace('@[^A-Za-z0-9]@', '', $cat_class));
					$cat_icon = Html::element('div', ['class' => "cat_icon $cat_class"]);
				} else {
					$cat_icon = '';
				}

				$out->addHTML("
				<table class='suggested_titles_list wh_block'>
					<tr class='st_top_row'>
						<th class='st_icon'>{$cat_icon}</th>
						<th class='st_title'>{$col_header}</th>
						<th>Requested By</th>
					</tr>"
				);

				$count = 0;
				foreach ($res as $row) {
					$title = Title::newFromDBKey($row->st_title);
					if (!$title) {
						continue;
					}

					if ($row->st_user == 0) {
						$requestor = 'Anonymous';
					} else {
						$u = User::newFromName($row->st_user_text);
						$requestor = Html::element('a', ['href' => $u->getUserPage()->getFullURL()], $u->getName());
					}

					$out->addHTML(
						Html::openElement('tr') .
							Html::openElement('td', ['class' => 'st_write']) .
								Html::element('a', ['href' => '/Special:CreatePage?target=' . $title->getPartialURL()], 'Write') .
							Html::closeElement('td') .
							Html::element('td', ['class' => 'st_title'], $title->getText()) .
							Html::openElement('td', ['class' => 'st_requestor']) . $requestor . Html::closeElement('td') .
						Html::closeElement('tr')
					);

					$count++;
				}
				$out->addHTML("</table>");
				$key = $st_search;
				if ($offset != 0) {
					$url = $_SERVER['SCRIPT_URI'];
					if ($key)
						$url .= "?st_search=" . urlencode($key);
					elseif ($category)
						$url .= "?category=" . urlencode($category);
					$out->addHTML(
						Html::element('a', [
							'class' => 'pagination',
							'style' => 'float: left;',
							'href' => $url . "&offset=" . (max($offset - $limit, 0))
						], "Previous {$limit}")
					);
				}
				if ($count == $limit) {
					$url = $_SERVER['SCRIPT_URI'];
					if ($key)
						$url .= "?st_search=" . urlencode($key);
					elseif ($category)
						$url .= "?category=" . urlencode($category);

					$out->addHTML(
						Html::element('a', [
							'class' => 'pagination',
							'style' => 'float: right;',
							'href' => $url . "&offset=" . ($offset + $limit)
						], "Next {$limit}")
					);
				}
				$out->addHTML("<br class='clearall' />");
			} else {
				if ($key) {
					if ($wgLanguageCode == 'en') {
						$create_link = '/Special:ArticleCreator?t='.urlencode($st_search);
					}
					else {
						$create_link = '/index.php?title='.urlencode($st_search).'&action=edit';
					}

					$out->addHTML(
						Html::openElement('div', ['class' => 'search_noresults']) .
							wfMessage('suggest_noresults', $st_search)->escaped() .
							'<br>' .
							Html::element('a', [
								'href' => $create_link,
								'class' => 'button primary create_btn',
							], wfMessage('suggest_start_article', $st_search)->text()) .
							'<br>' .
							Html::element('a', [
								'href' => '/Special:ListRequestedTopics',
							], wfMessage('suggest_continue_searching')->text()) .
						Html::closeElement('div')
					);
				}
				else {
					$out->addHTML(wfMessage('suggest_noresults', $category)->escaped());
				}
			}

			$out->addHTML('</div> <!-- end div for bodycontents-->');
		}
	}

	private static function getSearchBox($searchTerm = "", $category = "") {
		$width = $category ? ('width: ' . (421-(strlen($category)*6)).'px') : '';

		$html =
		Html::openElement('form', ['action' => '/Special:ListRequestedTopics', 'id' => 'st_search_form']) .
			Html::element('input', [
				'type' => 'text',
				'id' => 'st_search',
				'name' => 'st_search',
				'class' => 'search_input',
				'style' => $width,
				'value' => $searchTerm,
			]) .
			Html::element('input', [
				'type' => 'hidden',
				'name' => 'category',
				'value' => $category,
			]) .
			Html::element('input', [
				'type' => 'submit',
				'id' => 'st_search_btn',
				'class' => 'button secondary',
				'style' => 'margin-left: 10px',
				'value' => wfMessage('st_search', $category)->text(),
			]) .
		Html::closeElement('form');

		return $html;
	}

	public static function getCategoryImage($category) {
		$parts = explode(' ', $category);
		$firstName = count($parts) ? strtolower($parts[0]) : '';
		$options = array(
			'arts', 'cars', 'computers', 'education', 'family', 'finance', 'food',
			'health', 'hobbies', 'holidays', 'home', 'personal', 'pets', 'philosophy',
			'relationships', 'sports', 'travel', 'wikihow', 'work', 'youth',
		);
		if (in_array($firstName, $options)) {
			$path = wfGetPad("/skins/WikiHow/images/category_icon_$firstName.png");
			$image = Html::element('img', ['src' => $path, 'alt' => $category]);
		} else {
			$path = '';
			$image = '';
		}
		return $image;
	}

	public static function setActiveWidget() {
		$unw = number_format(self::getUnwrittenTopics(), 0, ".", ", ");

		if (RequestContext::getMain()->getUser()->getID() != 0) {
			$today = self::getArticlesWritten(false);
			$topicsToday = self::getTopicsSuggested(false);
			$alltime = self::getArticlesWritten(true);
			$topicsAlltime = self::getTopicsSuggested(true);
		} else {
			$today = Linker::link(Title::makeTitle(NS_SPECIAL, "Userlogin"), "Login");
			$topicsToday = "N/A";
			$alltime = "N/A";
			$topicsAlltime = "N/A";
		}

		$html =
		Html::openElement('div', ['class' => 'stactivewidget']) .

			Html::element('h3', [], wfMessage('st_currentstats')->text()) .

			Html::openElement('table', ['class' => 'st_stats']) .

				Html::openElement('tr', ['class' => 'dashed']) .
					Html::element('td', [], wfMessage('st_numunwritten')->text()) .
					Html::element('td', ['class' => 'stcount'], $unw) .
				Html::closeElement('tr') .

				Html::openElement('tr') .
					Html::openElement('td') .
						Html::element('a', [
							'href' => '/Special:Leaderboard/articles_written?period=24',
							'target' => 'new'
						], wfMessage('st_articleswrittentoday')->text()) .
					Html::closeElement('td') .
					Html::element('td', ['class' => 'stcount', 'id' => 'patrolledcount'], $today) .
				Html::closeElement('tr') .

				Html::openElement('tr', ['class' => 'dashed']) .
					Html::openElement('td') .
						Html::element('a', [
							'href' => '/Special:Leaderboard/requested_topics?period=24',
							'target' => 'new'
						], wfMessage('st_articlessuggestedtoday')->text()) .
					Html::closeElement('td') .
					Html::element('td', ['class' => 'stcount', 'id' => 'quickedits'], $topicsToday) .
				Html::closeElement('tr') .

				Html::openElement('tr') .
					Html::element('td', [], wfMessage('st_alltimewritten')->text()) .
					Html::element('td', ['class' => 'stcount', 'id' => 'alltime'], $alltime) .
				Html::closeElement('tr') .

				Html::openElement('tr', ['class' => 'dashed']) .
					Html::element('td', [], wfMessage('st_alltimesuggested')->text()) .
					Html::element('td', ['class' => 'stcount'], $topicsAlltime) .
				Html::closeElement('tr') .

			Html::closeElement('table') .

			Html::element('center', [], wfMessage('rcpatrolstats_activeupdate')) .

		Html::closeElement('div');

		$skin = RequestContext::getMain()->getSkin();
		$skin->addWidget($html);
	}

	public static function setTopAuthorWidget($target) {
		$startdate = strtotime('7 days ago');
		$starttimestamp = date('Ymd-G', $startdate) . '!' . floor(date('i',$startdate)/10) . '00000';
		$data = LeaderboardStats::getArticlesWritten($starttimestamp);
		arsort($data);
		$topAuthorWidget = "<h3>Top Authors - Last 7 Days</h3> <table class='stleaders'>";

		$index = 1;

		foreach ($data as $key => $value) {
			$u = new User();
			$value = number_format($value, 0, '', ',');
			$u->setName($key);
			if ($value > 0 && $key != '') {
				$userAvatar = Avatar::getPicture($u->getName(), true);
				if (!$userAvatar) {
					$userAvatar = Avatar::getDefaultPicture();
				}
				$target = urlencode($target);
				$userName = urlencode($u->getName());
				$leaderHref = "/Special:Leaderboard/{$target}?action=articlelist&lb_name={$userName}";
				$topAuthorWidget .= "
				<tr>
					<td class='leader_image'>" . $userAvatar . "</td>
					<td class='leader_user'>" . Linker::link($u->getUserPage(), $u->getName()) . "</td>
					<td class='leader_count'><a href='{$leaderHref}'>{$value}</a></td>
				</tr> ";
				$data[$key] = $value * -1;
				$index++;
			}
			if ($index > 6) break;
		}
		$topAuthorWidget .= "</table>";

		$skin = RequestContext::getMain()->getSkin();
		$skin->addWidget('<div>' . $topAuthorWidget . '</div>');
	}

	private static function getNewArticlesBox() {
		$dbr = wfGetDB(DB_REPLICA);
		$ids = RisingStar::getRisingStarList(5, $dbr);
		$html = "<div id='side_new_articles'><h3>" . wfMessage('newarticles')->escaped() . "</h3>\n<table>";
		if ($ids) {
			$tables = 'page';
			$fields = ['page_namespace', 'page_title'];
			$where = ['page_id' => $ids];
			$options = ['ORDER BY' => 'page_id desc', 'LIMIT' => 5];
			$res = $dbr->select($tables, $fields, $where, __METHOD__, $options);
			foreach ($res as $row) {
				$t = Title::makeTitle(NS_MAIN, $row->page_title);
				if (!$t) continue;
				$html .= FeaturedArticles::featuredArticlesRow($t);
			}
		}
		$html .=  "</table></div>";
		return $html;
	}

	public static function getNewArticlesWidget() {
		$skin = RequestContext::getMain()->getSkin();
		$html = self::getNewArticlesBox();
		$skin->addWidget($html);
	}

	// Used in community dashboard
	public static function getUnwrittenTopics() {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('suggested_titles', 'count(*)', ['st_used' => 0]);
		return $count;
	}

	private static function getArticlesWritten($alltime) {
		$dbr = wfGetDB(DB_REPLICA);
		$conds = array('fe_user' => RequestContext::getMain()->getUser()->getID(), 'page_id = fe_page', 'page_namespace=0');
		if (!$alltime) {
			// just today
			$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
			$conds[] = "fe_timestamp > '{$cutoff}'";
		}
		$count = $dbr->selectField(['firstedit', 'page'], 'count(*)', $conds);
		return number_format($count, 0, ".", ", ");
	}

	private static function getTopicsSuggested($alltime) {
		$dbr = wfGetDB(DB_REPLICA);
		$conds = array('fe_user' => RequestContext::getMain()->getUser()->getID(), 'fe_page=page_id', 'page_title=st_title', 'page_namespace=0');
		if (!$alltime) {
			// just today
			$cutoff = wfTimestamp(TS_MW, time() - 24 * 3600);
			$conds[] = "fe_timestamp > '{$cutoff}'";
		}
		$count = $dbr->selectField(['firstedit', 'page' ,'suggested_titles'], 'count(*)', $conds);
		return number_format($count, 0, ".", ", ");
	}

}


