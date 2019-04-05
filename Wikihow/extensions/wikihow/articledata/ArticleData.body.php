<?php

class ArticleData extends UnlistedSpecialPage {

	var $action = null;
	var $slowQuery = false;
	var $introOnly = false;

	public function __construct() {
		parent::__construct('ArticleData');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($req->wasPosted()) {
			$this->action = $req->getVal('a');
			$this->slowQuery = $req->getVal('alts') == 'true';
			$this->introOnly = $req->getVal('intonly') == 'true';
			switch ($this->action) {
				case 'cats':
					$this->outputCategoryReport();
					break;
				case 'articles':
					$this->outputArticleReport();
					break;
				case 'ids':
					$this->outputArticleIdReport();
					break;
			}
			return;
		}

		$this->action = empty($par) ? 'cats' : strtolower($par);
		$out->addScript(HtmlSnips::makeUrlTag('/extensions/wikihow/common/download.jQuery.js'));
		EasyTemplate::set_path( __DIR__.'/' );
		$vars = array();
		$this->setVars($vars);
		$html = EasyTemplate::html('ArticleData.tmpl.php', $vars);
		$out->setPageTitle('Article Stats');
		$out->addHTML($html);
	}

	private function setVars(&$vars) {
		$vars['ct_a'] = $this->action;
	}

	private function outputCategoryReport() {
		$req = $this->getRequest();
		$out = $this->getOutput();

		$title = Misc::getTitleFromText(trim(Misc::getUrlDecodedData($req->getVal('data'))));
		$cat = $title->getText();
		$catArr = array($cat);
		$cats = CategoryInterests::getSubCategoryInterests($catArr);
		$cats[] = $cat;
		$cats = array_map(
			function($cat) {
				return str_replace(' ', '-', $cat);
			},
			$cats
		);

		$dbr = wfGetDB(DB_REPLICA);
		$cats = $dbr->makeList($cats);
		$sql = 'SELECT
					page_id, page_title, page_counter
				FROM page p
				INNER JOIN categorylinks c ON c.cl_from = page_id
				WHERE page_namespace = 0  and page_is_redirect = 0 AND c.cl_to IN (' . $cats . ')';
		$res = $dbr->query($sql);

		$articles = array();
		foreach ($res as $row) {
			$altsData = "";
			if ($this->slowQuery) {
				$r = Revision::loadFromPageId($dbr, $row->page_id);
				$wikitext = ContentHandler::getContentText( $r->getContent() );
				//$imgs = $this->hasImages($wikitext);
				$altsData = $this->hasAlternateMethods($wikitext) ? "Yes" : "No";
				$sizeData = $this->getArticleSize($r);
			}
			//$output .= join("\t", array_values(get_object_vars($row))) . "\t$imgs\t$altsData\t$sizeData\n";
			$row = array_values(get_object_vars($row));
			$row[1] = Misc::makeUrl($row[1]);
			$articles[] = $row;
		}


		if ($req->getVal('format') == 'csv') {
			$output = $this->getCategoryReportCSV($articles);
			$this->sendFile($cat, $output);
		} else {
			$output = $this->getCategoryReportHtml($articles);
			$this->sendHtml($output);
		}
	}

	private function getCategoryReportHtml(&$articles) {
		$header = array("page_id", "url", "views");
		/*
		if ($this->slowQuery) {
			$header[] = "alt_methods";
			$header[] = "byte_size";
		}
		*/

		$output = "<table><thead><th>";
		$output .= implode("</th><th>", $header);
		$output .= "</th></thead><tbody>";
		foreach ($articles as $article) {
			$output .= "<tr><td>";
			$article[1] = "<a href='{$article[1]}'>{$article[1]}</a>";
			$output .= implode("</td><td>", $article);
			$output .= "</td></tr>";
		}
		$output .= "</tbody><table>";

		return $output;
	}

	private function getCategoryReportCSV($articles) {
		$slowColumns = $this->slowQuery ? "\talt_methods\tbyte_size" : "";
		$output = "page_id\turl\tviews$slowColumns\n";
		foreach ($articles as $row) {
			$output .= join("\t", $row) . "\t$altsData\t$sizeData\n";
		}

		return $output;
	}

	private function hasImages(&$wikitext) {
		if ($this->introOnly) {
			$text = WikiText::getIntro($wikitext);
			$firstImage = Wikitext::getFirstImageURL($text);
			$hasImages = !empty($firstImage) ? "Yes" : "No";
		}
		else {
			list($stepsText, ) = Wikitext::getStepsSection($wikitext, true);
			if ($stepsText) {
				// has steps section, so assume valid candidate for detailed title
				$num_steps = preg_match_all('/^#[^*]/im', $stepsText, $matches);
			}
			$num_photos = preg_match_all('/\[\[Image:/im', $wikitext, $matches);
			$hasImages = $num_photos > ($num_steps / 2) ? "Yes" : "No";
		}

		return $hasImages;
	}

