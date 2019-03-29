<?php

/**
 * Customizes a page's meta information, such as <title> and
 * <meta name="description" .. head elements.
 */
class AdminCustomMeta extends UnlistedSpecialPage {

	var $customMeta, $type;

	public function __construct() {
		global $wgHooks;
		$this->action = RequestContext::getMain()->getTitle()->getText();
		parent::__construct($this->action);
		$wgHooks['ShowSideBar'][] = array('AdminCustomMeta::removeSideBarCallback');
	}

	// Callback indicating to remove the right rail
	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	private static function displayRecentChanges() {
		$changes = CustomMetaChangesLog::dbGetRecentChanges(10);
		$headers = CustomMetaChangesLog::getColumnHeaders();

		if (!$changes) {
			return 'No recent changes found';
		}

		$html = '';
		$html .= '<table width="100%">';
		// use html5 column groups to set widths
		$html .= '<colgroup>
	<col style="width:10%">
	<col style="width:8%">
	<col style="width:7%">
	<col style="width:75%">
</colgroup>';

		$headHtml = join('', array_map( function($a) use($headers) {
			return '<th>' . $headers[$a] . '</th>';
		}, array_keys($changes[0]) ) );
		$html .= "<tr>" . $headHtml . "</tr>\n";
		foreach ($changes as $change) {
			foreach ($change as $col => &$val) {
				$val = CustomMetaChangesLog::toString($col, $val);
			}
			$html .= '<tr>';
			$html .= "<td>{$change['mccl_timestamp']}</td>";
			$html .= "<td>{$change['mccl_userid']}</td>";
			$html .= "<td>{$change['mccl_type']}</td>";
			$html .= "<td>{$change['mccl_summary']}</td>";
			$html .= "</tr>\n";
		}
		$html .= '</table>';
		return $html;
	}

	private function downloadChanges() {
		$filePrefix = $this->customMeta->getFilePrefix();
		self::httpDownloadHeaders($filePrefix . date('Ymd') . '.txt');
		$headers = $this->customMeta->getCustomHeaders();
		$list = $this->customMeta->getCustomList(false /* return unlabelled data */);
		print join("\t", $headers) . "\n";
		foreach ($list as $id => $item) {
			print "{$id}\t" . join("\t", $item) . "\n";
		}
	}

	private static function httpDownloadHeaders($filename) {
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="' . $filename . '"');
	}

	private function processChangesUpload($filename) {
		$content = file_get_contents($filename);
		if ($content === false) {
			$error = 'internal error opening uploaded file';
			return array('error' => $error);
		}
		$lines = preg_split('@(\r|\n|\r\n)@m', $content);
		$changes = array();
		foreach ($lines as $line) {
			$fields = explode("\t", $line);
			// skip any line that doesn't have at least a pageid and a custom title/desc
			if (count($fields) < 2) continue;
			$fields = array_map(trim, $fields);
			// skip first line if it's the pageid\t... header
			$pageid = (int)$fields[0];
			$custom = $fields[1]; // can be the empty string
			$custom_note = count($fields) > 2 ? $fields[2] : '';
			if (!$pageid) continue;
			$changes[$pageid] = array(
				'custom' => $custom,
				'custom_note' => $custom_note);
		}
		if (!$changes) {
			return array('error' => 'No lines to process in upload');
		} else {
			return $this->customMeta->processChanges($changes);
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		// Check permissions
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		// Figure out whether we're editing titles or meta descs
		$this->type = $par;
		if (!$this->type && $this->action == 'AdminTitles') {
			$this->type = 'title';
		} elseif (!$this->type && $this->action == 'AdminMetaDescs') {
			$this->type = 'desc';
		}
		if ($this->type == 'title') {
			$this->customMeta = new CustomTitleChanges;
		} elseif ($this->type == 'desc') {
			$this->customMeta = new CustomDescChanges;
		} else {
			$m = new Mustache_Engine;
			$html = $m->render('Please use either the <a href="/Special:AdminTitles">titles</a> ' .
				'or <a href="/Special:AdminMetaDescs">meta descriptions</a> editing tool.', []);
			$out->addHTML($html);
			return;
		}

		$req = $this->getRequest();
		if ($req->wasPosted()) {
			set_time_limit(0);
			$out->disable();
			$error = "";
			$action = $req->getVal('action');
			if ($action == 'save-list') {
				$filename = $req->getFileTempName('adminFile');
				$ret = $this->processChangesUpload($filename);
				print json_encode( ['results' => $ret] );
			} elseif ($action == 'retrieve-list') {
				$this->downloadChanges();
			} else {
				$error = 'unknown action';
			}
			if ($error) {
				print json_encode(array('error' => $error));
			}
			return;
		}

		$name = $this->type == 'title' ? 'Titles' : 'Meta Descriptions';
		$out->setHTMLTitle('Admin - Custom ' . $name . ' - wikiHow');
		$out->setPageTitle('Customize ' . $name);

		$out->addModules( ['wikihow.common.jquery.download', 'wikihow.common.aim'] );
		$tmpl = $this->getGuts();
		$out->addHTML($tmpl);
	}

	private function getGuts() {
		$action = $this->action;
		$recent = self::displayRecentChanges();
		$switchPage = $this->type == 'title' ? 'Special:AdminMetaDescs' : 'Special:AdminTitles';
		$switchName = $this->type == 'title' ? 'meta descriptions' : 'titles';
		$switchPage2 = $this->type == 'title' ? 'Special:AdminEditPageTitles' : 'Special:AdminEditMetaInfo';
		$switchName2 = $this->type == 'title' ? 'individual titles' : 'individual meta descriptions';
		$tmpl = new EasyTemplate( __DIR__ . '/templates' );
		$tmpl->set_vars( [
			'action' => $action,
			'recent' => $recent,
			'switchPage' => $switchPage,
			'switchName' => $switchName,
			'switchPage2' => $switchPage2,
			'switchName2' => $switchName2,
		] );
		$html = $tmpl->execute('admin-custom-meta.tmpl.php');
		// Note: we should refactor this to use Mustache
		//$mustache = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates')]);
		return $html;
	}
}

class CustomTitleChanges extends CustomMetaChanges {
	public function __construct() {
		parent::__construct('title');
	}

