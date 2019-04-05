<?php

/**
 * A class that represents a wikiHow article. Used to add special processing
 * on top of the Article class (but without adding any explicit database
 * access methods).
 *
 * NOTE: this desperately needs to be refactored. It should inherit from WikiPage.
 */
class WikihowArticleEditor {

	/*private*/
	 var $mSteps, $mTitle, $mLoadText;
	 var $section_array;
	 var $section_ids;
	 var $mSummary, $mCategories, $mLangLinks;

	/*private*/
	 var $mTitleObj;

	/*private*/
	 var $mIsWikiHow, $mIsNew;

	 var $mWikiPage;

	const MULTI_SEP = '|';

	/*static*/
	public static $imageArray;

	private function __construct() {
		$this->mSteps = "";
		$this->mTitle = "";
		$this->mSummary = "";
		$this->mIsWikiHow = true;
		$this->mIsNew = true;
		$this->mCategories = array();
		$this->section_array = array();
		$this->section_ids = array();
		$this->mLangLinks = "";
		$this->mLoadText = "";
	}

	private static $instance = null;

	// singleton constructor
	static function singletonFromWikiPage($wikiPage) {
		if (!self::$instance) {
			self::$instance = new WikihowArticleEditor();

			self::$instance->mWikiPage = $wikiPage;
			self::$instance->mTitleObj = $wikiPage->getTitle();
			self::$instance->mTitle = $wikiPage->getTitle()->getText();
			$text = ContentHandler::getContentText($wikiPage->getContent(Revision::RAW));
			self::$instance->loadFromText($text);
		}
		return self::$instance;
	}

	// this function is here to support getting the wikitext from an article by title,
	// but will not initialize a new wikihowArticle editor.. it exists to support functions
	// that want the wikitext only if it matches the currently loaded one.
	static function wikiHowArticleIfMatchingTitle($title) {
		if (self::$instance && self::$instance->mTitle == $title) {
			return self::$instance;
		} else {
			return null;
		}
	}

	// will get an instance of this class with the given title..
	// will return the shared instance if it has the same title but will not overwrite the shared instance
	static function newFromTitle($title) {
		// see if we already have this as our main instance...
		if (self::$instance && self::$instance->mTitle == $title) {
			return self::$instance;
		}

		$wikiPage = new WikiPage($title);
		if (!$wikiPage) return null;
		$whow = new WikihowArticleEditor();
		$whow->mTitleObj = $wikiPage->getTitle();
		$whow->mWikiPage = $wikiPage;
		$whow->mTitle = $wikiPage->getTitle()->getText();
		$text = ContentHandler::getContentText($wikiPage->getContent(Revision::RAW));
		$whow->loadFromText($text);

		return $whow;
	}

	static function newFromText($text) {
		$whow = new WikihowArticleEditor();
		$whow->loadFromText($text);
		return $whow;
	}

	private function loadFromText($text) {
		global $wgContLang, $wgParser;

		$this->mLoadText = $text;
		// extract the category if there is one
		// TODO: make this an array

		$this->mCategories = array();

		// just extract 1 category for now
		//while ($index !== false && $index >= 0) { // fix for multiple categories
		preg_match_all("/\[\[" .  $wgContLang->getNSText(NS_CATEGORY) . ":[^\]]*\]\]/im", $text, $matches);
		foreach ($matches[0] as $cat) {
			$cat = str_replace("[[" . $wgContLang->getNSText(NS_CATEGORY) . ":", "", $cat);
			$cat = trim(str_replace("]]", "", $cat));
			$this->mCategories[] = $cat;
			$text = str_replace("[[" . $wgContLang->getNSText(NS_CATEGORY) . ":" . $cat . "]]", "", $text);
		}

		// extract interlanguage links
		$matches = array();
		if ( preg_match_all('/\[\[[a-z][a-z]:.*\]\]/', $text, $matches) ) {
			foreach ($matches[0] as $match) {
				$text = str_replace($match, "", $text);
				$this->mLangLinks .= "\n" . $match;
			}
		}
		$this->mLangLinks = trim($this->mLangLinks);

		// get the number of sections

		$sectionCount = self::getSectionCount($text);

		$found_summary = false;
		for ($i = 0; $i < $sectionCount; $i++) {
			$section = $wgParser->getSection($text, $i);
			$title = self::getSectionTitle($section);
			$section = trim(preg_replace("@^==.*==@", "", $section));
			$title = strtolower($title);
			$title = trim($title);
			if ($title == "" && !$found_summary) {
				$this->section_array["summary"] = $section;
				$this->section_ids["summary"] = $i;
				$found_summary = true;
			} else {
				$orig = $title;
				$counter = 0;
				while (isset($section_array[$title])) {
					$title = $orig + $counter;
				}
				$title = trim($title);
				$this->section_array[$title] = $section;
				$this->section_ids[$title] = $i;
			}
		}

		// set the steps
		// AA $index = strpos($text, "== Steps ==");
		// AA if (!$index) {
		if ($this->hasSection("steps") == false) {
			$this->mIsWikiHow = false;
			return;
		}

		$this->mSummary = $this->getSection("summary");
		$this->mSteps = $this->getSection(wfMessage('steps')->text());

		// TODO: get we get tips and warnings from getSection?
		$this->mIsNew = false;
	}

