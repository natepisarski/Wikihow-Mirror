<?php

global $IP;

require_once("$IP/extensions/wikihow/Rating/RatingArticle.php");
require_once("$IP/extensions/wikihow/Rating/RatingSample.php");
require_once("$IP/extensions/wikihow/Rating/RatingStar.php");

class AdminClearRatings extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('AdminClearRatings');
	}

	/**
	 * Execute special page. Only available to wikihow staff.
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		if (!$this->userAllowed()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$out->setHTMLTitle('Admin - Clear Ratings - wikiHow');
		$out->setPageTitle('Clear Ratings for Multiple Pages');

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$html = '';

			set_time_limit(0);

			$pageList = $req->getVal('pages-list', '');
			$comment = '[Batch Clear] ' . $req->getVal('comment', '');

			if ($pageList) $pageList = urldecode($pageList);
			$pageList = preg_split('@[\r\n]+@', $pageList);
			$pageData = array();
			$failedPages = array();

			// Get the page titles from the URLs:
			foreach ($pageList as $url) {
				$trurl = trim($url);
				$partial = preg_replace('/ /', '-', self::getPartial($trurl));
				if (!empty($partial)) {
					$pageData[] = array('partial' => $partial, 'url' => $trurl);
				} elseif (!empty($trurl)) {
					$failedPages[] = $url;
				}
			}

			$html .= $this->generateResults($pageData, $failedPages, $comment);

			if (!empty($failedPages)) {
				$html .= '<br/><p>Unable to parse the following URLs:</p>';
				$html .= '<p>';
				foreach($failedPages as $p) {
					$html .= '<b>' . $p . '</b><br />';
				}
				$html .= '</p>';
			}
			$result = array('result' => $html);
			print json_encode($result);
			return;
		} else {
			$tmpl = self::getGuts('AdminClearRatings');
			$out->addHTML($tmpl);
		}

	}

	/**
	 * Given a URL or partial, give back the page title
	 */
	public static function getPartial($url) {
		$partial = preg_replace('@^https?://[^/]+@', '', $url);
		$partial = preg_replace('@^/@', '', $partial);
		return $partial;
	}

	function getGuts($action) {
		return "		<form method='post' action='/Special:$action'>
		<div>Enter a list of full URLs such as <code>https://www.wikihow.com/Kill-a-<a href='https://www.gva.be/cnt/blpbr_01728395/scorpions-bissen-in-sportpaleis'>Scorpion</a></code> or partial URLs like <code>Sample/Research-Outline</code> for pages whose ratings should be cleared.  One per line.</div>
		<div style='margin: 15px 0 0 0'>
			NOTE: This tool clears both Page Helpfulness and new Stu2. If you want to clear old Stu and new Stu2 for a page, visit <a href='/Special:AdminBounceTests'>Special:AdminBounceTests</a>.
		</div>
		<br/>
		<table><tr><td>Pages:</td><td><textarea id='pages-list' type='text' rows='10' cols='70'></textarea></td></tr>
		<tr><td>Reason:</td><td><textarea id='reason' type='text' rows='1' cols='70'></textarea></td></tr></table>
		<button id='pages-clear' disabled='disabled'>Clear</button>
		<br/><br/>
		<div id='pages-result'>
		</div>
		</form>

		<script>
		(function($){
			$(document).ready(function() {
				$('#pages-clear')
					.prop('disabled', false)
					.click(function() {
						$('#pages-result').html('Loading ...');
						$.post('/Special:$action',
							{ 'pages-list': $('#pages-list').val(),
							  'comment' : $('#reason').val()
							},
							function(data) {
								$('#pages-result').html(data['result']);
								$('#pages-list').focus();
							},
							'json');
						return false;
					});
				$('#pages-list').focus();
			});
		})(jQuery);
		</script>";
	}

	public function userAllowed() {
		$userGroups = $this->getUser()->getGroups();
		if ($this->getUser()->isBlocked() || !in_array('staff', $userGroups)) {
			return false;
		}

		return true;
	}

	private function generateResults($pageData, $failedPages, $comment) {

		// Set up the output table:
		$html = '<style>.tres tr:nth-child(even) {background: #e0e0e0;} .failed {color: #a84810;} .cleared {color: #48a810;}</style>';
		$html .= '<table class="tres"><tr>';
		$html .= '<th width="350px"><b>Page</b></th>';
		$html .= '<th width="50px"><b>Type</b></th>';
		$html .= '<th width="240px"><b>Status</b></th></tr>';

		$articleRatingTool = new RatingArticle();
		$sampleRatingTool = new RatingSample();
		$starRatingTool = new RatingStar();
		$dbr = wfGetDB(DB_REPLICA);
		$samplePrefix = 'Sample/';

		$user = $this->getUser();

		foreach ($pageData as $dataRow) {

			$p = $dataRow['partial'];
			$html .= '<tr>';
			$title = Title::makeTitleSafe(NS_MAIN, $p);
			$dataRow['title'] = $title;
			$dataRow['type'] = 'none';
			$tool = null;
			$notFound = false;

			if (!preg_match('/:/', $p) && !$title) {
				$notFound = true;
			} elseif (!preg_match('/:/', $p) && $title->exists()) {
				// It's an article in NS_MAIN:
				$artId = $title->getArticleID();
				if ($artId > 0) {
					$dataRow['type'] = 'article';
					$dataRow['pageId'] = $artId;
					$tool = $articleRatingTool;
				} else {
					$notFound = true;
				}
			} elseif (preg_match('@^Sample/@', $p)) {
				// It's a Sample:
				$dbKey = $title->getDBKey();
				$name = substr($dbKey, strlen($samplePrefix));
				$sampleId = $dbr->selectField('dv_sampledocs', 'dvs_doc', array('dvs_doc' => $name));
				if (!empty($sampleId)) {
					$dataRow['type'] = 'sample';
					$dataRow['pageId'] = $sampleId;
					$tool = $sampleRatingTool;
				} else {
					$notFound = true;
				}
			} elseif (preg_match('@^[0-9]+$@', $p)) {
				$title = Title::newFromID((int)$p);
				if ($title && $title->exists()) {
					$artId = $title->getArticleID();
					$dataRow['title'] = $title;
					$dataRow['pageId'] = $artId;
					$tool = $articleRatingTool;
				} else {
					$notFound = true;
				}
			} else {
				$notFound = true;
			}
			if ($notFound) {
				$html .= "<td>{$dataRow['url']}</td>"; // Title/URL
				$html .= "<td></td>"; // Type
				$html .= "<td><b><span class=\"failed\">Page not found</span></b></td>"; // Status
			} else {
				$status = '';
				$dataRow['pageRating'] = '';
				$dataRow['ratingCount'] = '';
				if ($tool) {
					$tablePrefix = $tool->getTablePrefix();
					// Active ratings (flag '_isdeleted' is 0):
					$ratRes = $dbr->select(
						$tool->getTableName(),
						array(
							"{$tablePrefix}page",
							"AVG({$tablePrefix}rating) as R",
							'count(*) as C'),
						array("{$tablePrefix}page" => "{$dataRow['pageId']}",
							  "{$tablePrefix}isdeleted" => 0),
						__METHOD__);
					// Active + inactive ratings:
					$ratResDel = $dbr->select(
						$tool->getTableName(),
						array(
							"{$tablePrefix}page",
							"AVG({$tablePrefix}rating) as R"),
						array("{$tablePrefix}page" => "{$dataRow['pageId']}"),
						__METHOD__);
					$ratResDelData = $ratResDel->fetchRow();
					if ($ratResDel->numRows() == 0 || !isset($ratResDelData['R'])) {
						$status = '<span class="failed">No ratings found</span>';
					} elseif ($user->getId() == 0) {
						$status = '<span class="failed">No permission: Not logged in?</span>';
					} else {
						$ratResData = $ratRes->fetchRow();
						$dataRow['pageRating'] = isset($ratResData['R']) ? $ratResData['R'] : 'N/A';
						$dataRow['ratingCount'] = $ratResData['C'] or 0;
						$tool->clearRatings($dataRow['pageId'], $user, $comment);
						if ($dataRow['type'] == 'sample') {
							// Also delete rating reasons for samples
							$tool->deleteRatingReason($dataRow['pageId']);
						}
						elseif ($dataRow['type'] == 'article') {
							// Also delete star ratings w/ articles
							$starRatingTool->clearRatings($dataRow['pageId'], $user, $comment);

							// clear data about the summary section (videos and helpfulness)
							self::resetSummaryData( $dataRow['pageId'] );
						}
						if (isset($ratResData['R'])) {
							$status = '<span class="cleared">Cleared</span>';
						} else {
							$status = 'No active ratings (already cleared?)';
						}
					}
				} else {
					$status = '<span class="failed">Server error (rating tool null)</span>';
				}

				$html .= "<td><a href='/{$dataRow['title']}' rel='nofollow'>{$dataRow['title']}</a></td>";
				$html .= "<td>{$dataRow['type']}</td>";
				$html .= "<td><b>{$status}</b></td>";
			}
		}
		unset($dataRow);

		$html .= '</table>';

		return $html;
	}

	private static function recordClearEvent( $pageId, $action, $domain = null ) {
		$dbw = wfGetDB( DB_MASTER );
		$table = 'clear_event';
		$date = gmdate( "Y-m-d H:i:s" );
		$insertData = array(
			'ce_page_id' => $pageId,
			'ce_action' => $action,
			'ce_date' => $date,
		);
		if ( $domain ) {
			$insertData['ce_domain'] = $domain;
		}
		$options = array( 'IGNORE' );
		$dbw->insert( $table, $insertData, __METHOD__, $options );
	}

	public static function resetSummaryData( $pageId ) {
		global $wgLanguageCode;
		$dbw = wfGetDB( DB_MASTER );

		$table = 'event_log';
		$var = '*';
		$summaryVideoActions = array( 'svideoplay', 'svideoview' );
		$cond = array(
			'el_page_id' => $pageId,
			'el_action' => $summaryVideoActions
		);
		$hasData = $dbw->selectRow( $table, $var, $cond, __METHOD__ );
		if ( $hasData ) {
			$domains = array( wfCanonicalDomain( $wgLanguageCode ), wfCanonicalDomain( $wgLanguageCode, true ) );
			foreach ( $domains as $domain ) {
				self::recordClearEvent( $pageId, 'summaryvideoevents', $domain );
			}
		}

		$table = 'item_rating';

		$helpfulnessActions = array( 'summaryvideohelp', 'summarytexthelp' );
		foreach ( $helpfulnessActions as $type ) {
			$cond = array(
				'ir_page_id' => $pageId,
				'ir_type' => $type
			);
			$hasData = $dbw->selectRow( $table, $var, $cond, __METHOD__ );
			if ( $hasData ) {
				self::recordClearEvent( $pageId, $type );
			}
		}
	}
}
