<?php

class ImportVideo extends SpecialPage {

	// youtube, 5min, etc.
	public $mSource;

	//public $mResponseData = array(), $mCurrentNode, $mResults, $mCurrentTag = array();

	public function __construct($source = null) {
		parent::__construct( 'ImportVideo' );
		$this->mSource = $source;
	}

	/**
	 *  Returns a title of a newly created article that needs a video
	 */
	private function getNewArticleWithoutVideo() {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		$req = $this->getRequest();
		$t = null;
		$dbr = wfGetDB(DB_REPLICA);
		$vidns = NS_VIDEO;
		$skip_sql = "";
		$skipVal = $req->getInt('skip');
		if ($skipVal) {
			$skip_sql = " AND nap_page < $skipVal";
			setcookie( $wgCookiePrefix.'SkipNewVideo', $skipVal, time() + 86400, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		} elseif ( isset( $_COOKIE["{$wgCookiePrefix}SkipNewVideo"] ) ) {
			$skip_sql = " AND nap_page < " . intval($_COOKIE["{$wgCookiePrefix}SkipNewVideo"]);
		}
		$sql = "SELECT nap_page
				FROM newarticlepatrol
					LEFT JOIN templatelinks t1 ON t1.tl_from = nap_page and t1.tl_namespace = {$vidns}
					LEFT JOIN templatelinks t2 on t2.tl_from =  nap_page and t2.tl_title IN ('Nfd', 'Copyvio', 'Merge', 'Speedy')
					LEFT JOIN page on  nap_page = page_id
			WHERE nap_patrolled =1 AND t1.tl_title is NULL AND nap_page != 0  AND t2.tl_title is null AND page_is_redirect = 0 {$skip_sql}
			ORDER BY nap_page desc LIMIT 1;";
		$res = $dbr->query($sql, __METHOD__);
		if ($row = $dbr->fetchObject($res)) {
			$t = Title::newFromID($row->nap_page);
		}
		return $t;
	}

	/**
	 *  Returns an article from a specific category that requires a video
	 */
	private function getTitleFromCategory($category) {
		$cat = Title::makeTitle(NS_CATEGORY, $category);
		$t	 = null;
		$dbw = wfGetDB(DB_MASTER);
		$sql = "SELECT page_title
				FROM page
				LEFT JOIN templatelinks ON tl_from=page_id AND tl_namespace=" . NS_VIDEO . "
				LEFT JOIN categorylinks ON cl_from = page_id
				WHERE tl_title is NULL
					AND	cl_to = " . $dbw->addQuotes($cat->getDBKey()) . "
				ORDER BY rand() LIMIT 1;";
		$res = $dbw->query($sql, __METHOD__);
		if ($row = $dbw->fetchObject($res))
			$t = Title::newFromText($row->page_title);
		return $t;
	}

	/**
	 * Processes a search for users who are looking for an article to
	 * add a video to
	 */
	private function doSearch($target, $orderby, $query, $search) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$me = Title::makeTitle(NS_SPECIAL, 'ImportVideo');
		$out->addHTML(wfMessage('importvideo_searchinstructions')->text() .
			"<br/><br/><form action='{$me->getFullURL()}'>
					<input type='hidden' name='target' value='" . htmlspecialchars($target) . "'/>
					<input type='hidden' name='orderby' value='{$orderby}'/>
					<input type='hidden' name='popup' value='{$req->getVal('popup')}'/>
					<input type='hidden' name='q' value='" . htmlspecialchars($query) . "' >
					<input type='text' name='dosearch' value='" . ($search != "1" ? htmlspecialchars($search) : "") . "' size='40'/>
					<input type='submit' value='" . wfMessage('importvideo_search') . "'/>
				</form>
				<br/>");
		if ($search != "1") {
			$l = new LSearch();
			$results = $l->externalSearchResultTitles($search);
			$base_url = $me->getFullURL() . "?&q=" . urlencode($query) . "&source={$source}";
			if (sizeof($results) == 0) {
				$out->addHTML(wfMessage('importvideo_noarticlehits')->text());
				return;
			}
			#output the results
			$out->addHTML(wfMessage("importvideo_articlesearchresults")->text() . "<ul>");
			foreach ($results as $t) {
			$out->addHTML("<li><a href='" . $base_url . "&target=" . urlencode($t->getText()) . "'>"
					. wfMessage('howto', $t->getText() . "</a></li>"));
			}
			$out->addHTML("</ul>");
		}
	}

	/**
	 * Maintain modes through URL parameters
	 */
	protected function getURLExtras() {
		$req = $this->getRequest();
		$popup		= $req->getVal('popup') == 'true' ? "&popup=true" : "";
		$rand		= $req->getVal('new') || $req->getVal('wasnew')
						? "&wasnew=1" : "";
		$bycat		= $req->getVal('category') ? "&category=" . urlencode($req->getVal('category')) : "";
		$orderby	= $req->getVal('orderby') ? "&orderby=" . $req->getVal('orderby') : "";
		return $popup . $rand. $bycat . $orderby;
	}

	public function execute($par) {
		global $wgImportVideoSources, $wgParser;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($req->getVal('popup') == 'true') {
			$out->setArticleBodyOnly(true);
		}
		$this->setHeaders();
		$out->addModules( ['ext.wikihow.ImportVideo'] );
		$source = $this->mSource = $req->getVal('source', 'youtube');
		$target = isset($par) ? $par : $req->getVal('target');
		$query = $req->getVal('q');
		$me = Title::makeTitle(NS_SPECIAL, "ImportVideo");
		$wasnew = $this->getRequest()->getVal('wasnew');

		// some sanity checks on the target
		if ($target && !$wasnew) {
			$title = Title::newFromURL($target);
			if (!$title || !$title->exists()) {
				$out->addHTML("Error: target article does not exist.");
				return;
			} else {
				$wikiPage = WikiPage::factory($title);
				if ($wikiPage->isRedirect()) {
					$out->addHTML("Error: target article is a redirect.");
					return;
				}
			}
		}

		$out->addHTML("<div id='importvideo'>");
		$out->addHTML("<h2>".wfMessage('add_a_video')->text()."</h2>");
		# changing target article feature
		$search = $req->getVal("dosearch");
		if ($search) {
			$this->doSearch($target, $orderby, $query, $search);
			return;
		}
		$sp = null;
		switch ($source) {
			case 'howcast':
				$sp = new ImportVideoHowcast($source);
				break;
			case 'youtube':
			default:
				$sp = new ImportVideoYoutube($source);
				break;
		}

		// handle special cases where user is adding a video to a new article or by category
		if ($req->getVal('new') || $req->getVal('wasnew')) {
			if ($req->getVal('new')) {
				$t = $this->getNewArticleWithoutVideo();
				$target = $t->getText();
			} else {
				$t = Title::newFromText($target);
			}
			$req->setVal('target', $target);
		} elseif ($req->getVal('category') && $target == '') {
			$t = $this->getTitleFromCategory($req->getVal('category'));
			$target = $t->getText();
			$req->setVal('target', $target);
		}

		// construct base url to switch between sources
		$url = $me->getFullURL() . "?target=" . urlencode($target) . "&q=" . urlencode($query) . $this->getURLExtras() . "&source=";

		$title = Title::newFromText($target);
		if (!trim($target)) {
			$out->addHTML("Error: no target specified.");
			return;
		}
		$target = $title->getText();

		//get the steps and intro to show to the user
		$r = Revision::newFromTitle($title);
		$text = "";
		if ($r) {
			$text = ContentHandler::getContentText( $r->getContent() );
		}
		$extra  = $wgParser->getSection($text, 0);
		$steps = "";
		for ($i = 1; $i < 3; $i++) {
			$xx = $wgParser->getSection($text, $i);
			if (preg_match("/^==[ ]+" . wfMessage('steps') . "/", $xx)) {
				$steps = $xx;
				break;
			}
		}
		$extra = preg_replace("/{{[^}]*}}/", "", $extra);
		$extra = $out->parse($extra);
		$steps = $out->parse($steps);
		$cancel = "";

		$nextlink = "/Special:ImportVideo?new=1&skip={$title->getArticleID()}";
		if ($req->getVal('category'))
			$nextlink = "/Special:ImportVideo?category=" . urlencode($req->getVal('category'));

		if ($req->getVal('popup') != 'true') {
			$out->addHTML("<div class='article_title'>
				" . wfMessage('importvideo_article')->text() . "- <a href='{$title->getFullURL()}' target='new'>" . wfMessage('howto', $title->getText()) . "</a>");
			$out->addHTML("<spanid='showhide' style='font-size: 80%; text-align:right; font-weight: normal;'>
					(<a href='{$nextlink}' accesskey='s'>next article</a> |
					<a href='$url&dosearch=1' accesskey='s'>" . wfMessage('importvideo_searchforarticle')->text() . "</a> {$cancel} )
				</span>");
			if ($req->getVal('category')) {
				$out->addHTML("You are adding videos to articles from the \"{$req->getVal('category')}\" category.
					(<a href='#'>change</a>)");
			}
			$out->addHTML("</div>");

			$out->addHTML("<div class='video_related wh_block'>
					<h2>Introduction</h2>
					{$extra}
					<br clear='all'/>
					<div id='showhide' style='font-size: 80%; text-align:right;'>
						<span id='showsteps'><a href='#' onclick='WH.ImportVideo.showhidesteps(); return false;'>" . wfMessage('importvideo_showsteps')->text() . "</a></span>
						<span id='hidesteps' style='display: none;'><a href='#' onclick='WH.ImportVideo.showhidesteps(); return false;'>" . wfMessage('importvideo_hidesteps')->text() . "</a></span>
					</div>
					<div id='stepsarea' style='display: none;'>
					{$steps}
					</div>
					<br clear='all'/>
				</div>
			");
		}
		$out->addHTML("<script type='text/javascript'>
			var isPopUp = " . ($req->getVal('popup') ?  "true" : "false") . ";
			</script>");

		if (!$req->wasPosted()) {
			$out->addHTML( wfMessage('add_video_info')->parseAsBlock() );
			# HEADER for import page
			$url = $me->getFullURL() . "?target=" . urlencode($target) . "&q=" . urlencode($query) . $this->getURLExtras(). "&source=";

			// refine form
			$orderby = $req->getVal('orderby', 'relevance');
			$out->addHTML($this->refineForm($me, $target, $req->getVal('popup') == 'true', $query, $orderby));

			// sources tab
			$out->addHTML("<ul id='importvideo_search_tabs'>");
			foreach ($wgImportVideoSources as $s) {
				$selected = ($s == $source) ? ' class="iv_selected"' : '';
				$out->addHTML("<li$selected><a href='{$url}{$s}'>" . wfMessage('importvideo_source_' . $s)->text() . "</a></li>");
			}
			$out->addHTML("</ul>");

			$vt = Title::makeTitle(NS_VIDEO, $target);
			if ($vt->getArticleID() > 0 && $req->getVal('popup') != 'true') {
				$out->addHTML("<div class='wh_block importvideo_main'>" . wfMessage('importvideo_videoexists', $vt->getFullText())->parse() . "</div>");
			}
		}

		//special class just for pop-ups
		if ($req->getVal('popup')) $pop_class = 'importvideo_pop';

		$out->addHTML("<div class='wh_block importvideo_main $pop_class'>");
		$sp->execute($par);
		$out->addHTML("</div>");	//Bebeth: took out extra closing div
		$out->addHTML("</div>");	//Scott: put a brand new extra closing div in (take that, Bebeth!)
	}

	protected function refineForm($me, $target, $popup, $query, $orderby = '') {
		$req = $this->getRequest();
		$p 		= $popup ? "true" : "false";
		$rand 	= $req->getVal('new') || $req->getVal('wasnew')
					? "<input type='hidden' name='wasnew' value='1'/>" : "";
		$cat   	= $req->getVal('category') != ""
					? "<input type='hidden' name='category' value=\"" . htmlspecialchars($req->getVal('category')) . "\"/>" : "";
		if ($query == '') $query = $target;
		return "<div style='text-align:center; margin-top: 5px; padding: 3px;'>
			<form action='{$me->getFullURL()}' name='refineSearch' method='GET'>
			<input type='hidden' name='target' value=\"" . htmlspecialchars($target) . "\"/>
			<input type='hidden' name='popup' value='{$p}'/>
			{$rand}
			<input type='hidden' name='orderby' value='{$orderby}'/>
			<input type='hidden' name='source' value='{$this->mSource}'/>
			{$cat}
			<input type='text' name='q' value=\"" . htmlspecialchars($query) . "\" id='refinesearch_input' class='search_input' />
			<input type='submit' class='button' value='" . wfMessage('importvideo_refine') . "'/>
			</form></div>
			<br/>";
	}

	protected function getPostForm($target) {
		$req = $this->getRequest();
		$me = Title::makeTitle(NS_SPECIAL, "ImportVideo");
		$tar_es = htmlspecialchars($target);
		$query = $req->getVal('q');
		$popup = $req->getVal('popup') == "true" ?  "true" : "false" ;
		$rand = $req->getVal('new') || $req->getVal('wasnew')
					? "<input type='hidden' name='wasnew' value='1'/>" : "";
		$cat = $req->getVal('category') != ""
					? "<input type='hidden' name='category' value=\"" . htmlspecialchars($req->getVal('category')) . "\"/>" : "";
		return "<form method='POST' action='{$me->getFullURL()}' name='videouploadform' id='videouploadform'>
				<input type='hidden' name='description' value='' />
				<input type='hidden' name='url' id='url' value='/Special:ImportVideo?{$_SERVER['QUERY_STRING']}'/>
				<input type='hidden' name='popup' value='{$req->getVal('popup')}'/>
				{$rand}
				{$cat}
				<input type='hidden' name='video_id' value=''/>
				<input type='hidden' name='target' value=\"{$tar_es}\"/>
				<input type='hidden' name='source' value='{$this->mSource}'/>   </form>
		";
	}

	protected function getResults($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$contents = curl_exec($ch);

		if (curl_errno($ch)) {
			echo "curl error {$url}: " . curl_error($ch) . "\n";
		}
		curl_close($ch);
		return $contents;
	}

	// Called by VideoAdder
	public static function updateVideoArticle($title, $text, $editSummary) {
		$wikiPage = WikiPage::factory($title);
		$content = ContentHandler::makeContent($text, $title);
		$wikiPage->doEditContent($content, $editSummary);
		self::markVideoAsPatrolled($wikiPage->getId());
	}

	// Called externally from video embed helper
	public static function markVideoAsPatrolled($article_id) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('recentchanges',
			array('rc_patrolled'=>1),
			array('rc_namespace'=>NS_VIDEO, 'rc_cur_id'=>$article_id),
			__METHOD__,
			array("ORDER BY" => "rc_id DESC", "LIMIT"=>1));
	}

	protected function urlCleaner($url) {
	  $U = explode(' ', $url);

	  $W = array();
	  foreach ($U as $k => $u) {
		if (stristr($u, 'http') || (count(explode('.', $u)) > 1)) {
		  unset($U[$k]);
		  return $this->urlCleaner( implode(' ', $U) );
		}
	  }
	  return implode(' ', $U);
	}

	public static function updateMainArticle($target, $editSummary) {
		$ctx = RequestContext::getMain();
		$req = $ctx->getRequest();
		$out = $ctx->getOutput();

		$title = Title::makeTitle(NS_MAIN, $target);
		$vid = Title::makeTitle(NS_VIDEO, $target);
		$r = Revision::newFromTitle($title);
		$update = true;
		if (!$r) {
			$update = false;
			$text = "";
		} else {
			$text = ContentHandler::getContentText( $r->getContent() );
		}

		$tag = "{{" . $vid->getFullText() . "|}}";
		if ($req->getVal('description') != '') {
			$tag = "{{" . $vid->getFullText() . "|" . $req->getVal('description') . "}}";
		}
		$newsection .= "\n\n== " . wfMessage('video') . " ==\n{$tag}\n\n";
		$a = new Article($title);

		$newtext = "";

		// Check for existing video section in the target article
		preg_match("/^==[ ]*" . wfMessage('video') . "/im", $text, $matches, PREG_OFFSET_CAPTURE);
		if (sizeof($matches) > 0 ) {
			// There is an existing video section, replace it
			$i = $matches[0][1];
			preg_match("/^==/im", $text, $matches, PREG_OFFSET_CAPTURE, $i+1);
			if (sizeof($matches) > 0) {
				$j = $matches[0][1];
				// == Video == was not the last section
				$newtext = trim(substr($text, 0, $i)) . $newsection . substr($text, $j, strlen($text));
			} else {
				// == Video == was the last section append it
				$newtext = trim($text) . $newsection;
			}
			// existing section, change it.
		} else {
			// There is not an existng video section, insert it after steps
			// This section could be cleaned up to handle it if there was an existing video section too I guess
			$arr = preg_split('/(^==[^=]*?==\s*?$)/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
			$found = false;
			for ($i =0 ; $i < sizeof($arr); $i++) {
				if (preg_match("/^==[ ]*" . wfMessage('steps') . "/", $arr[$i])) {
					$newtext .= $arr[$i];
					$i++;
					if ($i < sizeof($arr))
						$newtext .= $arr[$i];
					$newtext = trim($newtext) . $newsection;
					$found = true;
				} else {
					$newtext .= $arr[$i];
				}
			}
			if (!$found) {
				$arr = preg_split('/(^==[^=]*?==\s*?$)/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
				$newtext = "";
				$newtext = trim($arr[0]) . $newsection;
				for ($i =1 ; $i < sizeof($arr); $i++) {
					$newtext .= $arr[$i];
				}
			}
		}
		if ($newtext == "") {
			$newtext = $newsection;
		}
		$watch = $ctx->getUser()->isWatched($title);
		if ($update) {
			$a->updateArticle($newtext, $editSummary, false, $watch);
		} else {
			$a->insertNewArticle($newtext, $editSummary, false, $watch);
		}

		if ($req->getVal("popup") == "true") {
			$out->clearHTML();
			$out->setArticleBodyOnly(true);
			echo "<script type='text/javascript'>
			function onLoad() {
				var e = document.getElementById('video_text');
				e.value = \"" . htmlspecialchars($tag) . "\";
				WH.PreviewVideo.fetchPreview();
				var summary = document.getElementById('wpSummary');
				if (summary.value != '')
					summary.value += ',  " . ($update ? wfMessage('importvideo_changingvideo_summary')->text() : $editSummary) . "';
				else
					summary.value = '" . ($update ? wfMessage('importvideo_changingvideo_summary')->text() : $editSummary) . "';
				closeModal();
			}
			onLoad();
				</script>
				";
		}
		$me = Title::makeTitle(NS_SPECIAL, "ImportVideo");
		if ($req->getVal('wasnew') || $req->getVal('new')) {
			// log it, we track when someone uploads a video for a new article
			$params = array($title->getArticleID());
			$log = new LogPage( 'vidsfornew', false );
			$log->addEntry('added', $title, 'added');

			$out->redirect($me->getFullURL() . "?new=1&skip=" . $title->getArticleID());
			return;
		} elseif ($req->getVal('category')) {
			// they added a video to a category, keep them in the category mode
			$out->redirect($me->getFullURL() . "?category=" . urlencode($req->getVal('category')));
			return;
		}
	}

	/**
	 * Parser setup functions, subclasses over ride parseStartElement
	 * and parseEndElement
	function parseDefaultHandler($parser, $data) {
		if ($this->mCurrentTag) {
			if (is_array($this->mCurrentNode)) {
				if (isset($this->mCurrentNode[$this->mCurrentTag])) {
					$this->mCurrentNode[$this->mCurrentTag] .= $data;
				} else {
					$this->mCurrentNode[$this->mCurrentTag] = $data;
				}
			} else {
				$this->mResponseData[$this->mCurrentTag] = $data;
			}
		}
	}

	function parseResults($results) {
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, "parseStartElement"), array($this, "parseEndElement"));
		xml_set_default_handler($xml_parser, array($this, "parseDefaultHandler"));
		xml_parse($xml_parser, $results);
		xml_parser_free($xml_parser);
	}

	function isValid(&$timestring) {
		$userGroups = $this->getUser()->getGroups();
		// Staff, admin and nabbers can see all vids
		if (in_array('staff', $userGroups) || in_array('admin', $userGroups) || in_array('newarticlepatrol', $userGroups)) {
			return true;
		}

		$ret = true;
		$pub = strtotime($timestring);
		if ($pub) {
			$current = time();
			$diff = $current - $pub;
			// If published more than 30 days ago, it's valid
			$ret = $diff > 60 * 60 * 24 * 30 ? true : false;
		}
		return $ret;
	}
*/
}

/**
 * This class is used to grab a description from the user when they
 * insert their video
 */
class ImportVideoPopup extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ImportVideoPopup' );
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$out->addHTML('<div style="margin-top:20px">');
		$out->addWikiText(wfMessage('importvideo_add_desc_details')->text());
		if ($req->wasPosted()) {
			$iv = Title::makeTitle(NS_SPECIAL, "ImportVideo");
			$out->addHTML("<form method='POST' name='importvideofrompopup' action='{$iv->getLocalUrl()}'>");
			$vals = $req->getValues();
			foreach ($vals as $key=>$val) {
				if ($key != "title") {
					$out->addHTML("<input type='hidden' name='{$key}' value=\"" . htmlspecialchars($val) . "\"/>");
				}
			}
			$out->addHTML(' <p><center><textarea id="importvideo_comment" name="description" style="width:520px; height: 50px;margin-top: 10px"></textarea></p>
				<br/>
				<p><input type="submit" class="button primary" value="' . wfMessage('importvideo_popup_add_desc') . '" />
				</center>
				</p>
			</div></form>');
		} else {
			$out->addHTML('<br /><center><p><textarea id="importvideo_comment" style="width:550px; height: 50px;"></textarea></p>
				<br/><br/>
				<input type="button" class="button primary" value="' . wfMessage('importvideo_popup_add_desc') . '" onclick="WH.ImportVideo.throwdesc();" /> <a href="#" onclick="$(\'#dialog-box\').dialog(\'close\'); return false;" class="button">' . wfMessage('importvideo_popup_changearticle')->text() . '</a>
			</center>
			</div></form>
			');
		}
	}

}

/**
 *  This page is used for processing ajax requests to show a video preview in the guided editor
 */
class PreviewVideo extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct( 'PreviewVideo' );
	}

	public function execute($par) {
		$user = $this->getUser();
		$req = $this->getRequest();
		$out = $this->getOutput();

		$out->setArticleBodyOnly(true);

		$target = !empty($par) ? $par : $req->getVal( 'target' );
		$vt = Title::newFromURL($target);
		if (!$vt) return;
		$t = Title::makeTitle(NS_MAIN, $vt->getText());

		# can we parse from the main naemspace article to include the comment?
		$r = Revision::newFromTitle($t);
		if (!$r) return;
		$text = ContentHandler::getContentText( $r->getContent() );

		preg_match("/{{Video:[^}]*}}/", $text, $matches);
		if (sizeof($matches) > 0) {
			$comment = preg_replace("/.*\|/", "", $matches[0]);
			$comment = preg_replace("/}}/", "", $comment);
		}

		$rev = Revision::newFromTitle($vt);
		if (!$rev) return;
		$text = ContentHandler::getContentText( $rev->getContent() );
		$text = str_replace("{{{1}}}", $comment, $text);
		$html = $out->parse($text, true, true) ;
		echo $html;
	}
}

/**
 * This is a leaderboard for users who are adding videos to new articles
 */
class NewVideoBoard extends SpecialPage {


	public function __construct() {
		parent::__construct( 'NewVideoBoard' );
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$target = !empty($par) ? $par : $req->getVal( 'target' );
		$dbr = wfGetDB(DB_REPLICA);

		$this->setHeaders();

		$out->addModuleStyles('ext.wikihow.PatrolCount');

		$me = Title::makeTitle(NS_SPECIAL, 'NewVideoBoard');
		$now = wfTimestamp(TS_UNIX);

		// allow the user to grab the local patrol count relative to their own timezone
		if ($req->getVal('window', 'day') == 'day') {
			$links = "[" . Linker::link($me, wfMessage('videoboard_week'), array(), array("window" => "week")) . "] [" . wfMessage('videoboard_day'). "]";
			$date1 = substr(wfTimestamp(TS_MW, $now - 24*3600*7), 0, 8) . "000000";
			$date2 = substr(wfTimestamp(TS_MW, $now + 24*3600), 0, 8) . "000000";
		} else {
			$links = "[" . wfMessage('videoboard_week') . "] [" . Linker::link($me, wfMessage('videoboard_day'), array(), array("window" => "day")) . "]";
			$date1 = substr(wfTimestamp(TS_MW), 0, 8) . "000000";
			$date2 = substr(wfTimestamp(TS_MW, $now + 24*3600), 0, 8) . "000000";
		}

		$out->addHTML($links);
		$out->addHTML("<br/><br/><table width='500px' align='center' class='status'>" );

		$sql = "select log_user, count(*) as C
				from logging where log_type='vidsfornew' and log_timestamp > '$date1' and log_timestamp < '$date2'
				group by log_user order by C desc limit 20;";
		$res = $dbr->query($sql, __METHOD__);
		$index = 1;
		$out->addHTML("<tr>
						   <td></td>
							<td>User</td>
							<td  align='right'>" . wfMessage('videoboard_numberofvidsadded')->text() . "</td>
							</tr>");

		foreach ($res as $row) {
			$u = User::newFromID($row->log_user);
			$count = number_format($row->C, 0, "", ',');
			$class = "";
			if ($index % 2 == 1)
				$class = 'class="odd"';
			$log = Linker::link( Title::makeTitle( NS_SPECIAL, 'Log'), $count, array(), array('type' => 'vidsfornew', 'user' => $u->getName()) );
			$out->addHTML("<tr $class>
				<td>$index</td>
				<td>" . Linker::link($u->getUserPage(), $u->getName()) . "</td>
				<td  align='right'>{$log}</td>
				</tr>
			");
			$index++;
		}

		$out->addHTML("</table></center>");
		if ($user->getOption('patrolcountlocal', "GMT") != "GMT")  {
			$out->addHTML("<br/><br/><i><font size='-2'>" . wfMessage('patrolcount_viewlocal_info')->parseAsBlock() . "</font></i>");
		}
	}
}

