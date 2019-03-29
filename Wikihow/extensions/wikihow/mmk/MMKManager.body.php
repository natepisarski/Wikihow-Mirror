<?php

/*
CREATE TABLE mmk.mmk_manager (
  mmk_position int(11) NOT NULL PRIMARY KEY,
  mmk_keyword varchar(255),
  mmk_status int(4) NOT NULL DEFAULT 0,
  mmk_page_title varchar(255) NOT NULL,
  mmk_page_id int(11) NOT NULL,
  mmk_old_page_id int(11) NOT NULL,
  mmk_rating varchar(1),
  mmk_rating_date varchar(8),
  mmk_language_code varchar(2) NOT NULL,
  mmk_last_updated timestamp NOT NULL DEFAULT 0 ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE mmk.mmk_manager_log (
  mml_uploaded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  mml_notes varchar(255)
);
 */


class MMKManager extends UnlistedSpecialPage {

	const MMK_STATUS_ANY		= -1;	//don't worry about the status (also don't mark the as being in review)
	const MMK_STATUS_DEFAULT 	= 0;	//unprocessed/available
	const MMK_STATUS_IN_REVIEW 	= 1;	//in review for duplicate checking/matching against wikiHow pages
	const MMK_STATUS_WRITING 	= 2;	//writing (query has been sent out for writing, but has not been published yet)
	const MMK_STATUS_MATCHED	= 3; 	//matched (query has been associated with a wikiHow page)
	const MMK_STATUS_DONE		= 4; 	//done (matched page, editing and visual work is complete)
	const MMK_STATUS_BAD		= 5;	//bad (query is a topic we do not want or is malformed/not a how to)

	static $status_names = array('Available','In Review','Writing','Matched','Done','Bad');
	static $title_changes = 0;
	static $errors = array();

