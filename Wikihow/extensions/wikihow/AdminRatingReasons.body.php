<?php

class AdminRemoveRatingReason extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('AdminRemoveRatingReason');
	}

	public function userCanExecute( User $user ) {
		$userGroups = $user->getGroups();

		if ($user->isBlocked()) {
			return false;
		}

		$allowedGroups = array("staff", "staff_widget");

		foreach($allowedGroups as $group) {
			if (in_array($group, $userGroups)) {
				return true;
			}
		}
		return false;
	}

    public function execute($par) {
		$out = $this->getOutput();

		$ratr_item = isset( $par ) ? $par : $this->getRequest()->getVal('target');
		$ratr_id = $this->getRequest()->getVal('id');

		$dbw = wfGetDB( DB_MASTER );

		if (!$ratr_id) {
			$dbw->delete("rating_reason", array("ratr_item" => $ratr_item));
			$out->addHTML("<h3>All Rating Reasons for {$ratr_item} have been deleted.</h3><br><hr size='1'><br>");
		} else {
			$dbw->delete("rating_reason", array("ratr_id" => $ratr_id));
			$out->addHTML("<h3>Rating Reason for {$ratr_item} has been deleted.</h3><br><hr size='1'><br>");
		}

		$arr = Title::makeTitle(NS_SPECIAL, "AdminRatingReasons");

		$orig = Title::newFromText("sample/".$ratr_item);

		$out->addHTML("Return to ". Linker::link($arr, "AdminRatingReasons"."<br>"));
		$out->addHTML("Go to ".Linker::linkKnown($orig, "{$ratr_item}"));
	}
}


abstract class Helpfulness extends QueryPage {
	protected $mType;
	protected $mRatingTableName;
	protected $mRatingTablePrefix;
	protected $mShowAccuracyInline = false;
	protected $mAllowedGroups = array("staff", "staff_widget");

	public function __construct($name = '') {
		$this->ratingsCache = array();
		parent::__construct($name);
	}

	public function isListed() {
		return false;
	}

	public function userCanExecute( User $user ) {
		$userGroups = $user->getGroups();

		if ($user->isBlocked()) {
			return false;
		}

		foreach($this->mAllowedGroups as $group) {
			if (in_array($group, $userGroups)) {
				return true;
			}
		}
		return false;
	}

	public function execute( $par ) {
        global $wgHooks;

        $wgHooks['ShowSideBar'][] = array($this, 'removeSideBarCallback');

		$action = $this->getRequest()->getVal('action');
		if ($action == 'csv') {
			$this->getCSV();
			return;
		}

		parent::execute($par);
	}

    public static function removeSideBarCallback(&$showSideBar) {
        $showSideBar = false;
        return true;
    }

	protected function getHeaderTitle() {
		return "Helpfulness Responses";
	}

	private function getPageJS() {
		$html = <<<EOHTML
		<script>
		$('.mw-spcontent').on("click", '.arr_ratings_show', function(e) {
			e.preventDefault();
			$('.phr_data').toggle();
			$(this).html($(this).text() == 'Show Ratings' ? 'Hide Ratings' : 'Show Ratings');
		});

		</script>
EOHTML;
		return $html;
	}

	function getPageHeader() {
		$html = HtmlSnips::makeUrlTag('/extensions/wikihow/Rating/adminratingreasons.css');
		$html .= $this->getPageJS();
		$item = $this->getRequest()->getVal('item');
		$csvLink = $this->getCSVLink($item);
		$headerTitle = $this->getHeaderTitle();

		if ($item) {
			$title = Title::newFromText($item);
			$showAll = Linker::link($this->getTitle(), 'All Results');
			$showRatings = "<a class='arr_ratings_show' href='#'>Show Ratings</a>";
			$html .= "<h2>$headerTitle for ".Linker::link($title)."</h2>";
			$html .= "<p>(".$showAll." | ".$csvLink." | ".$showRatings.")</p>";
			$html .= $this->getRatingHTML($item);
		} else {
			$html .= "<h2>$headerTitle</h2>";
			$html .= "<p>(".$csvLink.")</p><br>";
		}

		// total helpfulness responses
		$totals = $this->getTotalReasonsHTML($item);
		$html .= $totals;

		return $html;
	}

