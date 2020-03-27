<?php

class AdminEditInfo extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		global $wgTitle, $wgHooks;
		$this->specialPage = $wgTitle->getPartialUrl();
		$this->editDescs = $this->specialPage == 'AdminEditMetaInfo';

		parent::__construct($this->specialPage);
		$wgHooks['ShowSideBar'][] = array('AdminEditInfo::removeSideBarCallback');
	}

	// Callback indicating to remove the right rail
	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist($type, $pageList) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$urls = array();
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				$title = WikiPhoto::getArticleTitleNoCheck($url);
				if ($title && $title->exists()) {
					$id = $title->getArticleId();
					$url = array(
						'url' => $url,
						'title' => $title,
						'id' => $id,
					);
					if ('descs' == $type) {
						$meta = new ArticleMetaInfo($title);
						$desc = $meta->getDescription();
						$url['desc'] = $desc;
					} else {
						$ct = CustomTitle::newFromTitle($title);
						$heading = '';
						$note = '';
						if ($ct) {
							$pageTitle = $ct->getTitle();
							$data = $ct->getData();
							$heading = $data['heading'];
							$note = $data['note'];
						} else {
							$pageTitle = '<i>error generating title</i>';
						}
						$url['page-title'] = $pageTitle;
						$url['page-heading'] = $heading;
						$url['page-note'] = $note;
					}
					$urls[] = $url;
				}
			}
		}
		return $urls;
	}

	/**
	 * Load the meta description for a page.
	 *
	 * @param int $page page ID
	 * @return array ($desc, $defaultDesc, $wasEdited)
	 */
	private static function loadPageDesc($page) {
		$title = Title::newFromID($page);
		if ($title) {
			$meta = new ArticleMetaInfo($title);
			$style = $meta->getStyle();
			$desc = $meta->getDescription();
			$defaultDesc = $meta->getDescriptionDefaultStyle();
			$wasEdited = ArticleMetaInfo::DESC_STYLE_EDITED == $style;
		} else {
			$desc = '';
			$defaultDesc = '';
			$wasEdited = false;
		}
		return array($desc, $defaultDesc, $wasEdited);
	}

	/**
	 * Load the title for a page.
	 */
	private static function loadPageTitle($page) {
		$title = Title::newFromID($page);
		$pageTitle = '';
		$default = '';
		$heading = '';
		$note = '';
		$wasEdited = false;
		if ($title) {
			$ct = CustomTitle::newFromTitle($title);
			if ($ct) {
				$pageTitle = $ct->getTitle();
				list($default, $wasEdited) = $ct->getDefaultTitle();
				$data = $ct->getData();
				$heading = $data['heading'];
				$note = $data['note'];
			}
		}
		return array($pageTitle, $default, $wasEdited, $heading, $note);
	}

	/**
	 * Save the description for a page as either default or edited.
	 *
	 * @param string $type 'default' or 'edited'
	 * @param int $page page ID
	 * @param string $desc new meta descript if $type is 'edited'
	 * @return string the actual new meta description that was saved (html
	 *   removed, possibly truncated, etc)
	 */
	private static function savePageDesc($type, $page, $desc) {
		$title = Title::newFromID($page);
		if (!$title) return '';

		$desc = trim($desc);
		$meta = new ArticleMetaInfo($title);

		if ('default' == $type) {
			$meta->resetMetaData();
		} elseif ('edited' == $type && $desc) {
			$meta->setEditedDescription($desc, 'Edited via Special:AdminEditMetaInfo');
		} else {
			return '';
		}

		return $meta->getDescription();
	}

	/**
	 * Save or remove the custom page title
	 */
	private static function savePageTitle($type, $pageid, $pageTitle, $heading, $note) {
		$title = Title::newFromID($pageid);
		if (!$title) return '';
		$dbw = wfGetDB(DB_MASTER);

		if ('default' == $type) {
			CustomTitle::dbResetTitle($dbw, $title);
		} elseif ('edited' == $type && $pageTitle) {
			CustomTitle::dbSetCustomTitle($dbw, $title, $pageTitle, $heading, $note);
		} else {
			return '';
		}

		$ct = CustomTitle::newFromTitle($title);
		if ($ct) {
			return $ct->getData();
		} else {
			return '';
		}
	}

	/**
	 * List all page titles
	 */
	private static function listPageTitlesCSV() {
		header("Content-Type: text/csv");
		$dbr = wfGetDB(DB_REPLICA);
		$titles = CustomTitle::dbListCustomTitles($dbr);
		print "page,title\n";
		foreach ($titles as $custom) {
			$title = Title::newFromDBkey($custom['ct_page']);
			print '"http://www.wikihow.com/' . $title->getPartialUrl() . '","' . $custom['ct_custom'] . '"' . "\n";
		}
		exit;
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
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
			$out->setArticleBodyOnly(true);
			$dbr = wfGetDB(DB_REPLICA);

			$action = $req->getVal('action', '');

			if ('list-descs' == $action || 'list-titles' == $action) {
				$type = preg_replace('@^list-@', '', $action);
				$pageList = $req->getVal('pages-list', '');
				$urls = self::parseURLlist($type, $pageList);
				if (!empty($urls)) {
					$html = self::genURLListTable($type, $urls);
				} else {
					$html = '<i>ERROR: no URLs found</i>';
				}
				$result = array('result' => $html);
			} elseif ('load-descs' == $action) {
				$page = $req->getInt('page', '');
				list($desc, $defaultDesc, $wasEdited) = self::loadPageDesc($page);
				$result = array(
					'data' => $desc,
					'default-data' => $defaultDesc,
					'was-edited' => $wasEdited,
				);
			} elseif ('save-descs' == $action) {
				$type = $req->getVal('edit-type', '');
				$page = $req->getInt('page', '');
				$desc = $req->getVal('data', '');
				$msg = 'saved';
				$desc = self::savePageDesc($type, $page, $desc);
				$result = array(
					'result' => $msg,
					'data' => $desc,
				);
			} elseif ('load-titles' == $action) {
				$page = $req->getInt('page', '');
				list($pageTitle, $defaultPageTitle, $wasEdited, $heading, $note) = self::loadPageTitle($page);
				$result = array(
					'data' => $pageTitle,
					'default-data' => $defaultPageTitle,
					'was-edited' => $wasEdited,
					'heading' => $heading,
					'note' => $note,
				);
			} elseif ('save-titles' == $action) {
				$type = $req->getVal('edit-type', '');
				$page = $req->getInt('page', '');
				$pageTitle = $req->getVal('data', '');
				$heading = $req->getVal('heading', '');
				$note = $req->getVal('note', '');
				$data = self::savePageTitle($type, $page, $pageTitle, $heading, $note);
				if ( $data ) {
					$result = array(
						'result' => 'saved',
						'data' => $data['title'],
						'heading' => $data['heading'],
						'note' => $data['note']
					);
				} else {
					$result = array(
						'result' => 'error'
					);
				}
			} elseif ('list-all-csv' == $action) {
				self::listPageTitlesCSV($titles);
			} else {
				$result = array('result' => 'error: no action');
			}
			print json_encode($result);
		} else {
			$out->setHTMLTitle( wfMessage('pagetitle', $this->editDescs ? 'Admin - Edit Meta Info' : 'Admin - Edit Page Titles' ) );
			$out->setPageTitle( $this->editDescs ? 'Edit Description Meta Info' : 'Edit Page Titles' );
			$out->addModules( ['wikihow.common.jquery.download', 'jquery.ui.dialog', 'ext.wikihow.admineditinfo'] );
			$tmpl = $this->genAdminForm();
			$out->addHTML($tmpl);
		}
	}

	// TODO: convert this to use mustache templating
	private function genAdminForm() {
		$html = '';
		$metaClass = $this->editDescs ? 'primary' : '';
		$titlesClass = $this->editDescs ? '' : 'primary';
$html .= <<<EOHTML
<style>
	.edit-list li { list-style: none; padding-bottom: 10px; padding-right: 15px; }
	.aei-navigation {
		position: absolute;
		top: -5.25em;
		right: 1em;
	}
	.aei-navigation .button {
		margin-right: 0;
		display: block;
		float: left;
		border-radius: 0;
	}
	.aei-navigation .button:not(:last-child) {
		border-right: none;
	}
	.aei-navigation .button:first-child {
		border-top-left-radius: 4px;
		border-bottom-left-radius: 4px;
	}
	.aei-navigation .button:last-child {
		border-top-right-radius: 4px;
		border-bottom-right-radius: 4px;
	}
</style>
<div class="aei-navigation">
<a class="button $titlesClass" href="/Special:AdminEditPageTitles">titles</a>
<a class="button $metaClass" href="/Special:AdminEditMetaInfo">meta descriptions</a>
<a class="button" href="/Special:AdminTitles">bulk titles</a>
<a class="button" href="/Special:AdminMetaDescs">bulk meta descriptions</a>
</div>
<form id="urls-submit" method="post" action="/Special:{$this->specialPage}">
<input id="pages-go-action" type="hidden" name="action" value="list" />
EOHTML;

		if (!$this->editDescs) {
$html .= <<<EOHTML
<div style="font-size: 13px">
	<br>
</div>
EOHTML;
		}
$html .= <<<EOHTML
<style>
	.ept-section {
		position: relative;
		padding: 1em;
		margin-bottom: 15px;
		border: 1px solid #dddddd;
		border-radius: 4px;
		background-color: #fff
	}
	.ept-results {
		display: none;
	}
	.ept-section h3 {
		margin-bottom: 1em;
	}
	.ept-section .button {
		display: inline-block;
	}
	.ept-section p {
		margin: 0 0 1em 0;
	}
	.pages-list-all {
		position: absolute;
		top: 1em;
		right: 1em;
		margin-right: 0;
	}
</style>
<div class="ept-section">
	<h3>Query</h3>
	<a class="pages-list-all button" href="#">list all pages with custom titles</a>
	<p>Enter a list of URLs such as <code style="font-weight: bold;">https://www.wikihow.com/Lose-Weight-Fast</code> to look up. One per line.</p>
	<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70" style="width:100%;box-sizing: border-box;"></textarea>
EOHTML;
		if (!$this->editDescs) {
$html .= <<<EOHTML
<button class="pages-go button" id="pages-go-titles" disabled="disabled">query page titles</button>
EOHTML;
		} else {
$html .= <<<EOHTML
<button class="pages-go button" id="pages-go-descs" disabled="disabled">query meta descriptions</button>
EOHTML;
		}
$html .= <<<EOHTML
</div>
<div class="ept-section ept-results">
	<h3>Query Results</h3>
	<div id="pages-result"></div>
</div>
</form>
<style>
	.edit-dialog textarea:disabled {
		opacity: 0.5;
	}
	.edit-dialog input {
		margin-right: 0.5em;
	}
	.edit-dialog textarea {
		margin: 0.5em 0;
	}
</style>
<div class="edit-dialog" style="display:none;" title="">
<span class="data" id="edit-page-id"></span>
<ul class="edit-list">
<li>
	<input id="ec-default" class="ec" type="radio" name="editchoice" value="default">
	<label for="ec-default"><b>Default</b></label><br/>
	<div class="edit-default-data">
	</div>
</li>
<li>
	<input id="ec-edit" class="ec" type="radio" name="editchoice" value="edited">
	<label for="ec-edit"><b>Hand-Edited</b></label><br/>
	<textarea class="edit-edited-data" cols="60" rows="3"></textarea>
</li>
<li class="ec-titles-only">
	<label for="edit-heading-data"><b>Custom heading</b></label><br/>
	<textarea class="edit-heading-data" cols="60" rows="3"></textarea>
</li>
<li class="ec-titles-only">
	<label for="edit-note-data"><b>Note</b></label><br/>
	<textarea class="edit-note-data" cols="60" rows="3"></textarea>
</li>
</ul>
<button id="edit-save" disabled="disabled" class="button">save meta description</button><br><span style="font-style: italic; font-size: 10px; font-weight: normal;">(note that this <span class="edit-footnote-type">description</span> will no longer be automatically updated if hand-edited)</span><br/>
</div>
EOHTML;
		return $html;
	}

	private static function genURLListTable($type, $urls) {
		$editDescs = 'descs' == $type;
		$header = $editDescs ? 'URL and Description' : 'Title and Description';
$html = <<<EOHTML
<style>
	.tres, .tres-details {
		width: 100%;
		border-collapse: collapse;
	}
	table.tres td, table.tres th {
		vertical-align: top;
		text-align: left;
		padding: 1em;
	}
	table.tres .tres-row td:first-child {
		padding: 0.5em;
	}
	.tres tr.tres-row:nth-child(even) {
		background: #eee;
	}
	.tres th {
		background: #e5eedd;
	}
	table.tres-details td {
		padding: 0.5em;
	}
	.tres-result {
		margin-top: 1em;
		font-size: smaller;
		color: #aaa;
	}
	table.tres .tres-action {
		text-align: center;
		width: 1%;
	}
	.tres-action .button {
		display: inline-block;
		margin-right: 0;
	}
	.row-data {
		font-weight: bold;
	}
	.row-desc,
	.row-title,
	.row-url,
	.row-heading,
	.row-note {
		font-size: smaller;
	}
	.tres-details td:first-child {
		width: 10%;
	}
	.data { display: none; }
</style>
<table class="tres">
	<tr>
		<th>$header</th>
		<th class="tres-action">Action</th>
	</tr>
EOHTML;
		foreach ($urls as $row) {
			$titleEnc = htmlentities($row['title']);
$html .= <<<EOHTML
<tr class="tres-row">
	<td>
		<table class="tres-details">
			<tr class="row-data"><td colspan="2"><a href='{$row['url']}' target="_blank" class="title-{$row['id']}">{$titleEnc}</a></td></tr>
EOHTML;
			if ($editDescs) {
$html .= <<<EOHTML
			<tr class="row-desc"><td>Description</td><td class='row-data-{$row['id']}'>{$row['desc']}</td></tr>
EOHTML;
			} else {
$html .= <<<EOHTML
		<tr class='row-title'><td>Title</td><td class='row-data-{$row['id']}'>{$row['page-title']}</td></tr>
		<tr class='row-heading'><td>Heading</td><td class='row-heading-{$row['id']}'>{$row['page-heading']}</td></tr>
		<tr class='row-note'><td>Note</td><td class='row-note-{$row['id']}'>{$row['page-note']}</td></tr>
EOHTML;
			}
$html .= <<<EOHTML
		</table>
	</td>
	<td class="tres-action"><a class="edit-data button" id="page-{$row['id']}" href='#'>edit</a><div class='tres-result result-{$row['id']}'></div></td>
</tr>
EOHTML;
		}
$html .= <<<EOHTML
</table>
EOHTML;
		return $html;
	}

}
