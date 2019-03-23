<?php

class AdminSamples extends UnlistedSpecialPage {

	public function __construct() {
		global $IP;
		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");

		parent::__construct('AdminSamples');
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist($pageList,$bImport) {
		$pageList = preg_split('@[\r\n]+@', $pageList);
		$urls = array();
		$dbr = wfGetDB(DB_REPLICA);
		foreach ($pageList as $url) {
			$url = trim($url);
			if (!empty($url)) {
				if ($bImport) {
					//gdrive
					$urls[] = $url;
				}
				else {
					$sample = preg_replace('@https?://www.wikihow.com/Sample/@','',$url);
					$err = self::validateSample($sample,$dbr);
					$urls[] = array('url' => $url, 'sample' => $sample, 'err' => $err);
				}
			}
		}
		return $urls;
	}

	private static function validateSample($sample, $db) {
		//is it in the main db table?
		$res = $db->select('dv_sampledocs','*',array('dvs_doc' => $sample), __METHOD__);
		if (!$res->fetchObject()) return 'invalid sample';

		//make sure it isn't linked from any articles
		$res = $db->select('dv_links','*',array('dvl_doc' => $sample), __METHOD__);
		if ($res->fetchObject()) return 'Still linked from articles';

		//still here?
		return '';
	}

	private static function fourOhFourSamples($urls) {
		$sample_array = array();

		//gather up the articles
		foreach ($urls as $url) {
			if (!$url['err']) {
				$sample_array[] = $url['sample'];
			}
		}

		//do the deletes
		if ($sample_array) {
			$dbw = wfGetDB(DB_MASTER);
			$result = '';

			//ready it for the db
			$the_samples = implode("','",$sample_array);
			$the_samples = "('".$the_samples."')";

			//remove the sample from dv_sampledocs
			$res = $dbw->delete('dv_sampledocs', array('dvs_doc' => $sample_array), __METHOD__);
			if ($res) $result .= '<p>Samples removed.</p>';

			//remove custom names from dv_display_names
			$res = $dbw->delete('dv_display_names', array('dvdn_doc' => $sample_array), __METHOD__);
			if ($res) $result .= '<p>Sample display names removed.</p>';

			//remove the sample from qbert
			$res = $dbw->delete('dv_sampledocs_status', array('sample' => $sample_array), __METHOD__);
			if ($res) $result .= '<p>Qbert updated.</p>';
		}

		return $result;
	}

	public function getDisplayNames() {
		$html = '';

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('dv_display_names',array('dvdn_doc','dvdn_display_name'), "REPLACE(dvdn_doc,'-',' ') != dvdn_display_name", __METHOD__, array('ORDER BY' => 'dvdn_doc ASC'));

		if ($res) {
			$html = '<table class="display_name_table">
					<tr><th>Sample-Name</th><th>Display Name</th><th></th><th></th></tr>
					<tr><td><input type="text" id="new_sample" /></td>
					<td><input type="text" id="new_dname" /></td>
					<td colspan="2"><input type="button" id="add_new_dname" value="Add New" /></td></tr>';
			foreach ($res as $row) {
				$html .= "<tr><td>{$row->dvdn_doc}</td><td>{$row->dvdn_display_name}</td><td class='edit'></td><td class='delete'></td></tr>";
			}
			$html .= '</table>';
		}
		return $html;
	}

	private function updateDisplayName($sample,$new_dname) {
		if (!$sample || !$new_dname) return false;

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update('dv_display_names', array('dvdn_display_name' => $new_dname), array('dvdn_doc' => $sample), __METHOD__);
		if ($res) self::clearDisplayNameCache($sample);

		$result = ($res) ? 'The display name for '.$sample.' has been updated' : '';
		return $result;
	}

	private function addDisplayName($sample,$new_dname) {
		if (!$sample || !$new_dname) return false;

		$dbw = wfGetDB(DB_MASTER);

		//first, check to see if we even have this sample
		$res = $dbw->select('dv_sampledocs','*',array('dvs_doc' => $sample), __METHOD__);
		if (!$res->fetchObject()) return 'invalid sample';

		//check to make sure it's not already in there
		$res = $dbw->select('dv_display_names','*',array('dvdn_doc' => $sample),__METHOD__);
		if (!$res->fetchObject()) {
			//insert
			$res = $dbw->insert('dv_display_names', array('dvdn_display_name' => $new_dname, 'dvdn_doc' => $sample), __METHOD__);
			$result = ($res) ? 'The display name for '.$sample.' has been added' : '';
			if ($res) self::clearDisplayNameCache($sample);
		}
		else {
			//it is? just update
			$result = self::updateDisplayName($sample,$new_dname);
		}

		return $result;
	}

	private function removeDisplayName($sample) {
		if (!$sample) return false;

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->delete('dv_display_names', array('dvdn_doc' => $sample), __METHOD__);
		if ($res) self::clearDisplayNameCache($sample);

		$result = ($res) ? 'The display name for '.$sample.' has been removed' : '';
		return $result;
	}

	private function clearDisplayNameCache($sample) {
		global $wgMemc;
		$memkey = wfMemcKey('sample_display_name', $sample);
		$wgMemc->delete($memkey);
		return;
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

		if ($_SERVER['HTTP_HOST'] != 'parsnip.wikiknowhow.com') {
			$out->redirect('https://parsnip.wikiknowhow.com/Special:AdminSamples');
		}

		if ($req->wasPosted()) {
			// this may take a while...
			set_time_limit(0);

			$out->setArticleBodyOnly(true);
			$bImport = ($req->getVal('action') == 'import');
			$check_for_plagiarism = ($req->getVal('cfp') == 'on') ? true : false;

			//grab those urls
			$pageList = ($bImport) ? $req->getVal('samples-list','') : $req->getVal('pages-list','');
			$urls = self::parseURLlist($pageList,$bImport);

			//we *do* have some urls, right?
			if (empty($urls)) {
				print json_encode(array('result' => '<i>ERROR: no URLs given</i>'));
				return;
			}

			if ($bImport) {
				$html = '';
				//importing
				$new_samples = SampleProcess::importSamples($urls,$check_for_plagiarism);

				if ($new_samples) {
					$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
					$html .= '<table class="tres"><tr><th width="400px">Sample</th><th>Files</th></tr>';
					foreach ($new_samples as $row) {
						$formats = (is_array($row['formats'])) ? implode('<br />',$row['formats']) : $row['formats'];
						$html .= "<tr><td>{$row['sample']}</td><td>{$formats}</td></tr>";
					}
					$html .= '</table>';
				}

			} elseif ($req->getVal('action') == 'edit') {
				//update this display name
				$sample = $req->getVal('sample','');
				$dname = $req->getVal('dname','');
				$res = self::updateDisplayName($sample,$dname);
				if ($res) print $res;
				return;

			} elseif ($req->getVal('action') == 'delete') {
				//dump this display name
				$sample = $req->getVal('sample','');
				$res = self::removeDisplayName($sample);
				if ($res) print $res;
				return;

			} elseif ($req->getVal('action') == 'addnew') {
				//add a new display name
				$sample = $req->getVal('sample','');
				$dname = $req->getVal('dname','');
				$res = self::addDisplayName($sample,$dname);
				if ($res) print $res;
				return;

			} else {
				//404ing
				$res = self::fourOhFourSamples($urls);

				$html = '<style>.tres tr:nth-child(even) {background: #ccc;}</style>';
				$html .= $res.'<table class="tres"><tr><th width="400px">URL</th><th>Error</th></tr>';
				foreach ($urls as $row) {
					$html .= "<tr><td><a href='{$row['url']}'>{$row['sample']}</a></td><td>{$row['err']}</td></tr>";
				}
				$html .= '</table>';
			}
			print json_encode(array('result' => $html));
			return;
		}

		$out->setHTMLTitle('Admin - Samples - wikiHow');
		$userEmail = $user->getEmail();

$tmpl = <<<EOHTML
<style type="text/css">
.display_name_table {
	font-size: .9em;
	border: 1px solid #CCC;
	border-radius: 5px;
	background-color: #FFF;
}
.display_name_table tr:nth-child(even) { background: #EFEFEF; }
.display_name_table td, .display_name_table th { padding: 4px; text-align: left; }
.display_name_table .edit, .display_name_table .delete {
	width: 20px;
	cursor: pointer;
}
.display_name_table .edit { background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNS4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB3aWR0aD0iMjQuODc4cHgiIGhlaWdodD0iMjQuODg3cHgiIHZpZXdCb3g9IjAgMCAyNC44NzggMjQuODg3IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAyNC44NzggMjQuODg3IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxnPg0KCTxwYXRoIGZpbGw9IiM5M0I4NzQiIGQ9Ik0yNC44NjMsNi4wMTdsLTguMDMzLDguMDMybC05LjI0Myw5LjE4TDAsMjQuODg3bDEuNTk0LTcuNjVsOS4yNDQtOS4xOGw4LjAzMy04LjAzMw0KCQljMCwwLDEuNzIxLTAuMzgzLDQuMDE3LDEuOTEzQzI1LjE4Miw0LjI5NSwyNC44NjMsNi4wMTcsMjQuODYzLDYuMDE3eiBNNy45MDUsMjEuNTczYzAsMC0wLjA2My0xLjQwMy0xLjY1Ny0yLjk5Nw0KCQljLTEuNTMtMS41OTUtMi45OTctMS41OTUtMi45OTctMS41OTVMMi41NSwxNy43NDdsLTAuNTczLDIuNTVjMC40NDYsMC4yNTUsMC45NTYsMC41NzQsMS40NjYsMS4wODMNCgkJYzAuNTc0LDAuNTc0LDAuODkzLDEuMDIsMS4xNDcsMS41M2wyLjU1MS0wLjU3NEw3LjkwNSwyMS41NzN6Ii8+DQo8L2c+DQo8L3N2Zz4NCg==) no-repeat center center / 16px; }
.display_name_table .delete { background: url(../../skins/owl/images/ico-x.png) no-repeat center center / 16px; }
.display_name_table input[type='text'] { width: 100%; }
</style>


<form id="import_the_samples" method="post" action="/Special:AdminSamples">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Admin Samples
</div>

<h3>Import Samples</h3>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of Google Drive ids such as <code style="font-weight: bold;">12345678thx11383263827</code> to process.<br />
	One per line.
</div>
<textarea id="samples-list" name="samples-list" type="text" rows="10" cols="70"></textarea><br />
<input type='button' value='Import now!' id='import_samples' style='padding: 5px;' />
<input type='checkbox' name='cfp' id='cfp' checked="checked" /> <label for='cfp' style="font-size: 13px; margin: 20px 0 7px 0;">Check for plagiarism</label><br /><br />
<div id='import_result'></div>
</form>

<form id="404_samples" method="post" action="/Special:AdminSamples">
<h3>404 Samples</h3>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of URLs such as <code style="font-weight: bold;">http://www.wikihow.com/Sample/Functional-Resume</code> to process.<br />
	One per line.
</div>
<input type='hidden' name='action' value='remove' />
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea><br />
<button id="pages-go" disabled="disabled" style="padding: 5px;">process</button><br/>
<br/>
<div id="pages-result"></div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#pages-go')
			.removeAttr('disabled')
			.click(function () {
				var form = $('#404_samples').serializeArray();
				$('#pages-result').html('loading...');
				$.post('/Special:AdminSamples',
					form,
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});

		// $('#pages-list')
			// .focus();

		$('#import_samples').click(function() {
			var form = $('#import_the_samples').serializeArray();
			$('#import_result').html('importing...');
			$.post('/Special:AdminSamples?action=import',
				form,
				function(data) {
					$('#import_result').html(data['result']);
					$('#import_samples').removeAttr('disabled');
				},
				'json');
			return false;
		});

		$('.display_name_table .edit').click(function() {
			//reset any open inputs
			$('.display_name_table').find('.dname_edit').each(function() {
				$(this).parent().html($(this).attr('value'));
			});
			var samp = $(this).prev().prev().html();
			var dname = $(this).prev();
			$(dname).html('<input type=\"text\" class=\"dname_edit\" name=\"dname_edit\" value=\"'+$(dname).html()+'\" /> <input type=\"button\" id=\"dname_change\" value=\"Update\" />');

			$('#dname_change').click(function() {
				var new_dname = $(this).prev().attr('value');
				$.post('/Special:AdminSamples?action=edit', { 'sample': samp, 'dname': new_dname, 'pages-list': 'nothing' }, function(data) {
					if (data.length) {
						$(dname).html(new_dname);
					}
					else {
						alert('Error: Display name not changed.');
					}
				});
			});
		});

		$('.display_name_table .delete').click(function() {
			var samp = $(this).prev().prev().prev().html();
			var row = $(this).parent();
			if (confirm('Are you sure you want to remove the display name from '+samp+'?')) {
				$.post('/Special:AdminSamples?action=delete', { 'sample': samp, 'pages-list': 'nothing' }, function(data) {
					if (data.length) {
						alert(data);
						$(row).hide();
					}
				});
			}
		});

		$('#add_new_dname').click(function() {
			var samp = $('#new_sample').attr('value');
			var dname = $('#new_dname').attr('value');

			$.post('/Special:AdminSamples?action=addnew', { 'sample': samp, 'dname': dname, 'pages-list': 'nothing' }, function(data) {
				if (data.length) {
					$('#new_sample').attr('value','');
					$('#new_dname').attr('value','');
					alert(data);
					location.reload();
				}
				else {
					alert('Error: Display name not added.');
				}
			});
		});
	});
})(jQuery);
</script>
EOHTML;

		$display_names = self::getDisplayNames();

		$out->addHTML($tmpl.$display_names);
	}
}