	private function getTotalReasonsHTML($item) {
		$dbr = wfGetDB(DB_REPLICA);
		$type = $this->mType;
		$where = array( 'ratr_type' => $type );

		$result = array();
		if ($item) {
			$where['ratr_item'] = $item;
		}

		$total = $dbr->selectField('rating_reason', 'count(*)', $where, __METHOD__);
		$where['ratr_rating'] = 0;
		$no = $dbr->selectField('rating_reason', 'count(*)', $where, __METHOD__);
		$yes = $total - $no;

		$html = "<div class='arr_totals'>";
		$html .= "There are $total responses.  $yes chose 'yes', and $no chose 'no'.</div><br>";
		return $html;
	}

	private function getCSV() {
		global $wgCanonicalServer;

		$req = $this->getRequest();
		$req->response()->header('Content-type: application/force-download');
		$req->response()->header('Content-disposition: attachment; filename="data.csv"');

		// NOTE: if we used setArticleBodyOnly(true) instead here, Content-Type would
		// automatically change to text/html. Not what we want.
		$this->getOutput()->disable();

		$item = $req->getVal('item');

		$lines = array();

		$dbr = wfGetDB(DB_REPLICA);

		$vars = array('ratr_item as title',
			'ratr_rating as rating',
			'ratr_text as reason',
			'ratr_user_text as user',
			'ratr_name as name',
			'ratr_email as email',
			'ratr_type as type',
			'ratr_detail as detail',
			'ratr_timestamp as date');

		$type = $this->mType;
		$where = array( 'ratr_type' => $type );
		if ($item) {
			$where['ratr_item'] = $item;
		}

		$options = array("ORDER BY"=>"ratr_timestamp DESC", "LIMIT" => 50000);
		$res = $dbr->select('rating_reason', $vars, $where, __METHOD__, $options);

		foreach ($res as $row) {
			$rating = $row->rating == 0 ? 'no' : 'yes';
			$reason = '"' . str_replace('"', '""', $row->reason) . '"';

			$title = Title::newFromText($row->title);
			if ( !$title || !$title->exists() ) {
				continue;
			}

			$link = str_replace(" ", "-", "$wgCanonicalServer/".$title->getText());

			$acc = $this->getRatingAccuracy($row->title);
			$accPercent = $acc->percentage . "%";
			$accVotes = $acc->total;

			$detail = "";
			if ( $row->detail ) {
				$detail = wfMessage( $row->detail )->text();
				$detail = '"' . str_replace('"', '""', $detail) . '"';
			}

			$line = array($link, $rating, $reason, $row->user, $row->name, $row->email, $row->date, $row->type, $accPercent, $accVotes, $detail);
			$lines[] = implode(",", $line);
		}

		// print must be used if disabling OutputPage
		print "title,rating,reason,user,name,email,date,type,accuracy,accuracy votes,detail\n";
		print implode("\n", $lines);
	}

	private function getCSVLink($item) {
		$queryParams = array("action"=>"csv");

		if ($item) {
			$queryParams['item'] = $item;
		}

		$html = Linker::linkKnown($this->getTitle(), "download .csv [last 50k entries only]", array(), $queryParams);
		return $html;

	}

	function isSyndicated() {
		return false;
	}

	function getQueryInfo() {
		$cond = array();
		$type = $this->mType;
		$cond["ratr_type"] = $type;

		$item = $this->getRequest()->getVal("item");
		if ($item) {
			$cond["ratr_item"] = $item;
		}

		return array(
			'tables' => array( 'rating_reason' ),
			'fields' => array( 'namespace' => 0,
					'title' => 'ratr_item',
					'value' => 'ratr_id',
					'type' => 'ratr_type',
					'ratr_text' => 'ratr_text',
					'ratr_timestamp' => 'ratr_timestamp',
					'name' => 'ratr_name',
					'email' => 'ratr_email',
					'ratr_detail' => 'ratr_detail',
					'ratr_rating' => 'ratr_rating' ),
			'conds' => $cond,
			'options' => array()
		);
	}

	function getOrderFields() {
		return array("ratr_timestamp");
	}

	private function getAccuracyText($ratingsData) {
		if ($this->mShowAccuracyInline) {
			return "{$ratingsData->percentage}% of {$ratingsData->total} votes";
		}
	}

