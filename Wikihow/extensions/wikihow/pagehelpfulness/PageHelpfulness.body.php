<?php

class PageHelpfulness extends UnlistedSpecialPage {
	public static $lastClear = null;

	public function __construct() {
		parent::__construct('PageHelpfulness');
	}

	public static function getRatingReasonFeedback($item, $type, $limit=10) {
		$dbr = wfGetDB(DB_REPLICA);

		$table = 'rating_reason';
		$var = array(
			'user_repr' => 'ratr_name',
			'feedback' => 'ratr_text',
			'rating' => 'ratr_rating',
			'detail' => 'ratr_detail',
			'type' => 'ratr_type',
			'timestamp' => "CONVERT_TZ(ratr_timestamp, @@session.time_zone, '+00:00')"
		);
		$cond = array(
			'ratr_item' => $item,
			'ratr_type' => $type
		);
		$options = array(
			'ORDER BY' => 'ratr_timestamp DESC',
			'LIMIT' => $limit
		);
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );

		//decho("here", $dbr->lastQuery());exit;
		$feedback = array();

		foreach ($res as $row) {
			$feedback[] = get_object_vars($row);
		}

		return $feedback;
	}

	protected static function convertRatingReasonFeedbackTimezone(&$feedback) {
		foreach (array_keys($feedback) as $k) {
			$datetime = new DateTime($feedback[$k]['timestamp']);
			$utcTZ = new DateTimeZone('UTC');
			$datetime->setTimeZone($utcTZ);
			$feedback[$k]['timestamp'] = $datetime->format('Y-m-d H:i:s');
		}
	}

	public static function getCombinedRatingFeedback($t) {
		$dbr = wfGetDB(DB_REPLICA);
		$limit = 10;

		$item = $t->getText();
		$type = array( 'article', 'itemrating' );
		$pageFeedback = self::getRatingReasonFeedback($item, $type, $limit);
		self::convertRatingReasonFeedbackTimezone($pageFeedback);

		$aid = $t->getArticleId();
		$combinedFeedback = array();

		if (class_exists('MethodHelpfulness\ArticleMethod')) {
			$methodFeedback = MethodHelpfulness\ArticleMethod::getLatestFeedback($aid, $limit);

			$itemCount = count( $pageFeedback ) + count( $methodFeedback );
			if ( $itemCount < $limit ) {
				$limit = $itemCount;
			}
			// Weave the page and method feedback together
			for ($i = 0, $j = 0; $i + $j < $limit;) {
				$pageTime = strtotime($pageFeedback[$i]['timestamp']);
				$methodTime = strtotime($methodFeedback[$j]['timestamp']);

				if ($pageTime > $methodTime) {
					$pageFeedback[$i]['displayDate'] = date("n/j/y", $pageTime);
					$combinedFeedback[] = $pageFeedback[$i];
					$i++;
				} else {
					$methodFeedback[$j]['displayDate'] = date("n/j/y", $methodTime);
					$combinedFeedback[] = $methodFeedback[$j];
					$j++;
				}
			}
		} else {
			$combinedFeedback = $pageFeedback;
		}

		//decho("combined", $combinedFeedback);exit;
		$result = '';

		foreach ($combinedFeedback as $row) {
			$phr_rating = $row['rating'] > 0 ? "phr_yes" : "phr_no";
			$rowClass = array();
			if (strtotime($row['timestamp']) < self::$lastClear) {
				$rowClass[] = "old_rating";
			}

			if ( $row['type'] == 'itemrating' ) {
				$rowClass[] = 'ph_ir_' . $row['detail'];
			}

			$text = $row['displayDate'] . ": " . $row['feedback'];
			$name = "";
			if ($row['user_repr'] && $row['user_repr'] != "MOBILE") {
				$name = "<em>".$row['user_repr']."</em><br />";
			}
			$rowClass = implode( ' ', $rowClass );
			$result .= "<tr class='$rowClass'><td><div class='phr_thumb $phr_rating'></div></td><td>$name$text</td></tr>";
		}

		return $result;
	}

	public static function getRatingsDetail($pageId) {
		$dbr = wfGetDB(DB_REPLICA);
		$table = "rating";
		$vars = array("rat_detail as D", "count(*) as C");
		$conds = array("rat_page = $pageId", "rat_detail > 0", "rat_isdeleted = 0");
		$options = array("GROUP BY" => "rat_detail");

		$res = $dbr->select($table, $vars, $conds, __METHOD__, $options);

		$result = array(1=>0, 2=>0, 3=>0, 4=>0);
		foreach ($res as $row) {
			$result[$row->D] = $row->C;
		}

		return $result;
	}

	private static function getAggregateRatingData( $pageId, $ratingData ) {
		$ratingCount = 0;
		$yesVotes = 0;

		if ( $ratingData && count($ratingData) && $ratingData[0]->date <= 0 ) {
			$ratingCount = $ratingData[0]->total;
			$yesVotes = $ratingData[0]->percent * $ratingCount / 100;
		}

		// now add the star ratings info
		$dbr = wfGetDB(DB_REPLICA);
		$table = "rating_star";
		$var = array( "sum(rs_rating)/5 as yesVotes", "count(*) as count" );
		$cond = array( "rs_page" => $pageId, "rs_isdeleted" => 0 );
		$row = $dbr->selectRow( $table, $var, $cond, __METHOD__ );

		$starYesVotes = $row ? $row->yesVotes : 0;
		$starRatingCount = $row ? $row->count : 0;

		$ratingCount += $starRatingCount;
		$yesVotes += $starYesVotes;
		$rating = 0;
		if ( $ratingCount > 0 ) {
			$rating = round( 100 * $yesVotes / $ratingCount );
		}

		$data = new stdClass();
		$data->rating = $rating;
		$data->ratingCount = $ratingCount;
		return $data;
	}

	public static function getRatingData($pageId, $type) {
		$dbr = wfGetDB(DB_REPLICA);
		$ri = new RateItem();
		$rt = $ri->getRatingTool($type);
		$table = $rt->getTableName();
		$prefix = $rt->getTablePrefix();
		$var = array("sum({$prefix}rating) as yes", "count(*) as total", "{$prefix}deleted_when as ts");
		$cond = array("{$prefix}page" => $pageId);
		if ($type == 'star') $cond[] = "{$prefix}isdeleted = 0";
		$options = array("GROUP BY" => "{$prefix}deleted_when", "ORDER BY" => "{$prefix}deleted_when DESC");
		$res = $dbr->select($table, $var, $cond, __METHOD__, $options);

		$result = array();
		foreach ($res as $row) {
			// all time stats skipping sections with less than 10 votes
			$data = new stdClass();

			$data->total = $row->total;

			$percent = round($row->yes*1000/$row->total)/10;
			$data->percent = $percent;
			if ($type == "star") {
				$data->percent = round($data->percent / 5, 1);
			}
			$time = strtotime($row->ts);
			$data->date = $time;
			// potentially do the following:
			// always show current score regardless of votes
			// but filter out past votes for values under 6
			//if ($time != 'current' && $row->total < 6) {
			//continue;
			//}
			$result[] = $data;
		}

		$last = end($result);
		if ($last && $last->date <= 0) {
			$result = array_merge(array_splice($result, -1), $result);
		}

		return $result;
	}

	public static function getClearEventDate( $pageId, $domain, $type, $dbr = null ) {
		if ( $dbr == null ) {
			$dbr = wfGetDB(DB_REPLICA);
		}

		$table = 'clear_event';
		$var = array( 'ce_date' );
		$cond = array(
			'ce_page_id' => $pageId,
			'ce_action' => $type,
		);
		// do not use the domain yet
		//if ( $domain ) {
			//$cond['ce_domain'] = $domain;
		//}
		$options = array( 'ORDER BY' => 'ce_date DESC' );

		$date = $dbr->selectField( $table, $var, $cond, __METHOD__, $options );
		return $date;
	}

	private static function getDisplayRatingHtml( $pageId ) {
		global $wgLanguageCode;
		if ( $wgLanguageCode != 'en' ) {
			return '';
		}
		$dbr = wfGetDB(DB_REPLICA);

		// see if the article has a summary video or text section
		$table = array( WH_DATABASE_NAME_EN.'.titus_copy' );
		$vars = array( 'ti_helpful_percentage_display_all_time', 'ti_helpful_percentage_display_soft_reset', 'ti_helpful_total_display_all_time' );

		$conds = array(
			'ti_page_id' => $pageId,
			'ti_language_code' => $wgLanguageCode,
		);

		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__ );
		if ( !$row ) {
			return '';
		}

		$sectionName = "Display Rating All Time";
		$percent = $row->ti_helpful_percentage_display_all_time;
		$total = $row->ti_helpful_total_display_all_time;
		$text = "$sectionName: $percent% -  $total votes";
		$html = Html::element( 'div', [], $text );

		$sectionName = "Display Rating Soft Reset";
		$percent = $row->ti_helpful_percentage_display_soft_reset;
		$total = $row->ti_helpful_total_display_all_time;
		$text = "$sectionName: $percent% -  $total votes";
		$html .= Html::element( 'div', [], $text );

		return $html;

	}

	private static function getSummarySectionRating( $pageId ) {
		global $wgLanguageCode;
		if ( $wgLanguageCode != 'en' ) {
			return '';
		}
		$dbr = wfGetDB(DB_REPLICA);

		// see if the article has a summary video or text section
		$table = array( WH_DATABASE_NAME_EN.'.titus_copy' );
		$vars = array( 'ti_summarized', 'ti_summary_video' );

		$conds = array(
			'ti_page_id' => $pageId,
			'ti_language_code' => $wgLanguageCode,
		);

		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__ );
		if ( !$row ) {
			return '';
		}

		$html = '';

		$table = 'item_rating';
		$vars = 'count(*)';
		$conds = array('ir_page_id' => $pageId );

		$sectionName = "Summary Video";
		$lookup = false;
		if ( $row->ti_summary_video ) {
			$lookup = true;
			$conds['ir_type'] = 'summaryvideohelp';
		} elseif ( $row->ti_summarized ) {
			$lookup = true;
			$conds['ir_type'] = 'summarytexthelp';
			$sectionName = "Summary Text";
		}
		$domain = '';
		$date = self::getClearEventDate( $pageId, $domain, $conds['ir_type'], $dbr );
		if ( $date ) {
			$conds[] = "ir_timestamp > '$date'";
		}
		if ( $lookup ) {
			$total = $dbr->selectField( $table, $vars, $conds, __METHOD__ );
			$conds[] = 'ir_rating > 0';
			$yes = $dbr->selectField( $table, $vars, $conds, __METHOD__ );
			$percent = 0;
			if ( $total > 0 ) {
				$percent = round( 100 * $yes / $total );
			}
			$html .= "<div>$sectionName: $percent% -  $total votes </div>";
		}

		return $html;

	}

	public static function getRatingHTML($pageId, $user) {
		$html = '';

		$data = self::getRatingData($pageId, "article");
		$starData = self::getRatingData($pageId, "star");
		$aggregateRating = self::getAggregateRatingData( $pageId, $data );
		$lastClear = self::getLastRatingClear($data);
		$clearLink = false;

		// special case where there are no ratings at all
		if (count($data) == 0 && count($starData) == 0) {
			$html .= '';
			$html .= "<div class='phr_data phr_accuracy'><h3>Ratings</h3>This page has never been rated.</div>";
			return $html;
		}

		$current = array_shift($data);
		if ($current->date < 0 && $current->total >= 6) {
			$html .= "<div class='phr_data phr_accuracy' rating={$current->percent}>";
		} else {
			$html .= "<div class='phr_data phr_accuracy'>";
		}

		$html .= '<h3>Ratings</h3>';

		if ($current->date <= 0) {
			$html .= "<div class='phr_current_rating'>{$current->percent}% - {$current->total} votes";
			$clearLink = true;
		} else {
			// special case where there are no current votes, only cleared votes
			$html .= "<div class='phr_current_rating'>no current votes";
			array_unshift($data, $current);
		}

		// put all the other ratings in a list
		if (count($data) > 0) {
			$html .= "<div class='phr_ratings_show_link'>(<a href='#'>show past ratings</a>)</div>";
			$html .= "</div>";
			$html .= "<ol class='phr_ratings_old'>";
			foreach ($data as $d) {
				$html .= "<li>";
				$html .= "{$d->percent}% - {$d->total} votes";
				if ($d->date > 0) {
					$timestamp = date("m-d-Y", $d->date);
					$html .= " cleared $timestamp";
				}
				$html .= "</li>";
			}
			$html .= "</ol>";
		} else {
			$html .= "</div>";
		}
		$html .= "<div class='phr_last_clear'>Last cleared: {$lastClear}</div>";

		if (count($starData) == 0) {
			$html .= "<div>This page has no star votes.</div>";
		} else {
			$starCurrent = array_shift($starData);
			//the if conditions below should be redundant and can be
			// simplified once we select one of the two star rating
			// percent calculations to keep.
			if ($starCurrent->total > 0) {
				$html .= "<div >Star votes: {$starCurrent->percent}% - {$starCurrent->total} votes </div>";
				$clearLink = true;
			} else {
				// special case where there are no current votes, only cleared votes
				$html .= "<div>No current star votes. </div>";
			}
		}

		$html .= "<div >Display Rating: {$aggregateRating->rating}% - {$aggregateRating->ratingCount} votes </div>";

		$html .= self::getSummarySectionRating( $pageId );
		$html .= self::getDisplayRatingHtml( $pageId );
		// if the user can, show the clearing ratings link
		if ($clearLink && Misc::isUserInGroups($user, array('staff', 'staff_widget'))) {
			$cl = Title::newFromText('ClearRatings', NS_SPECIAL);
			$link = Linker::linkKnown($cl, 'Clear ratings', array(), array("type"=>"article", "target"=>$pageId));
			$html .= "{$link}<br>";
		}
		$html .= "</div>";

		$html .= "<div class='phr_data'>";
		$html .= "<table class='helpfulness_reasons'>";
		$html .= "<tr><th>Rating Detail</td><th></th></tr>";
		$detail = self::getRatingsDetail($pageId);
		foreach ($detail as $key=>$val) {
			$key = self::getMessageFromKey($key);
			$html .= "<tr><td>$key</td><td>$val</td></tr>";
		}
		$html .= "</table>";
		$html .= "</div>";
		return $html;
	}


	function getMessageFromKey($key) {
		$responses = wfMessage("ratearticle_notrated_responses")->text();
		$responses = explode("\n", $responses);
		if (!isset($responses[$key-1])) {
			return "";
		}

		$message = $responses[$key-1];
		return wfMessage($message);
	}
	protected function getPageData($title) {
		global $wgUser;

		$html = "<h3>Page Helpfulness Data</h3>";
		if ( SpecialTechFeedback::isTitleInTechCategory($title) ) {
			$html = "<h3>Tech Page Up-To-Date Data</h3>";
		}

		$pageId = $title->getArticleId();
		$html .= self::getRatingHTML($pageId, $this->getUser());
		$data = self::getCombinedRatingFeedback($title);
		$html .= "<div class='phr_data'>";
		$html .= "<table class='helpfulness_reasons'>";
		$html .= "<tbody>";
		if ($data) {
			$html .= "<tr><th></th><th><em>Name</em> &amp; Rating Reasons</th></tr>{$data}";
		} else {
			$html .= "<tr><th></th><th>No rating reasons at this time</th><tr>{$data}";
		}
		$cl = SpecialPage::getTitleFor( 'ArticleHelpfulness');
		$html .= "</tbody>";
		$html .= "</table>";

		if (Misc::isUserInGroups($this->getUser(), array('staff', 'staff_widget'))) {
			$link = Linker::linkKnown($cl, 'view all', array(), array('item'=>$title));
			$html .= "<div class='phr_reasons_more'>{$link}</div>";
		}
		$html .= "</div>";
		return $html;
	}

	private function getLastRatingClear($data) {
		if (count($data) == 0) {
			return 'never';
		}

		$latest = $data[0]->date;

		if ($latest < 0) {
			if (count($data) > 1) {
				$latest = $data[1]->date;
				self::$lastClear = $latest;
				$latest = date("m-d-Y", $latest);
			} else {
				self::$lastClear = 0;
				$latest = 'never';
			}
		} else {
			self::$lastClear = $latest;
			$latest = date("m-d-Y", $latest);
		}
		return $latest;
	}

	public function execute($par) {
		$request = $this->getRequest();
		$out = $this->getOutput();

		$action = $request->getVal('action');
		if ($action == 'ajaxstats') {
			$out->setArticleBodyOnly(true);
			$target = $request->getVal('target');

			$type = $request->getVal('type');
			if ($type == "article") {
				$title = null;
				if (!empty($target)) {
					$title = Title::newFromURL($target);
					if ((!$title || !$title->exists()) && (int)$target > 0) {
						$title = Title::newFromId((int)$target);
					}
				}
				if ($title && $title->exists()) {
					$html = $this->getPageData($title);
					$result = array( 'body' => utf8_encode( $html ) );
					echo json_encode($result);
				}
			} elseif ($type == "sample") {
				// TODO - this
			}
		}
		if ($request->wasPosted() && $action = 'undolastclear') {
			$out->setArticleBodyOnly(true);
			$user = $this->getUser();
			$userGroups = $user->getGroups();
			if ( $user->isBlocked() || !in_array( 'staff', $userGroups ) ) {
				return;
			}

			$pageId = $request->getInt('pageId');
			// clear the last ratings
			$result = $this->undoLastClear( $pageId );

			echo json_encode($result);
		}
	}

	private function undoLastClear( $pageId ) {
		$result = array('restored'=>0);
		$dbw = wfGetDB(DB_MASTER);
		$table = "rating";
		$vars = array("rat_detail as D", "count(*) as C");
		$cond = array('rat_page'=>$pageId);
		$lastClearTime = $dbw->selectField( $table,
			"rat_deleted_when",
			array( 'rat_page'=>$pageId, 'rat_deleted_when > 0' ),
			__METHOD__,
			array( 'ORDER BY' => "rat_deleted_when DESC" ) );

		if ( $lastClearTime ) {
			$cond['rat_deleted_when'] = $lastClearTime;
			$values = array( 'rat_deleted_when'=> 0, 'rat_isdeleted' => 0 );
			$dbw->update( $table, $values, $cond, __METHOD__);
			$result['restored'] = $dbw->affectedRows();
		}
		return $result;
	}

	public static function getJSsnippet($type) {
?>
<style>
#phr_ratings_undo_clear {
	font-size:12px;
	position: absolute;
	right: 17px;
	display:none;
}
</style>
<script>
	if ($('#page_helpfulness_box').length) {

		$('#page_helpfulness_box').on("click", '.phr_ratings_show_link', function(e) {
			e.preventDefault();
			$('.phr_ratings_old').toggle();
			var $a = $(this).find('a');
			$a.html($a.text() == 'hide past ratings' ? 'show past ratings' : 'hide past ratings');
		});

		$(window).load(function() {
			if ($('.phr_accuracy .phr_ratings_old').length) {
				$('.phr_accuracy .phr_ratings_old li').first().append("<a href='' id='phr_ratings_undo_clear'>undo</a>");
				$('.phr_accuracy .phr_ratings_old li').first().append("<div id='dialog-confirm'></div>");
			}

		});

		$('#page_helpfulness_box').on("click", '#phr_ratings_undo_clear', function(e) {
			e.preventDefault();
			var r = confirm("Do you want to restore the most recently cleared ratings?");
			if (r == true) {
				postData = {'action':'undolastclear', 'pageId':wgArticleId};
				$.post('/Special:PageHelpfulness', postData, function(result) {
					// alternately we can display some better formatted message
					alert('restored ' + result['restored'] + ' ratings');
					location.reload();
				}, 'json');
			}
		});

		$('#page_helpfulness_box').html('Loading...');
		var type = "<?php echo $type ?>";
		var target = '';
		if (type == "sample") {
			target = wgSampleName;
		} else if (typeof wgPageHelpfulnessArticleId != 'undefined') {
			target = wgPageHelpfulnessArticleId;
		} else {
			target = wgTitle;
		}

		getData = {'action':'ajaxstats', 'target':target, 'type':type};
		$.get('/Special:PageHelpfulness', getData, function(data) {
				var result = (data && data['body']) ? data['body'] : 'Could not retrieve stats';
				$('#page_helpfulness_box').html(result);
				if (data && data['error']) {
					console.log(data['error']);
				}
				// set color of window
				if ($("#page_helpfulness_box").length) {
					var hue = $('.phr_accuracy').attr('rating');
					if (hue) {
						var scale = Math.abs(hue - 50) / 50;
						var maxSat = 100 - parseInt(scale * 63);
						var sat = maxSat - 21;
						var light = 60;
						var maxLight = 90 - parseInt(scale * 11);
						var light = maxLight - 21;
						var incr = 3;
						$("#page_helpfulness_box").css({"backgroundColor": "hsl("+hue+", " + sat + "%, "+light+"%)"});
						var interval = setInterval(function(){
							if (light < maxLight) {
								light = light + incr;
							}
							if (sat < maxSat) {
								sat = sat + incr;
							}
							if (sat >= maxSat && light >= maxLight) {
								clearInterval(interval);
							}
							$("#page_helpfulness_box").css({"backgroundColor": "hsl("+hue+", " + sat + "%, "+light+"%)"});
						}, 30);
					}

					// Inject Method Helpfulness widget
					if ($('#page_helpfulness_box').hasClass('smhw')) {
						var methodHelpfulnessDiv = $('<div/>', {
							id: 'method_helpfulness_box'
						});
						var firstDiv = $('#page_helpfulness_box>div:nth-child(2)');
						if (firstDiv.length) {
							firstDiv.after(methodHelpfulnessDiv);
						} else {
							$('#page_helpfulness_box').append(methodHelpfulnessDiv);
						}

						$('#page_helpfulness_box').removeClass('smhw');
					}
				}
			}, 'json');
	}
</script>
<?php
	}

}