	// list custom titles from CustomTitle class
	public function getCustomList($labelledData) {
		$dbr = wfGetDB(DB_REPLICA);
		$titles = CustomTitle::dbListCustomTitles($dbr);
		$output = array();
		foreach ($titles as $row) {
			$id = $row['ct_pageid'];
			$data = [ 'custom' => $row['ct_custom'], 'custom_note' => $row['ct_custom_note'] ];
			if ($labelledData) {
				$output[ $id ] = $data;
			} else {
				$output[ $id ] = [ $data['custom'], $data['custom_note'] ];
			}
		}
		return $output;
	}

	// return how columns are displayed to the user
	public function getCustomHeaders() {
		return ['pageid', 'custom_title', 'custom_note'];
	}

	protected function dbDeleteItemID($dbw, $pageid) {
		CustomTitle::dbRemoveTitleID($dbw, $pageid);
	}

	protected function dbSetItemID($dbw, $titleObj, $item) {
		CustomTitle::dbSetCustomTitle($dbw, $titleObj, $item['custom'], $item['custom_note']);
	}
}

class CustomDescChanges extends CustomMetaChanges {

	const CUSTOM_MAX_LENGTH = 700;

	// this reflects the database schema maximum currently
	const CUSTOM_NOTE_MAX_LENGTH = 255;

	public function __construct() {
		parent::__construct('desc');
	}

	public function getCustomList($labelledData) {
		$descs = ArticleMetaInfo::dbListEditedDescriptions();
		$output = array();
		foreach ($descs as $row) {
			$id = $row['ami_id'];
			$data = [ 'custom' => $row['ami_desc'], 'custom_note' => $row['ami_edited_note'] ];
			if ($labelledData) {
				$output[ $id ] = $data;
			} else {
				$output[ $id ] = [ $data['custom'], $data['custom_note'] ];
			}
		}
		return $output;
	}

	public function getCustomHeaders() {
		return ['pageid', 'custom_desc', 'custom_note'];
	}

	protected function dbDeleteItemID($dbw, $pageid) {
		$title = Title::newFromID($pageid);
		if ($title && $title->exists()) {
			$ami = new ArticleMetaInfo($title);
			$ami->setEditedDescription('', '');
			$ami->resetMetaData();
		}
	}

	protected function dbSetItemID($dbw, $titleObj, $item) {
		$ami = new ArticleMetaInfo($titleObj);

		// NOTE/WARNING: we don't consider utf8 strings at all here, which can be more than
		// 1 byte per character. If we break the string in the middle of a utf8 character,
		// it could make an invalid encoding.
		$item['custom'] = static::maybeShorten($item['custom'], self::CUSTOM_MAX_LENGTH);
		$item['custom_note'] = static::maybeShorten($item['custom_note'], self::CUSTOM_NOTE_MAX_LENGTH);

		$ami->setEditedDescription($item['custom'], $item['custom_note'], self::CUSTOM_MAX_LENGTH);
	}
}

abstract class CustomMetaChanges {

	var $type;

	protected function __construct($type) {
		$this->type = $type;
	}

	public abstract function getCustomList($labelledData);
	public abstract function getCustomHeaders();
	protected abstract function dbDeleteItemID($dbw, $pageid);
	protected abstract function dbSetItemID($dbw, $titleObj, $item);

	public function getFilePrefix() {
		return 'custom_' . $this->type . 's_';
	}

