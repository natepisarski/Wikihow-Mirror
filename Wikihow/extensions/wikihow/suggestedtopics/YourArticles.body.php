<?php

class YourArticles extends SpecialPage {

	public function __construct() {
		parent::__construct( 'YourArticles' );
	}

	public function execute($par) {
		global $wgLanguageCode, $wgHooks;

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();


		if ($wgLanguageCode != 'en') {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			$out->setRobotPolicy('noindex,nofollow');
			return;
		}

		$out->addModules( ['ext.wikihow.SuggestedTopics'] );

		ListRequestedTopics::setActiveWidget();
		ListRequestedTopics::setTopAuthorWidget($par);
		ListRequestedTopics::getNewArticlesWidget();

		$wgHooks['pageTabs'][] = ['SuggestedTopicsHooks::requestedTopicsTabs'];

		$out->setHTMLTitle('Articles Started By You - wikiHow');
		$out->setRobotPolicy('noindex,nofollow');

		//heading with link
		$request = Html::element('a',
			['href' => '/Special:RequestTopic', 'class' => 'editsection'],
			wfMessage('requesttopic')->text()
		);
		$heading = $request . Html::element('h2', [], wfMessage('your_articles_header')->text());

		//add surpise button
		$heading .= Html::element(
			'a',
			[
				'href' => '/Special:RecommendedArticles?surprise=1',
				'class' => 'button buttonright secondary',
				'id' => 'suggested_surprise'
			],
			wfMessage('suggested_list_button_surprise')->text()
		);
		$out->addHTML($heading . '<br><br><br>');

		if ($user->getID() > 0) {

			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->query("select * from firstedit
				 left join page on fe_page = page_id
				 left join suggested_titles
				   on page_title = st_title
				  and page_namespace = 0
				where fe_user = {$user->getID()}
				  and page_id is not NULL
				order by st_category");

			if ($dbr->numRows($res) == 0) {
				$out->addHTML(wfMessage('yourarticles_none')->text());
				return;
			}

			$last_cat = '-';

			// group it by categories
			// sometimes st_category is not set, so we have to grab the top category
			// from the title object of the target article
			$articles = [];
			foreach ($res as $row) {
				$t = Title::makeTitle(NS_MAIN, $row->page_title);
				$cat = $row->st_category;
				if ($cat == '') {
					$str = CategoryHelper::getTopCategory($t);
					if ($str != '')  {
						$title = Title::makeTitle(NS_CATEGORY, $str);
						$cat = $title->getText();
					} else {
						$cat = 'Other';
					}
				}
				if (!isset($articles[$cat]))
					$articles[$cat] = [];
				$articles[$cat][] = $row;
			}

			$mustacheEngine = new Mustache_Engine([
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
			]);

			foreach ($articles as $cat => $article_array) {
				$image = ListRequestedTopics::getCategoryImage($cat);
				$style = '';
				if ($image == '') {
					$style = 'style="padding-left: 67px;"';
				}

				$safeCat = htmlspecialchars($cat);
				$out->addHTML("<h2>{$safeCat}</h2><div class='wh_block'><table class='suggested_titles_list'>");

				foreach ($article_array as $row) {
					$t = Title::makeTitle(NS_MAIN, $row->page_title);
					$wp = WikiPage::factory($t);
					$ago = wfTimeAgo($wp->getTimestamp());
					$authors = array_keys(self::getAuthors($t));
					$authorsHtml = [];
					for ($i = 0; $i < 2 && sizeof($authors) > 0; $i++) {
						$a = array_shift($authors);
						if ($a == 'anonymous')  {
							$authorsHtml[] = 'Anonymous';
						} else {
							$u = User::newFromName($a);
							if (!$u) {
								echo "{$a} broke";
								exit;
							}
							$authorsHtml[] = Html::element('a', ['href' => $u->getUserPage()->getFullURL()], $u->getName());
						}
					}

					$vars = [
						'ago' => $ago,
						'imgSrc' => ImageHelper::getGalleryImage($t, 46, 35),
						'titleUrl' => $t->getFullURL(),
						'howToTxt' => wfMessage('howto', $t->getFullText())->text(),
						'userPageUrl' => $user->getUserPage()->getFullURL(),
						'authorList' => implode(', ', $authorsHtml),
						'viewCount' => number_format($row->page_counter, 0, '', ',')
					];
					$html = $mustacheEngine->render('your_articles_article_row.mustache', $vars);
					$out->addHTML($html);
				}
				$out->addHTML('</table></div>');
			}
		} else {
			$q = 'returnto=' . $this->getTitle()->getPrefixedURL();
			$out->addHTML(wfMessage('yourarticles_anon', $q)->text());
		}
	}

	private static function getAuthors($t) {
		$dbr = wfGetDB(DB_REPLICA);
		$authors = [];
		$res = $dbr->select('revision',
			['rev_user', 'rev_user_text'],
			['rev_page'=> $t->getArticleID()],
			__METHOD__,
			['ORDER BY' => 'rev_timestamp']
		);
		foreach ($res as $row) {
			if ($row->rev_user == 0) {
			   $authors['anonymous'] = 1;
			} elseif (!isset($authors[$row->rev_user_text])) {
			   $authors[$row->rev_user_text] = 1;
			}
		}
		return array_reverse($authors);
	}

}