	// used by formatWikiText below
	private static function formatBulletList($text) {
		$result = "";
		if (!$text) return $result;
		$lines = explode("\n", $text);
		if (!is_array($lines)) return $result;
		foreach ($lines as $line) {
			if (strpos($line, "*") === 0) {
				$line = substr($line, 1);
			}
			$line = trim($line);
			if ($line != "") {
				$result .= "*$line\n";
			}
		}
		return $result;
	}

	/**
	 * Returns the index of the given section
	 * returns -1 if not known
	 */
	function getSectionNumber($section) {
		$section = strtolower($section);
		if ( !empty($this->section_ids[$section]) ) {
			return $this->section_ids[$section];
		} else {
			return -1;
		}
	}

	private function setSteps($steps) {
		$this->mSteps = $steps;
	}

	private function setTitle($title) {
		$this->mTitle = $title;
	}

	private function setSummary($summary) {
		$this->mSummary = $summary;
	}

	private function setCategoryString($categories) {
		$this->mCategories = explode(self::MULTI_SEP, $categories);
	}

	function getLangLinks() {
		return $this->mLangLinks;
	}

	private function setLangLinks($links) {
		$this->mLangLinks = $links;
	}

	function getSteps($forEditing = false) {
		return str_replace("\n\n", "\n", $this->mSteps);
	}

	function getTitle() {
		return $this->mTitle;
	}

	function getSummary() {
		return $this->mSummary;
	}

	/**
	 * This function is used in places where the intro is shown to help
	 * in various backend tools (Intro Image Adder, Video Adder, etc)
	 * This removes all images for these tools.
	 */
	static function removeWikitext($text) {
		global $wgParser, $wgTitle;

		//remove all images
		$text = preg_replace('@\[\[Image:[^\]]*\]\]@im', '', $text);

		//then turn wikitext into html
		$options = new ParserOptions();
		$text = $wgParser->parse($text, $wgTitle, $options)->getText();

		//need to remove all <pre></pre> tags (not sure why they sometimes get added
		$text = preg_replace('/\<pre\>/i', '', $text);
		$text = preg_replace('/\<\/pre\>/i', '', $text);

		return $text;
	}

	// DEPRECATED -- used only in GuidedEditor.php
	function getCategoryString() {
		$s = "";
		foreach ($this->mCategories as $cat) {
			$s .= $cat . self::MULTI_SEP;
		}
		return $s;
	}