	private function hasAlternateMethods(&$wikitext) {
		return preg_match("@^===@m", $wikitext);
	}

	private function getArticleSize(&$object) {
		$size = 0;
		if ($object instanceof Title) {
			if (!is_null($r = Revision::newFromId($object->getLatestRevID()))) {
				$size = $r->getSize();
			}
		}

		if ($object instanceof Revision) {
			$size = $object->getSize();
		}
		return $size;
	}

	private function outputArticleReport() {
		$req = $this->getRequest();

		$urls = explode("\n", trim(Misc::getUrlDecodedData($req->getVal('data'))));
		$dbr = wfGetDB(DB_REPLICA);
		$articles = array();
		foreach ($urls as $url) {
			$t = Misc::getTitleFromText($url);
			if ($t && $t->exists()) {
				$articles[$t->getArticleId()] = array ('url' => Misc::makeUrl($t->getText()));
				if ($this->slowQuery) {
					$wikitext = Wikitext::getWikitext($dbr, $t);
					$articles[$t->getArticleId()]['alts'] = $this->hasAlternateMethods($wikitext) ? "Yes" : "No";
					$articles[$t->getArticleId()]['size'] = $this->getArticleSize($t);
					$articles[$t->getArticleId()]['imgs'] = $this->hasImages($wikitext);
				}
			}
		}
		$this->addPageCounts($articles);
		if ($req->getVal('format') == 'csv') {
			$output = $this->getArticleReportCSV($articles);
			$this->sendFile('article_stats', $output);
		} else {
			$output = $this->getArticleReportHtml($articles);
			$this->sendHtml($output);
		}
	}

	private function outputArticleIdReport() {
		$req = $this->getRequest();

		$ids = explode("\n", trim(Misc::getUrlDecodedData($req->getVal('data'))));
		$dbr = wfGetDB(DB_REPLICA);
		$articles = array();
		foreach ($ids as $id) {
			$id = trim($id);
			$t = Title::newFromId($id);
			if ($t && $t->exists()) {
				$articles[] = array("id" => $id, "url" => $t->getFullUrl());
			}
		}
		if ($req->getVal('format') == 'csv') {
			$output = $this->getArticleIdReportCSV($articles);
			$this->sendFile('article_stats', $output);
		} else {
			$output = $this->getArticleIdReportHtml($articles);
			$this->sendHtml($output);
		}
	}

	private function addPageCounts(&$articles) {
		$dbr = wfGetDB(DB_REPLICA);
		$aids = join(",", array_keys($articles));
		$res = $dbr->select('page', array('page_counter', 'page_id'), array("page_id IN ($aids)"));
		foreach ($res as $row) {
			$articles[$row->page_id]['views'] = $row->page_counter;
		}
	}

	private function getArticleReportHtml(&$articles) {
		$header = array("url");
		if ($this->slowQuery) {
			$header[] = "alt_methods";
			$header[] = "byte_size";
			$header[] = "images";
		}
		$header[] = "views";

		$output = "<table><thead><th>";
		$output .= implode("</th><th>", $header);
		$output .= "</th></thead><tbody>";
		foreach ($articles as $article) {
			$output .= "<tr><td>";
			$article['url'] = "<a href='{$article['url']}'>{$article['url']}</a>";
			$output .= implode("</td><td>", array_values($article));
			$output .= "</td></tr>";
		}
		$output .= "</tbody><table>";

		return $output;
	}

	private function getArticleReportCSV(&$articles) {
		$slowColumns = $this->slowQuery ? "\timages\talt_methods\tbyte_size" : "";
		$output = "url\tviews$slowColumns\n";

		foreach ($articles as $aid => $data) {
			$slowData = $this->slowQuery ? "\t{$data['imgs']}\t{$data['alts']}\t{$data['size']}" : "";
			$output .= "{$data['url']}\t{$data['views']}$slowData\n";
		}
		return $output;
	}

	private function getArticleIdReportHtml(&$articles) {
		$header = array("views", "url");

		$output = "<table><thead><th>";
		$output .= implode("</th><th>", $header);
		$output .= "</th></thead><tbody>";
		foreach ($articles as $article) {
			$output .= "<tr><td>";
			$article['url'] = "<a href='{$article['url']}'>{$article['url']}</a>";
			$output .= implode("</td><td>", $article);
			$output .= "</td></tr>";
		}
		$output .= "</tbody><table>";

		return $output;
	}

	private function getArticleIdReportCSV(&$articles) {
		$output = "id\turl\n";

		foreach ($articles as $data) {
			$output .= "{$data['id']}\t{$data['url']}\n";
		}
		return $output;
	}

	private function sendHtml(&$output) {
		$this->getOutput()->setArticleBodyOnly(true);
		print $output;
	}

	private function sendFile($filename, &$output) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$req->response()->header('Content-type: text/plain');
		$req->response()->header('Content-Disposition: attachment; filename="' . addslashes($filename) . '.txt"');
		$out->addHtml($output);
	}
}
