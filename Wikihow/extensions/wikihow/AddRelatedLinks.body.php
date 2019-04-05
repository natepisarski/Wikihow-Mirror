<?php

class AddRelatedLinks extends UnlistedSpecialPage {

	public static $ignore_cats = array(
        "Nominations for Deletion",
        "NFD (Accuracy)",
        "NFD (Advertisement)",
        "NFD (Below Character Article Standards)",
        "Copyright Problems",
        "NFD (Dangerous)",
        "NFD (Drug Focused)",
        "NFD (Duplicate)",
        "NFD (Hate or Racist Based)",
        "NFD (Impossible Instructions)",
        "NFD (Incomplete)",
        "NFD (Invalid or Expired Datestamp)",
        "NFD (Joke)",
        "NFD (Mean Spirited)",
        "NFD (Not a How to)",
        "NFD (Other)",
        "NFD (Political Opinion)",
        "NFD (Sarcastic)",
        "NFD (Sexually Charged)",
        "NFD (Societal Instructions)",
        "Speedy",
        "Speedy Image Deletion",
        "NFD (Universally Illegal)",
        "NFD (Vanity)",
        "Copyedit",
        "Pages Needing Attention",
        "Stub",
        "Merge",
        "Format",
        "Accuracy",
        "Cleanup",
        "Pictures",
        "Featured Articles",
        "Character",
        "Personal",
        "Title",
        "Summarization",
        "Unclear Articles",
        "Articles in Need of Sources",
        "Articles Needing Video",
        "Articles to be Split",
        "Gender Biased Pages",
        "RLtesting",
        "Subjective Articles",
        "Very Long Articles",
	);

	public function __construct() {
		parent::__construct( 'AddRelatedLinks' );
	}

	private function addLinkToRandomArticleInSameCategory($t, $summary = "Adding links", $linktext = null) {
		$out = $this->getOutput();
		$cats = array_keys($t->getParentCategories());
		$found = false;
		while (sizeof($cats) > 0) {
			$cat = array_shift($cats);
			$cat = preg_replace("@^Category:@", "", $cat);
			$cat = Title::newFromText($cat);
			if (in_array($cat->getText(), self::$ignore_cats)) {
				#echo "ignoring cat {$cat->getText()}\n";
				continue;
			}
			#echo "using cat {$cat->getText()} for {$t->getFullText()}\n";
			$dbr = wfGetDB(DB_REPLICA);
			$id  = $dbr->selectField(array('categorylinks', 'page'),
					array('cl_from'),
					array('cl_to'=>$cat->getDBKey(), 'page_id = cl_from', 'page_namespace'=>NS_MAIN, 'page_is_redirect'=>0),
					__METHOD__,
					array("ORDER BY"=>"rand()", "LIMIT"=>1));
			if (!$id) {
				$out->addHTML("<li>Couldn't get a category/enough results for <b>{$t->getText()}</b></li>\n");
				continue;
			}
			$src = Title::newFromID($id);
			#$out->addHTML("<li>Linked from <b>{$src->getText()}</b> to <b>{$t->getText()}</b> by picking a random article from the same category</li>\n");
			#kecho("Linked from {$src->getText()} to {$t->getText()} by picking a random article from the same category</li>\n");
			MarkRelated::addRelated($src, $t, $summary, true, $linktext);
			$found = true;
			return $src;
		}
		#if (!$found)
			#echo "Count'd find anything for {$t->getFullText()} " . print_r(array_keys($t->getParentCategories()), true) . "\n";
		return null;
	}

	public function execute($par) {
		global $wgUser;

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		if (!in_array('staff', $user->getGroups())) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$out->addHTML(<<<END
			<form action='/Special:AddRelatedLinks' method='post' enctype="multipart/form-data" >
			Pages to add links to (Urls) : <textarea name='xml'></textarea>
			<input type='submit'>
			</form>
END
		);

		if (!$req->wasPosted()) {
			return;
		}

		set_time_limit(3000);

		$dbr = wfGetDB(DB_REPLICA);
		$urls = array_unique(explode("\n", $req->getVal('xml')));

		$user = null;
		$wgUser = User::newFromName("Wendy Weaver");

		$out->addHTML("Started at " . date("r") . "<ul>");
		foreach ($urls as $url) {
			$url = trim($url);
			if ($url == "") continue;
			$url = preg_replace("@https?://www.wikihow.com/@im", "", $url);
			$t = Title::newFromURL($url);
			if (!$t) {
				$out->addHTML("<li>Can't make title out of {$url}</li>\n");
				continue;
			}
			$rev = Revision::newFromTitle($t);
			if (!$rev) {
				$out->addHTML("<li>Can't make revision out of {$url}</li>\n");
				continue;
			}
			$text = ContentHandler::getContentText( $rev->getContent() );
			$search = new LSearch();
			$results = $search->externalSearchResultTitles($t->getText(), 0, 30, 7);
			$good = array();
			foreach ($results as $res) {
				if ($res->getText() == $t->getText())
					continue;
				if (!$res->inNamespace(NS_MAIN))
					continue;
				if (preg_match("@\[\[{$t->getText()}@", $text))
					continue;
				$good[] = $res;
				if (sizeof($good) >= 4) break;
			}

			if (sizeof($good) == 0)  {
				$src = $this->addLinkToRandomArticleInSameCategory($t);
				if ($src) {
					$out->addHTML("<li>Linked from <b><a href='{$src->getFullURL()}?action=history' target='new'>{$src->getText()}</a></b> to <b><a href='{$t->getFullURL()}' target='new'>{$t->getText()}</a></b> (random)</li>\n");
				} else {
					$out->addHTML("<li>Could not find appropriate links for <b><a href='{$t->getFullURL()}' target='new'>{$t->getText()}</a></b></li>\n");
				}
			} else {
				$x = rand(0, min(4, sizeof($good) - 1));
				$src = $good[$x];
				$out->addHTML("<li>Linked from <b><a href='{$src->getFullURL()}?action=history' target='new'>{$src->getText()}</a></b> to <b><a href='{$t->getFullURL()}' target='new'>{$t->getText()}</a></b> (search)</li>\n");
				MarkRelated::addRelated($src, $t, "Weaving the web of links", true);
			}
		}
		$out->addHTML("</ul>Finished at " . date("r") );
	}
}