	// USE OF THIS METHOD IS DEPRECATED
	// it munges the wikitext too much and can't handle alt methods
	// GuidedEditor.php is the only file that should use it
	function formatWikiText() {
		global $wgContLang;

		$text = $this->mSummary . "\n";

		// move all categories to the end of the intro
		$text = trim($text);
		foreach ($this->mCategories as $cat) {
			$cat = trim($cat);
			if ($cat) {
				$text .= "\n[[" . $wgContLang->getNSText(NS_CATEGORY) . ":$cat]]";
			}
		}

		$ingredients = $this->getSection("ingredients");
		if ($ingredients != null && $ingredients != "") {
			$tmp = self::formatBulletList($ingredients);
			if ($tmp) {
				$text .= "\n== "  . wfMessage('ingredients')->text() .  " ==\n" . $tmp;
			}
		}

		$step = explode("\n", $this->mSteps);
		$steps = "";
		foreach ($step as $s) {
			$s = preg_replace('@^[0-9]*@', '', $s);
			$index = strpos($s, ".");
			if ($index !== false &&  $index == 0) {
				$s = substr($s, 1);
			}
			$s = trim($s);
			if ($s == "") continue;
			if (strpos($s, "#") === 0) {
				$steps .= $s . "\n";
			} else {
				$steps .= "#" . $s . "\n";
			}
		}
		$this->mSteps = $steps;

		$text .= "\n== " . wfMessage('steps')->text() . " ==\n" . $this->mSteps;

		$tmp = $this->getSection("video");
		if ($tmp != "")
		  $text .= "\n== " . wfMessage('video')->text() . " ==\n" . trim($tmp) . "\n";

		// do the bullet sections
		$hasReferences = false;
		$bullet_lists = array("tips", "warnings", "thingsyoullneed", "related", "references", "sources");
		foreach ($bullet_lists as $b) {
			$tmp = self::formatBulletList($this->getSection($b));
			if ($tmp != "") {
				if ($b == "references") {
					$hasReferences = true;
				}
				if ($b == "sources" && $hasReferences == true) {
					$text .= $tmp;
				} else {
					$text .= "\n== " . wfMessage($b)->text() . " ==\n" . $tmp;
				}
			}
		}

		$text .= $this->mLangLinks;
		$text = str_replace("*{{reflist}}", "{{reflist}}", $text);

		// add the references div if necessary
		if (strpos($text, "<ref>") !== false && strpos($text, "{{reflist}}" === false)) {
			$rdiv = '{{reflist}}';
			$headline = "== "  . wfMessage('sources')->text() .  " ==";
			if (strpos($text, $headline) !== false) {
				$text = trim($text) . "\n$rdiv\n";
			} else {
				$text .=  "\n== " . wfMessage('sources')->text() . " ==\n" . $rdiv . "\n";
			}
		}

		return $text;
	}

	function getFullURL() {
		return $this->mTitleObj->getFullURL();
	}

	function getDBKey() {
		return $this->mTitleObj->getDBKey();
	}

	function isWikiHow() {
		return $this->mIsWikiHow;
	}

	/*
	 * We might want to update this function later to be more comprehensive.
	 * For now, if it has == Steps == in it, it's a wikiHow article.
	 *
	 * NOTE: this is pretty inefficient because another pass over the wikitext
	 *   needs to be made for every call.
	 */
	public static function articleIsWikiHow($wikiPage) {
		if (!($wikiPage instanceof WikiPage)) return false;
		if (!$wikiPage->getTitle()->inNamespace(NS_MAIN)) return false;
		$wikitext = $wikiPage->getText();
		$count = preg_match('/^==[ ]*' . wfMessage('steps')->text() . '[ ]*==/mi', $wikitext);
		return $count > 0;
	}

	/**
	 * Returns true if the guided editor can be used on this article.
	 * Iterates over the article's sections and makes sure it contains
	 * all the normal sections.
	 *
	 * DEPRECATED -- used only in extensions/wikihow/guidededitor/GuidedEditor.class.php
	 * to determine if we can load the article in the guided editor.
	 */
	public static function useWrapperForEdit($article) {
		global $wgWikiHowSections, $wgParser;

		$index = 0;
		$foundSteps = 0;
		$text = ContentHandler::getContentText( $article->getPage()->getContent() );

		$mw = MagicWord::get( 'forceadv' );
		if ($mw->match( $text ) ) {
			return false;
		}
		$count = self::getSectionCount($text);

		// these are the good titles, if we have a section title
		// with a title in this list, the guided editor can't handle it
		$sections = array();
		foreach ($wgWikiHowSections as $s) {
			$sections[] = wfMessage($s)->text();
		}

		while ($index < $count) {
			$section = $wgParser->getSection($text, $index);
			$title = self::getSectionTitle($section);

			if ($title == wfMessage('steps')->text()) {
				$foundSteps = true;
			} elseif ($title == "" && $index == 0) {
				// summary
			} elseif (!in_array($title, $sections)) {
				return false;
			}
			if (!$section) {
				break;
			}
			$index++;
		}

		if ($index <= 8) {
			return $foundSteps;
		} else {
			return false;
		}
	}