	protected function getRatingAccuracyKey($titleText) {
		$title = $this->getResultTitle( urldecode( $titleText ) );
		return $title->getArticleId();
	}

	protected function getRatingAccuracy($titleText) {
		$key = $this->getRatingAccuracyKey($titleText);

		$rd = $this->ratingsCache[$key];

		if (!$rd) {
			$dbr = wfGetDB(DB_REPLICA);
			$rd = PageStats::getRatingData($key, $this->mRatingTableName, $this->mRatingTablePrefix, $dbr);
			$this->ratingsCache[$key] = $rd;
		}

		return $rd;
	}

	protected function getRatingHTML($titleText) {
		$key = $this->getRatingAccuracyKey($titleText);

		$rd = $this->ratingsCache[$key];

		if (!$rd) {
			$dbr = wfGetDB(DB_REPLICA);
			$rd = PageHelpfulness::getRatingHTML($key, $this->getUser());
			$this->ratingsCache[$key] = $rd;
		}
		return $rd;
	}

	function isExpensive() {
		return false;
	}

	protected function getResultTitle($titleText) {
		return Title::newFromText($titleText);
	}

	public function formatResult($skin, $result) {
		$title = $this->getResultTitle($result->title);
		$item = $this->getRequest()->getVal("item");
		if (!$item) {
			$titleLink = Linker::linkKnown($title, $result->title );
			$filterThis = Linker::link($this->getTitle(), 'filter', array(), array('item'=>$result->title));
			$accText = $this->getAccuracyText($this->getRatingAccuracy($result->title));
		}

		$timestamp = $result->ratr_timestamp;
		$time = strtotime($timestamp);
		$timestamp = wfTimestamp(TS_MW, $timestamp);
		$timestamp = date("m-d-Y", $time);

		$rating = $result->ratr_rating == 0 ? "No" : "Yes";

		$thumbClass = "arr_thumb arr_thumb_no";
		if ($result->ratr_rating > 0) {
			$thumbClass = "arr_thumb arr_thumb_yes";
		}

		$html = "<div class='arr_data'><div class='$thumbClass'></div></div>";
		$html.= "<div class='arr_rating arr_data'>rating: $result->ratr_rating<br></div>";
		if ($titleLink) {
			$html.= "<div class='arr_title arr_data'>$titleLink</div>";
		}
		$html.= "<div class='arr_result arr_data'>$result->ratr_text</div>";
		$html.= "<div class='arr_timestamp arr_data'>$timestamp</div>";
		$html.= "<div class='arr_name arr_data'>$result->name</div>";
		$html.= "<div class='arr_email arr_data'>$result->email</div>";
		$detail = $result->ratr_detail;
		if ($detail) {
			$detail = wfMessage($detail);
		}
		$html.= "<div class='arr_detail arr_data'>$detail</div>";
		if ($accText) {
			$html.= "<div class='arr_data'>$accText</div>";
		}
		if ($filterThis) {
			$html.= "<div class='arr_filter arr_data'>($filterThis)</div>";
		}
		return $html;
	}
}

class ArticleHelpfulness extends Helpfulness {
	public function __construct($name = '') {
		$this->mType = array( "article", "itemrating" );
		$this->mRatingTableName = "rating";
		$this->mRatingTablePrefix = "rat";
		parent::__construct('ArticleHelpfulness');
	}
}

class AdminRatingReasons extends Helpfulness {
	public function __construct($name = '') {
		$this->mType = "sample";
		$this->mRatingTableName = "ratesample";
		$this->mRatingTablePrefix = "rats";
		$this->mShowAccuracyInline = true;
		parent::__construct('AdminRatingReasons');
	}

	protected function getRatingHTML($titleText) {
		$acc = $this->getRatingAccuracy($titleText);
		$accText .= "{$acc->percentage}% of {$acc->total} votes";
		$html = "<div class='phr_data'>Accuracy: ".$accText."</div><br>";
		return $html;
	}

	protected function getHeaderTitle() {
		return "Sample Rating Reasons";
	}

	protected function getResultTitle($titleText) {
		return Title::newFromText('sample/'.$titleText);
	}

	protected function getRatingAccuracyKey($titleText) {
		return $titleText;
	}
}