	public function __construct() {
		global $wgHooks;
		$this->action = RequestContext::getMain()->getTitle()->getPartialUrl();
		parent::__construct($this->action);
		$wgHooks['ShowSideBar'][] = array('MMKManager::removeSideBarCallback');
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	static function httpDownloadHeaders($filename) {
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="' . $filename . '"');
	}

	function doKeywordQuery($posted_values) {
		$langCode = $this->getLanguage()->getCode();

		$keywords = $posted_values['keywords'];
		$query_rank = (int) ($posted_values['query_rank'] ?? 1);
		$query_status = (int) ($posted_values['query_status'] ?? 0);
		$limit = $posted_values['query_limit'];
		$change_status = $posted_values['auto_mark_status'];

		if (empty($limit)) $limit = 1000;

		$dbr = wfGetDB(DB_REPLICA);
		$kws = ($keywords) ? ' and match(title) against (' . $dbr->addQuotes($keywords) . ') ' : '';

		//status queries need a little help
		if ($query_status == self::MMK_STATUS_ANY)
			$status_query = '';
		elseif ($query_status == self::MMK_STATUS_DEFAULT)
			$status_query = ' and (mmk_status = 0 or mmk_status IS NULL) ';
		else
			$status_query = " and mmk_status = $query_status ";

		// Find keywords, which match keyword database
		$sql = "select keywords.* from mmk.keywords
				left join mmk.mmk_manager on position = mmk_position
				where position >= $query_rank $kws $status_query
				and (mmk_language_code = " . $dbr->addQuotes($langCode) . " or mmk_language_code IS NULL)
				order by position limit $limit";
		$res = $dbr->query($sql, __METHOD__);

		$queryInfo = array();
		foreach ( $res as $row ) {
			$queryInfo[] = array('title' => $row->title, 'position' => $row->position);
		}

		header("Content-Type: text/tsv");
		header("Content-Disposition: attachment; filename=\"keyword.xls\"");

		print ("Keyword\tPosition\tStatus\tTitle\trating\trating date\tlast_updated\tti_is_top10k\tti_top10k\tFellow Edit\tlast_fellow_edit_timestamp\twikiphoto_timestamp\thelpful_percentage\thelpful_total\n");
		foreach ( $queryInfo as $qi ) {
			$qi['status'] = 0;
			$qi['page'] = '';
			$qi['page_id'] = '';
			$qi['old_page_id'] = '';
			$qi['rating'] = '';
			$qi['rating date'] = '';
			$last_updated = '';
			$row = '';

			$altKeywords = array($dbr->addQuotes($qi['title']));
			$sql = 'select ti.*, mmkm.* from mmk.mmk_manager mmkm
					left join ' . TitusDB::getDBname().'.'.TitusDB::TITUS_INTL_TABLE_NAME . ' ti
					on ti_page_id = mmk_page_id and ti_language_code = '. $dbr->addQuotes($langCode).'
					where mmk_keyword in (' . implode(',', $altKeywords) . ')
					and mmk_language_code = ' . $dbr->addQuotes($langCode);
			$res = $dbr->query($sql, __METHOD__);

			foreach ($res as $row) {
				$qi['status'] = $row->mmk_status;
				$qi['page'] = ($row->mmk_page_title) ? $row->mmk_page_title : $row->ti_page_title;
				$qi['page_id'] = $row->mmk_page_id;
				$qi['old_page_id'] = $row->mmk_old_page_id;
				$qi['rating'] = ($row->mmk_rating) ? $row->mmk_rating : $row->ti_rating;
				$qi['rating date'] = ($row->mmk_rating_date) ? $row->mmk_rating_date : $row->ti_rating_date;
				$last_updated = $row->mmk_last_updated;
			}

			//make title clickable
			$page = ($qi['page']) ? 'http://www.wikihow.com/'.$qi['page'] : '';

			print $qi['title'] . "\t" . $qi['position'] . "\t" . $qi['status'] . "\t" . $page. "\t" . $qi['rating'] . "\t" . $qi['rating date'] . "\t" .  $row->mmk_last_updated;
			if ( $row->ti_page_id ) {
				print "\t" . $row->ti_is_top10k . "\t" . $row->ti_top10k . "\t" . $row->ti_last_fellow_edit . "\t" . $row->ti_last_fellow_edit_timestamp . "\t" . $row->ti_wikiphoto_timestamp . "\t" . $row->ti_helpful_percentage . "\t" . $row->ti_helpful_total;
			}
			print "\n";

			//auto-update the status
			if ($query_status == self::MMK_STATUS_DEFAULT && $change_status == "on")
				$this->setStatus($qi,1);
		}
		exit;
	}

	function processUpload($uploadfile) {
		if (!file_exists($uploadfile) || !is_readable($uploadfile)) {
			self::$errors[] = 'Bad file. Could not upload.';
			return;
		}

		$content = file_get_contents($uploadfile);
		if ($content === false) {
			self::$errors[] = 'Bad file. Could not upload.';
			return;
		}

		$rows = preg_split('@(\r|\n|\r\n)@m', $content);
		$header = NULL;
		$result_log = array();
		self::$title_changes = 0;
		foreach ($rows as $row) {
			$fields = explode("\t", $row);
			// skip any line that doesn't have at least a pageid and a custom title
			if (count($fields) < 2) continue;
			$fields = array_map(trim, $fields);
			// skip first line if it's the position\t... header
			$position = intval($fields[1]);
			if (!$position) {
				if (!$header) $header = $fields;
				continue;
			}

			$fields = array_pad($fields, count($header), '');
			$fields = array_combine($header, $fields);

			//resetting "in review" status to "default"
			$fields['Status'] = ($fields['Status'] == 1) ? 0 : $fields['Status'];

			//save this to the db
			if ($this->saveUploadedRow($fields)) {
				//update counts for different statuses
				$result_log[$fields['Status']]++;
			}
		}

		//log this upload
		$this->logUpload($result_log);

		return 'uploaded';
	}

	private function saveUploadedRow($row) {
		$dbw = wfGetDB(DB_MASTER);
		$langCode = $this->getLanguage()->getCode();

		$new_page_id = '';

		//what is the page according to the current table?
		$res = $dbw->select('mmk.mmk_manager',array('mmk_page_title','mmk_page_id'),array('mmk_position' => $row['Position']), __METHOD__);
		if ($res) {
			foreach ($res as $this_row) {
				$old_page_title = $this_row->mmk_page_title;
				$old_page_id = $this_row->mmk_page_id;
			}
		}
		else {
			$old_page_title = '';
			$old_page_id = '';
		}

		$new_page_title = trim(preg_replace('@http://www.wikihow.com/@i','',$row['Title']));
		$new_page_title = preg_replace('@ @','-',$new_page_title);
		$new_page_title = urldecode($new_page_title);

		if ($old_page_title != $new_page_title) {
			//I feel a disturbance in the Force...
			$page_title = $new_page_title;
			$page_id = '';

			if ($new_page_title != '') {
				//we have a brand new one!
				//grab page id
				$title = Title::newFromText($new_page_title);
				if ($title) {
					$wp = new WikiPage($title);
					if ($wp) {
						$page_id = $wp->getID();
					}
				}
				if (!$page_id) {
					self::$errors[] = 'Could not find the page for '.$new_page_title.'. Not updated';
					return false;
				}
			}

			//count this so that we can log how many we change
			if ($old_page_title != '') self::$title_changes++;
		}
		else {
			//same ol', same ol'
			$page_title = $old_page_title;
			$page_id = $old_page_id;
		}

		$position = array('mmk_position' => $row['Position']);
		$updates = array('mmk_keyword' => $row['Keyword'],
						'mmk_status' => $row['Status'],
						'mmk_page_title' => is_null($page_title) ? '' : $page_title,
						'mmk_page_id' => is_null($page_id) ? '' : $page_id,
						'mmk_old_page_id' => is_null($old_page_id) ? '' : $old_page_id,
						'mmk_rating' => $row['rating'],
						'mmk_rating_date' => $row['rating date'],
						'mmk_language_code' => $langCode,
						'mmk_last_updated' => wfTimeStampNow());

		$res = $dbw->upsert('mmk.mmk_manager',array_merge($position,$updates),$position,$updates,__METHOD__);

		if (!$res) {
			self::$errors[] = 'Error updating the database for keywords = '.$row['Keyword'].'. Check your data. Maybe something funky in there?';
			return false;
		}

		return true;
	}

	private function setStatus($row,$new_state) {
		$dbw = wfGetDB(DB_MASTER);
		$langCode = $this->getLanguage()->getCode();

		$position = array('mmk_position' => $row['position']);
		$updates = array('mmk_keyword' => $row['title'],
						'mmk_status' => $new_state,
						'mmk_page_title' => is_null($page_title) ? '' : $page_title,
						'mmk_page_id' => is_null($page_id) ? '' : $page_id,
						'mmk_old_page_id' => is_null($old_page_id) ? '' : $old_page_id,
						'mmk_rating' => $row['rating'],
						'mmk_rating_date' => $row['rating date'],
						'mmk_language_code' => $langCode,
						'mmk_last_updated' => wfTimeStampNow());

		$res = $dbw->upsert('mmk.mmk_manager',array_merge($position,$updates),$position,$updates,__METHOD__);
		return $res;
	}

	private function logUpload($log) {
		$dbw = wfGetDB(DB_MASTER);

		$notes = 'Queries added to writing: '.intval($log[self::MMK_STATUS_WRITING]).';
				Queries matched: '.intval($log[self::MMK_STATUS_MATCHED]).';
				Queries done: '.intval($log[self::MMK_STATUS_DONE]).';
				Queries bad: '.intval($log[self::MMK_STATUS_BAD]).';
				Queries released: '.intval($log[self::MMK_STATUS_DEFAULT]).';
				Query associations changed: '.intval(self::$title_changes).'.' ;

		$res = $dbw->insert('mmk.mmk_manager_log', array('mml_notes' => $notes), __METHOD__);
		return $res;
	}

	private function makeStatusPullDown() {
		$html = '<select name="query_status" id="query_status">';

		foreach (self::$status_names as $key => $match) {
			$html .= "<option value='$key'>$match</option>";
		}
		$html .= '<option value="-1">Any</option>
				</select>';

		return $html;
	}

	private function makeStatusKey() {
		$html = '<ul>';
		foreach (self::$status_names as $key => $match) {
			$html .= "<li>$key = $match</li>";
		}
		$html .= '</ul>';

		return $html;
	}

	private function getRecentActivity() {
		$dbr = wfGetDB(DB_REPLICA);
		$html = '';

		$res = $dbr->select('mmk.mmk_manager_log', array('*'), array(), __METHOD__, array('ORDER BY' => 'mml_uploaded DESC', 'LIMIT' => 10));
		foreach ($res as $row) {
			$html .= '<p><b>'.	$row->mml_uploaded.'</b><br />'.$row->mml_notes.'</p>';
		}

		return $html;
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgIsDevServer;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		// Check permissions
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$titusHost = 'titus.wikiknowhow.com';
		if (!$wgIsDevServer && $_SERVER['HTTP_HOST'] != $titusHost) {
			$out->redirect("https://$titusHost/Special:MMKManager");
		}

		//some of these take a little while
		set_time_limit(0);

		if ($req->getVal('keyword')) {
			$this->doKeywordQuery($req->getValues());
			return;
		}
		elseif ($req->getVal('upload')) {
			$html = $this->processUpload($req->getFileTempName('adminFile'));
		}

		$out->setHTMLTitle('MMK Manager - wikiHow');
		$out->setPageTitle('MMK Manager');

		$tmpl = $this->getGuts();

		if ($html) $tmpl .= $html;

		if (!empty(self::$errors)) {
			$errors = '<div class="errors">ERRORS:<br />'.implode('<br />',self::$errors).'</div>';
			$tmpl = $errors.  $tmpl;
		}

		$out->addHTML($tmpl);
	}

	function getGuts() {
		$action = $this->action;
		$statuses = $this->makeStatusPullDown();
		$statuses_key = $this->makeStatusKey();
		$log = $this->getRecentActivity();
		return <<<EOHTML
		<script src='/extensions/min/?f=extensions/wikihow/common/download.jQuery.js,extensions/wikihow/mobile/webtoolkit.aim.min.js'></script>
		<style>
			.sm { font-variant:small-caps; letter-spacing:2px; margin-right: 25px; }
			.bx { padding: 5px 10px 5px 10px; margin-bottom: 15px; border: 1px solid #dddddd; border-radius: 10px 10px 10px 10px; }
			.bx p { padding: .5em 0; }
			#auto_mark_status { margin-left: 1.5em; }
			#admin-result ul { margin-top: 0; font-size: 10px; }
			#admin-result li { margin: 3px 0; }
			.recent_log { font-size: .8em; }
			.errors { color: #C00; }
		</style>
		<form action="/Special:$action?keyword=1" method="post">
		<div class=bx>
			<p>
				<span class=sm>Keyword Search</span>
				<input type='text' value='' name='keywords' id='keywords' />
			</p>
			<p>
				<span class=sm>Starting Rank</span>
				<input type='text' value='1' name='query_rank' id='query_rank' />
			</p>
			<p>
				<span class=sm>Query Status</span>
				$statuses
				<input type="checkbox" name="auto_mark_status" id="auto_mark_status" />
				<label for="auto_mark_status">claim titles</label>
			</p>
			<p>
				<span class=sm>Number of Results</span>
				<input type='text' value='1000' name='query_limit' id='query_limit' />
			</p>
			<p><button type="submit" id="keyword-submit">submit</button></p>
		</div>
		</form>

		<form id="admin-upload-form" action="/Special:$action?upload=1" method="post" enctype="multipart/form-data">
		<div class='bx'>
			<span class='sm'>Upload</span>
			<input type="file" id="adminFile" name="adminFile" /><br /><br />
			<div id="admin-result">
				<ul>
					<li>The file needs to keep the same headers and format as you download from here.</li>
					<li>The file needs to be tab delimited.</li>
					<li>Only the <b>Status</b>, <b>Title</b>, <b>rating</b>, &amp; <b>rating date</b> columns can be edited.</li>
					<li>Status field:
						$statuses_key
					</li>
				</ul>
			</div>
			<div id='keyword-results'></div>
		</div>
		</form>

		<div class=bx>
			<p class=sm>Recent Activity Log</p>
			<div class="recent_log">$log</div>
		</div>

		<script>
			$('#adminFile').change(function () {
				var filename = $('#adminFile').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#admin-result').html('uploading file...');
					$('#admin-upload-form').submit();
				}
				return false;
			});
		</script>
EOHTML;
	}

}