	private static function getSectionCount($text) {
		$matches = array();
		# Note from Reuben: I separate the ? character from the > characters below because
		# there are some syntax highlighters that interpret that as END OF PHP
		preg_match_all( '/^(=+).+?=+|^<h([1-6]).*?' . '>.*?<\/h[1-6].*?' . '>(?!\S)/mi', $text, $matches);
		return count($matches[0]) + 1;
	}

	 /**
	  * Given a MediaWiki section, such as
	  *   == Steps ===
	  *   1. This is the first step.
	  *   2. This is the second step.
	  *
	  * This function returns 'Steps'.
	  */
	public static function getSectionTitle($section) {
		$title = "";
		$index = strpos(trim($section), "==");
		if ($index !== false && $index == 0) {
			$index2 = strpos($section, "==", $index+2);
			if ($index2 !== false && $index2 > $index) {
				$index += 2;
				$title = substr($section, $index, $index2-$index);
				$title = trim($title);
			}
		}
		return $title;
	}

	function hasSection($title) {
		$ret = isset($this->section_array[strtolower(wfMessage($title)->text())]);
		if (!$ret) $ret = isset($this->section_array[$title]);
		return $ret;
	}

	function getSection($title) {
		$title = strtolower($title);
		if ($this->hasSection($title)) {
			$sectionName = strtolower(wfMessage($title)->text());
			$ret = isset($this->section_array[$sectionName]) ? $this->section_array[$sectionName] : null;
			$ret = empty($ret) ? $this->section_array[$title] : $ret;
			return $ret;
		} else {
			return "";
		}
	}

	private function setSection($title, $section) {
		$this->section_array[$title] = $section;
	}

	private function setRelatedString($related) {
		 $r_array = explode(self::MULTI_SEP, $related);
		 $result = "";
		 foreach ($r_array as $r) {
			 $r = trim($r);
			 if ($r == "") continue;
			 $result .= "* [[" . $r . "]]\n";
		 }
		 $this->setSection("related", $result);
	}

	// DEPRECATED -- used only in GuidedEditor.php
	static function newFromRequest($request) {
		$whow = new WikihowArticleEditor();
		$steps = $request->getText("steps");
		$tips  = $request->getText("tips");
		$warnings = $request->getText("warnings");
		$summary =  $request->getText("summary");

		$category = "";
		$categories = "";
		for ($i = 0; $i < 2; $i++) {
			if ($request->getVal("category" . $i)) {
				if ($categories) $categories .= self::MULTI_SEP;
				$categories .= $request->getVal("category" . $i);
			} elseif ($request->getVal('topcategory' . $i) && $request->getVal('TopLevelCategoryOk') == 'true') {
				if ($categories) $categories .= self::MULTI_SEP;
				$categories .= $request->getVal("topcategory" . $i);
			}
		}

		$hidden_cats = $request->getText("categories22");
		if ($categories == "" && $hidden_cats) {
			$categories = $hidden_cats;
		}

		$ingredients = $request->getText("ingredients");

		$whow->setSection("ingredients", $ingredients);
		$whow->setSteps($steps);
		$whow->setSection('tips', $tips);
		$whow->setSection('warnings', $warnings);
		$whow->setSummary($summary);
		$whow->setSection("thingsyoullneed", $request->getVal("thingsyoullneed"));
		$whow->setLangLinks($request->getVal('langlinks'));

		$related_no_js = $request->getVal('related_no_js');
		$no_js = $request->getVal('no_js');

		if ($no_js) {
			$whow->setSection("related", $related_no_js);
		} else {
			// user has javascript
			$whow->setRelatedString($request->getVal("related_list"));
		}
		$whow->setSection("sources", $request->getVal("sources"));
		$whow->setSection("references", $request->getVal("references"));
		$whow->setSection("video", $request->getVal("video"));
		$whow->setCategoryString($categories);
		return $whow;
	}