	public function processChanges($changes) {
		$list = $this->getCustomList(true /* return labelled data */);
		$summary = '';
		$stats = array('new' => 0, 'delete' => 0, 'change' => 0, 'nochange' => 0);
		foreach ($changes as $pageid => $change) {
			$pageid = (int)$pageid;
			if (!$pageid) continue;

			if (!isset($list[$pageid])) {
				if ($change['custom']) {
					$list[$pageid] = array(
						'custom' => $change['custom'],
						'custom_note' => $change['custom_note'],
						'status' => 'new');
					$summary .= "New custom $pageid: {$change['custom']}\n";
					$stats['new']++;
				} else {
					// ignore any changes set to "delete" if they
					// already don't exist
				}
			} elseif ($list[$pageid]['status']) {
				return array('error' => "Error: pageid $pageid exists twice in input file");
			} elseif (!$change['custom']) {
				$list[$pageid]['status'] = 'delete';
				$summary .= "Delete $pageid\n";
				$stats['delete']++;
			} else {
				if ($list[$pageid]['custom'] != $change['custom']
					|| $list[$pageid]['custom_note'] != $change['custom_note'])
				{
					$list[$pageid] = array(
						'custom' => $change['custom'],
						'custom_note' => $change['custom_note'],
						'status' => 'change');
					$summary .= "Changed custom $pageid: {$change['custom']}\n";
					$stats['change']++;
				} else {
					// No custom title/desc or note change
					$list[$pageid]['status'] = 'nochange';
					$stats['nochange']++;
				}
			}
		}

		// [sc] per the new CustomTitles change, this tool will include most of our
		// article titles, so turning this feature off (so the spreadsheets can be smaller)
		//
		// // For any item no longer in change set, we delete them (per Chris, 7/12)
		// // so that there is effectively a "Master" spreadsheet of custom changes
		// foreach ($list as $pageid => &$item) {
		// 	if (!$item['status']) {
		// 		$item['status'] = 'delete';
		// 		$summary .= "Delete $pageid\n";
		// 		$stats['delete']++;
		// 	}
		// }

		// I separated this into a different function so we could fairly
		// easily do dry runs
		$stats['errors'] = $this->applyChanges($list, $summary);
		return array('stats' => $stats, 'summary' => $summary);
	}

	// apply changes using super classes
	protected function applyChanges($list, $summary) {
		$context = RequestContext::getMain();

		$dbw = wfGetDB(DB_MASTER);
		$errors = array();
		foreach ($list as $pageid => $item) {
			$status = $item['status'];
			if ($status == 'delete') {
				$this->dbDeleteItemID($dbw, $pageid);
			} elseif ($status == 'change' || $status == 'new') {
				$titleObj = Title::newFromID($pageid);
				if ($titleObj && $titleObj->exists() && $titleObj->inNamespace(NS_MAIN)) {
					$this->dbSetItemID($dbw, $titleObj, $item);
				} else {
					$errors[] = $pageid;
				}
			} else {
				// status == 'nochange'
			}
		}

		if ($errors) {
			$summary = "Warning: there were unexpected errors with page IDs: " . join(',', $errors) . "\n" . $summary;
		}
		if (!$summary) {
			$summary = 'No changes';
		}
		CustomMetaChangesLog::dbAddChangeSummary( $dbw, wfTimestampNow(), $context->getUser()->getID(), $this->type, $summary );

		return $errors;
	}

	// Utility function that makes sure we don't get db errors from crazy long descriptions
	protected static function maybeShorten($str, $len) {
		if (strlen($str) > $len) {
			$str = substr($str, 0, $len);
		}
		return $str;
	}
}

/*schema:
 *
CREATE TABLE meta_custom_change_log (
	mccl_timestamp varchar(14) NOT NULL,
	mccl_userid int(10) unsigned NOT NULL,
	mccl_type varchar(255) NOT NULL default '',
	mccl_summary BLOB,
	PRIMARY KEY(mccl_timestamp)
);
 */
class CustomMetaChangesLog {

	public static function dbGetRecentChanges($numChanges) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('meta_custom_change_log',
			'*',
			array(),
			__METHOD__,
			array('LIMIT' => $numChanges, 'ORDER BY' => 'mccl_timestamp DESC'));
		$changes = array();
		foreach ($res as $row) {
			$changes[] = (array)$row;
		}
		return $changes;
	}

	public static function dbAddChangeSummary($dbw, $timestamp, $userid, $changeType, $summary) {
		$row = array(
			'mccl_timestamp' => $timestamp,
			'mccl_userid' => $userid,
			'mccl_type' => $changeType,
			'mccl_summary' => $summary);
		$dbw->insert('meta_custom_change_log', $row, __METHOD__);
	}

	public static function getColumnHeaders() {
		return [
			'mccl_timestamp' => 'When',
			'mccl_userid' => 'User',
			'mccl_type' => 'Change type',
			'mccl_summary' => 'Summary' ];
	}

	public static function toString($col, $val) {
		static $usersCache = array();

		if ($col == 'mccl_timestamp') {
			$ts = wfTimestamp(TS_UNIX, $val);
			return date('Y-m-d', $ts);
		} elseif ($col == 'mccl_userid') {
			$userid = $val;
			if (!isset($users[$userid])) {
				$users[$userid] = User::newFromId($userid);
			}
			$user = $users[$userid];
			$usertext = $user ? $user->getName() : '';
			return $usertext;
		} elseif ($col == 'mccl_summary') {
			$summary = substr($val, 0, 200);
			if ($summary != $val) {
				$summary .= '...';
			}
			return $summary;
		} else {
			return $val;
		}
	}

}

