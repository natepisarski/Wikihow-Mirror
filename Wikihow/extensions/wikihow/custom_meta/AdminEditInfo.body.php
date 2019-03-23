<?php

class AdminEditInfo extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		global $wgTitle;
		$this->specialPage = $wgTitle->getPartialUrl();
		$this->editDescs = $this->specialPage == 'AdminEditMetaInfo';

		parent::__construct($this->specialPage);
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
						if ($ct) {
							$pageTitle = $ct->getTitle();
						} else {
							$pageTitle = '<i>error generating title</i>';
						}
						$url['page-title'] = $pageTitle;
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
		$wasEdited = false;
		if ($title) {
			$ct = CustomTitle::newFromTitle($title);
			if ($ct) {
				$pageTitle = $ct->getTitle();
				list($default, $wasEdited) = $ct->getDefaultTitle();
			}
		}
		return array($pageTitle, $default, $wasEdited);
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
	private static function savePageTitle($type, $pageid, $pageTitle) {
		$title = Title::newFromID($pageid);
		if (!$title) return '';
		$dbw = wfGetDB(DB_MASTER);

		if ('default' == $type) {
			CustomTitle::dbRemoveTitle($dbw, $title);
		} elseif ('edited' == $type && $pageTitle) {
			CustomTitle::dbSetCustomTitle($dbw, $title, $pageTitle);
		} else {
			return '';
		}

		$ct = CustomTitle::newFromTitle($title);
		if ($ct) {
			return $ct->getTitle();
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
				list($pageTitle, $defaultPageTitle, $wasEdited) = self::loadPageTitle($page);
				$result = array(
					'data' => $pageTitle,
					'default-data' => $defaultPageTitle,
					'was-edited' => $wasEdited,
				);
			} elseif ('save-titles' == $action) {
				$type = $req->getVal('edit-type', '');
				$page = $req->getInt('page', '');
				$pageTitle = $req->getVal('data', '');
				$msg = 'saved';
				$pageTitle = self::savePageTitle($type, $page, $pageTitle);
				$result = array(
					'result' => $msg,
					'data' => $pageTitle,
				);
			} elseif ('list-all-csv' == $action) {
				self::listPageTitlesCSV($titles);
			} else {
				$result = array('result' => 'error: no action');
			}
			print json_encode($result);
		} else {
			$title = $this->editDescs ? 'Admin - Edit Meta Info' : 'Admin - Edit Page Titles';
			$out->setHTMLTitle( wfMessage('pagetitle', $title) );
			$out->addModules('jquery.ui.dialog');

			$tmpl = $this->genAdminForm();
			$out->addHTML($tmpl);
		}
	}

	private function genAdminForm() {
		$basepage = '/Special:' . $this->specialPage;
		$header = $this->editDescs ? 'Edit Description Meta Info' : 'Edit Page Titles';
		$html = '';
$html .= <<<EOHTML
<style>
	.edit-list li { list-style: none; padding-bottom: 10px; padding-right: 15px; }
</style>

<script src="/extensions/wikihow/common/download.jQuery.js"></script>
<form id="urls-submit" method="post" action="/Special:{$this->specialPage}">
<input id="pages-go-action" type="hidden" name="action" value="list" />
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	{$header}
</div>
EOHTML;

		if (!$this->editDescs) {
$html .= <<<EOHTML
<div style="font-size: 13px; font-style: italic">
	<p>Note: check out our <a href="/Special:AdminTitles">other tool</a> used to bulk-edit custom titles.</p>
</div>
EOHTML;
		} else {
$html .= <<<EOHTML
<div style="font-size: 13px; font-style: italic">
	<p>Note: check out our <a href="/Special:AdminMetaDescs">other tool</a> used to bulk-edit custom meta descriptions.</p>
</div>
EOHTML;
		}

		if (!$this->editDescs) {
$html .= <<<EOHTML
<div style="font-size: 13px">
	<br>
	<a class="pages-list-all" href="#">list all pages with custom titles</a>
</div>
EOHTML;
		}
$html .= <<<EOHTML
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	<p>Enter a list of URLs such as <code style="font-weight: bold;">https://www.wikihow.com/Lose-Weight-Fast</code> to look up. One per line.</p>
</div>
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea>
<br>
EOHTML;
		if (!$this->editDescs) {
$html .= <<<EOHTML
<button class="pages-go" id="pages-go-titles" disabled="disabled" style="padding: 5px;">edit page titles</button><br/>
EOHTML;
		} else {
$html .= <<<EOHTML
<button class="pages-go" id="pages-go-descs" disabled="disabled" style="padding: 5px;">edit meta descriptions</button><br/>
EOHTML;
		}
$html .= <<<EOHTML
<br/>
<div id="pages-result">
</div>
</form>

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
	<textarea class="edit-edited-data" rows="10"></textarea>
</li>
</ul>
<button id="edit-save" disabled="disabled" style="padding 10px; margin:5px;">save meta description</button><br><span style="font-style: italic; font-size: 10px; font-weight: normal;">(note that this <span class="edit-footnote-type">description</span> will no longer be automatically updated if hand-edited)</span><br/>
</div>

<script>
(function($) {
	$(document).ready(function() {
		$('.pages-go')
			.prop('disabled', false)
			.click(function() {
				var action = $(this).attr('id').replace(/^pages-go-/, 'list-');
				$('#pages-go-action').val(action);
				var form = $('#urls-submit').serializeArray();
				$('#pages-result').html('loading ...');
				$.post('{$basepage}',
					form,
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});

		$('#pages-list')
			.focus();

		$('.pages-list-all')
			.click(function() {
				var url = '{$basepage}/pagetitles.csv?action=list-all-csv';
				$.download(url, 'x=y'); // pseudo empty form submitted
				return false;
			});

		$('.edit-data').live('click', function() {
			var action = $('#pages-go-action').val().replace(/^list-/, '');
			var editDescs = action == 'descs';
			var buttonText = editDescs ? 'save meta description' : 'save page title';
			var editType = editDescs ? 'Description' : 'Page Title';

			$('#edit-save').html(buttonText);
			$('.edit-footnote-type').html(editType.toLowerCase());

			var id = $(this).attr('id').replace(/^page-/, '');
			var title = $('.title-' + id).first().html();
			$('#edit-page-id').html(id);
			$.post('{$basepage}',
				'action=load-' + action + '&page=' + id,
				function(data) {
					$('.edit-default-data').html(data['default-data']);
					$('.edit-edited-data').val(data['data']);

					var edited = data['was-edited'] == 1;
					if (!edited) {
						$('#ec-default').click();
						var editDisabled = true;
					} else {
						$('#ec-edit').click();
						var editDisabled = false;
					}
					$('#edit-save').prop('disabled', true);
					$('.edit-edited-data').prop('disabled', editDisabled);

					var dialogTitle = 'Edit ' + editType + ' &ldquo;' + title + '&rdquo;';
					$('.ui-dialog-title').html(dialogTitle);
					$('.edit-dialog')
						.attr('title', dialogTitle)
						.dialog({
							width: 500,
							minWidth: 500,
							closeText: 'x'
						});

				},
				'json');
			return false;
		});

		// when Edit radio button is clicked
		$('#ec-edit').click(function() {
			$('.edit-edited-data')
				.prop('disabled', false)
				.focus();
		});

		// when Default radio buttons are clicked
		$('#ec-default').click(function () {
			$('.edit-edited-data').prop('disabled', true);
		});

		// when any radio button is clicked
		$('.ec').click(function() {
			$('#edit-save').prop('disabled', false);
		});
		$('.edit-edited-data').bind('keypress keyup keydown', function() {
			$('#edit-save').prop('disabled', false);
		});

		// when any the save description button is pressed
		$('#edit-save').click(function() {
			var action = $('#pages-go-action').val().replace(/^list-/, '');
			var editDescs = action == 'descs';

			var editType = $('input:radio[name=editchoice]:checked').val();
			var id = $('#edit-page-id').html();
			var editedData = $('.edit-edited-data').val();
			$('#edit-save').prop('disabled', true);

			$.post('{$basepage}',
				'action=save-' + action + '&edit-type=' + editType + '&page=' + id + '&data=' + encodeURIComponent(editedData),
				function(data) {
					$('.result-' + id).html(data['result']);
					$('.row-data-' + id).html(data['data']);
					$('.edit-dialog').dialog('close');
				},
				'json');
		});
	});
})(jQuery);
</script>
EOHTML;
		return $html;
	}

	private static function genURLListTable($type, $urls) {
		$editDescs = 'descs' == $type;
$html = <<<EOHTML
<style>
	.tres tr:nth-child(even) { background: #ccc; }
	.data { display: none; }
</style>
<table class="tres"><tr><th width="500px"><?= $editDescs ? 'URL and Description' : 'Title and Description' ?></th><th>Action</th><th>Result</th></tr>
EOHTML;
		foreach ($urls as $row) {
			$titleEnc = htmlentities($row['title']);
$html .= <<<EOHTML
	<tr><td>
		<span class="data title-{$row['id']}">{$titleEnc}</span>
		<p><a href='{$row['url']}' target="_blank">{$row['url']}</a></p>
EOHTML;
			if ($editDescs) {
$html .= <<<EOHTML
		<p><i>currently:</i> <span class='row-data-{$row['id']}'>{$row['desc']}</span></p>
EOHTML;
			} else {
$html .= <<<EOHTML
		<p><span class='row-data-{$row['id']}'>{$row['page-title']}</span></p>
EOHTML;
			}
$html .= <<<EOHTML
	</td><td style="text-align: center;">
		<a class="edit-data" id="page-{$row['id']}" href='#'>edit</a>
	</td><td class='result-{$row['id']}'>
	</td></tr>
EOHTML;
		}
$html .= <<<EOHTML
</table>
EOHTML;
		return $html;
	}

}