	/**
	 *
	 * Convert wikitext to plain text
	 *
	 * @param    text  The wikitext
	 * @param    options An array of options that you would like to keep in the text
	 *				"category": Keep category tags
	 *				"image": Keep image tags
	 *				"internallinks": Keep internal links the way they are
	 *				"externallinks": Keep external links the way they are
	 *				"headings": Keep the headings tags
	 *				"templates": Keep templates
	 *				"bullets": Keep bullets
	 * @return     text
	 *
	 */
	public static function textify($text, $options = array()) {
		// take out category and image links
		$tags = array();
		if (!isset($options["category"])) {
			$tags[] = "Category";
		}
		if (!isset($options["image"])) {
			$tags[] = "Image";
		}
		$text = preg_replace("@^#[ ]*@m", "", $text);
		foreach ($tags as $tag) {
			$text = preg_replace("@\[\[{$tag}:[^\]]*\]\]@", "", $text);
		}

		// take out internal links
		if (!isset($options["internallinks"])) {
			preg_match_all("@\[\[[^\]]*\|[^\]]*\]\]@", $text, $matches);
			foreach ($matches[0] as $m) {
				$n = preg_replace("@.*\|@", "", $m);
				$n = preg_replace("@\]\]@", "", $n);
				$text = str_replace($m, $n, $text);
			}

			// internal links with no alternate text
			$text = preg_replace("@\]\]|\[\[@", "", $text);
		}

		// external links
		if (isset($options["remove_ext_links"])) {
			// for [http://google.com proper links]
			$text = preg_replace("@\[[^\]]*\]@", "", $text);
			// for http://www.inlinedlinks.com
			$text = preg_replace("@http://[^ |\n]*@", "", $text);
		} elseif (!isset($options["externallinks"])) {
			// take out internal links
			preg_match_all("@\[[^\]]*\]@", $text, $matches);
			foreach ($matches[0] as $m) {
				$n = preg_replace("@^[^ ]*@", "", $m);
				$n = preg_replace("@\]@", "", $n);
				$text = str_replace($m, $n, $text);
			}
		}

		// headings tags
		if (!isset($options["headings"])) {

			if (isset($options["no-heading-text"])) {
				$text = preg_replace("@^[=]+[^=]+[=]+$@m", "", $text);
			} else {
				$text = preg_replace("@^[=]+@m", "", $text);
				$text = preg_replace("@[=]+$@m", "", $text);
			}
		}

		// templates
		if (!isset($options["templates"])) {
			$text = preg_replace("@\{\{[^\}]*\}\}@", "", $text);
		}

		// bullets
		if (!isset($options["bullets"])) {
			$text = preg_replace("@^[\*|#]*@m", "", $text);
		}

		// leading space
		$text = preg_replace("@^[ ]*@m", "", $text);

		// kill html
		$text = strip_tags($text);

		return trim($text);
	}

	// Removes method prefix in alt method names, and returns types of alt methods for display to user
	static function removeMethodNamePrefix(&$name) {
		global $wgLanguageCode;
		$ret = array('has_parts' => false, 'has_methods' => true);
		$count = 0;

		// For English we use the partRegex. For international, we allow multiple words for part line-seperated in the parts message
		$partRegex = '@^Part [^:.-]+[:.-]@';
		if ($wgLanguageCode != 'en') {
			$parts = preg_split('@[\r\n]+@', wfMessage('parts')->text());
			if ($parts) {
				$partRegex = array();
				foreach ($parts as $part) {
					$partRegex[] = '@^' . preg_quote($part, '@') . '[^:]*(:|$)@i';
				}
			}
		}

		$name = preg_replace($partRegex, '', $name, -1, $count);
		if ($count > 0) {
			$name = trim($name);
			$ret['has_parts'] = true;
			$ret['has_methods'] = false;
			return $ret;
		}

		// For English we use the methodRegex and respective matchRegex
		// For international, we allow multiple words for method line-seperated in the methods message, but only method names of a single regex form are allowed
		$methodRegex = array('@^Method [^:.-]+([:.-]|$)@', '@^Option [^:-]+[:-]@', '@^Project [^:-]+[:-]@', '@^Methods$@', '@^Method \d+ of \d+@', '@^(First|Second|Third|[A-Z][a-z]+th) Method([:-]|$)@', '@^Method[ -]\d+\s*\(([^)]+)\)$@');
		$matchRegex = array('', '', '', '', '', '', '$1');
		if ($wgLanguageCode != 'en') {
			$methods = preg_split("@[\r\n]+@", wfMessage('methods')->text());
			if ($methods) {
				$methodRegex = array();
				$matchRegex = array();
				foreach ($methods as $method) {
					$methodRegex[] = '@^' . preg_quote($method) . ' [^:.-]+([:.-])@i';
					$matchRegex[] = '';
				}
				foreach ($methods as $method) {
					$methodRegex[] = '@^' . preg_quote($method) . '@i';
					$matchRegex[] = '';

				}
			}
		}
		$name = preg_replace(
			$methodRegex,
			$matchRegex,
			$name, -1, $count);
		if ($count > 0) {
			$name = trim($name);
		}
		return $ret;
	}

	public static function grabArticleEditLinks($isGuided) {
		$ctx = RequestContext::getMain();

		$title = $ctx->getTitle();
		// Fix Exception when getWikiPage is called on virtual namespace page
		if ($title->inNamespace(NS_SPECIAL)) {
			return '';
		}

		$wikiPage = $ctx->getWikiPage();
		$req = $ctx->getRequest();
		$langCode = $ctx->getLanguage()->getCode();

		$relHTML = '';
		$relBtn = '';
		$editLink = '';
		$editHelp = '';
		$warning = '';

		if (self::articleIsWikiHow($wikiPage)
			|| ($title->getArticleID() == 0
				&& $title->inNamespace(NS_MAIN))
		) {
			$oldParams = [];
			$oldid = $req->getInt('oldid');
			if ($oldid) {
				$oldParams = ['oldid' => $oldid];
			}
			if ($isGuided) {
				$editLink = Linker::linkKnown($title,
					wfMessage('advanced_editing_link')->text(),
					[],
					['action' => 'edit', 'advanced' => 'true'] + $oldParams);
				// weave links button
				$relBtn = $langCode == 'en' ? PopBox::getGuidedEditorButton() : '';
				$relHTML = PopBox::getPopBoxJSGuided();
			} else {
				$article = Article::newFromWikiPage($wikiPage, $ctx);
				$possibleInGuided = GuidedEditor::possibleInGuidedEditor($req, $title, $article);
				if ($possibleInGuided) {
					$editLink = Linker::linkKnown($title,
						wfMessage('guided_editing_link')->text(),
						[],
						['action' => 'edit', 'override' => 'yes'] + $oldParams);
				} else {
					$warning = '<span class="greywarning">Guided Editing not available for this article</span>';
				}
			}

			$helpTitle = Title::newFromText( wfMessage('edithelppage')->inContentLanguage()->text() );
			$editHelp = Linker::linkKnown( $helpTitle,
				wfMessage('edithelp_link')->text(),
				['target' => '_blank']);
		}

		// Take out switch to guided editing and editing help on edit page for logged out users on international
		if ($langCode == "en" || $title->userCan('edit')) {
			$editlinks = $relHTML . '<div class="editpage_links">' . $editLink . ' ' . $relBtn . ' ' . $editHelp . ' ' . $warning . '</div>';
		} else {
			$editlinks = '';
		}

		return $editlinks;
	}

	static function setImageSections($articleText) {
		global $wgContLang;

		$sectionArray = array("summary", "steps", "video", "tips", "warnings", "things you'll need", "related wikihows", "ingredients", "sources and citations", "references");

		self::$imageArray = array();

		$who = WikihowArticleEditor::newFromText($articleText);
		$nsTxt = "(Image|" . $wgContLang->getNsText(NS_IMAGE) . ")";

		foreach ($who->section_array as $section => $sectionText) {
			if (!in_array($section, $sectionArray))
				$section = "steps";
			if (preg_match_all("@([\*]*)\[\[" . $nsTxt . ":([^\|\]]+)[^\]]*\]\]@im", $sectionText, $matches) > 0) {
				foreach ($matches[3] as $index => $match) {
					$match = str_replace(" ", "-", $match);
					if ($section == "steps" && $matches[1][$index] == "*")
						self::$imageArray[ucfirst($match)] = "substep";
					else
						self::$imageArray[ucfirst($match)] = $section;
				}
			}

			if (preg_match_all("@\{\{largeimage\|([^\}]+)\}\}@im", $sectionText, $matchesLarge) > 0) {
				foreach ($matchesLarge[1] as $match) {
					//apparently some large images still have captions so get rid of everything else
					$parts = explode("|", $match);
					$imageName = str_replace(" ", "-", $parts[0]);
					self::$imageArray[ucfirst($imageName)] = $section;
				}
			}

			// wiki vids can have default images as their third parameter and we want
			// to add these to the imageArray if they are present
			if (preg_match_all("@\{\{whvid\|([^\}]+)\}\}@im", $sectionText, $matchesVid) > 0) {
				foreach ($matchesVid[1] as $match) {
					$imageTitle = null;
					$parts = explode("|", $match);
					if (count($parts) >= 3) {
						$previewImageTitle = Title::newFromText($parts[1], NS_IMAGE);
						$imageTitle = Title::newFromText($parts[2], NS_IMAGE);
					} elseif (count($parts) == 2) {
						$previewImageTitle = Title::newFromText($parts[1]);
					}

					if ($imageTitle) {
						// Liam: changed this from getPartialUrl to getText to avoid placement of videos below steps
						// for images with apostrophes or other special chars in the name
						$encodedImageName = $imageTitle->getPartialURL(); // for backwards compatibility -- may be unused
						$imageName = str_replace(" ", "-", $imageTitle->getText());
						self::$imageArray[ucfirst($imageName)] = $section;
						self::$imageArray[ucfirst($encodedImageName)] = $section; // backwards compatibiliy
					}

					if ( $previewImageTitle ) {
						// Liam: changed this from getPartialUrl to getText to avoid placement of videos below steps
						// for images with apostrophes or other special chars in the name
						$encodedImageName = $previewImageTitle->getPartialURL(); // for backwards compatibility -- may be unused
						$imageName = str_replace(" ", "-", $previewImageTitle->getText());
						self::$imageArray[ucfirst($imageName)] = $section;
						self::$imageArray[ucfirst($encodedImageName)] = $section; // backwards compatibility
					}
				}
			}
		}
	}

	static function getImageSection($image) {
		$imgName = ucfirst($image);
		return isset(self::$imageArray[$imgName]) ? self::$imageArray[$imgName] : '';
	}

	static function resolveRedirects($title) {
		$res = null;
		$i = 5; // max redirects
		$dbr = wfGetDB(DB_REPLICA);

		while ($i > 0 && $title && $title->exists()) {
			$titleKey = $dbr->selectField('redirect',
				'rd_title',
				[	'rd_from' => $title->getArticleID(),
					'rd_namespace' => $title->getNamespace()],
				__METHOD__);
			if (!$titleKey) break;
			$title = Title::newFromDBkey($titleKey);
			$i--;
		}
		if ($i > 0 && $title) {
			$res = $title;
		}
		return $res;
	}
}

class BuildWikihowArticle extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('BuildWikihowArticle');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$whow = WikihowArticleEditor::newFromRequest($req);
		if ($req->getVal('parse') == '1') {
			$body = $out->parse($whow->formatWikiText());
			$magic = WikihowArticleHTML::grabTheMagic($whow->formatWikiText());
			echo WikihowArticleHTML::processArticleHTML($body, array('magic-word' => $magic));
		} else {
			echo $whow->formatWikiText();
		}
	}
}
