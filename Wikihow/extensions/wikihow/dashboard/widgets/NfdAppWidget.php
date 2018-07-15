<?

class NfdAppWidget extends DashboardWidget {

	public function __construct($name) {
		parent::__construct($name);
	}

	/*
	 * Returns the start link for this widget
	 */
	public function getStartLink($showArrow, $widgetStatus){
		if($widgetStatus == DashboardWidget::WIDGET_ENABLED)
			$link = "<a href='/Special:NFDGuardian' class='comdash-start'>Start";
		else if($widgetStatus == DashboardWidget::WIDGET_LOGIN)
			$link = "<a href='/Special:Userlogin?returnto=Special:NFDGuardian' class='comdash-login'>Login";
		else if($widgetStatus == DashboardWidget::WIDGET_DISABLED)
			$link = "<a href='/Become-a-New-Article-Booster-on-wikiHow' class='comdash-start'>Start";
		if($showArrow)
			$link .= " <img src='" . wfGetPad('/skins/owl/images/actionArrow.png') . "' alt=''>";
		$link .= "</a>";

		return $link;
	}

	public function getMWName(){
		return "nfd";
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the last contributor to this widget
	 */
	public function getLastContributor(&$dbr){
		$sql = "";
		$bots = WikihowUser::getBotIDs();

		if(sizeof($bots) > 0) {
			$sql = "log_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if($sql != "")
			$res = $dbr->select('logging', array('*'), array('log_type' => 'nfd', 'log_action' => 'vote', $sql), 'NfdAppWidget::getLastContributor', array("ORDER BY"=>"log_timestamp DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('logging', array('*'), array('log_type' => 'nfd', 'log_action' => 'vote'), 'NfdAppWidget::getLastContributor', array("ORDER BY"=>"log_timestamp DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);
		$res->free();

		return $this->populateUserObject($row->log_user, $row->log_timestamp);
	}

	/**
	 *
	 * Provides the content in the footer of the widget
	 * for the top contributor to this widget
	 */
	public function getTopContributor(&$dbr){
		$startdate = strtotime("7 days ago");
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$sql = "";
		$bots = WikihowUser::getBotIDs();

		if(sizeof($bots) > 0) {
			$sql = "log_user NOT IN (" . $dbr->makeList($bots) . ")";
		}

		if($sql != "")
			$res = $dbr->select('logging', array('*', 'count(*) as C', 'MAX(log_timestamp) as log_recent'), array('log_type' => 'nfd', 'log_action' => 'vote', 'log_timestamp >= "' . $starttimestamp . '"', $sql), 'NfdAppWidget::getTopContributor', array("GROUP BY" => 'log_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
		else
			$res = $dbr->select('logging', array('*', 'count(*) as C', 'MAX(log_timestamp) as log_recent'), array('log_type' => 'nfd', 'log_action' => 'vote', 'log_timestamp >= "' . $starttimestamp . '"'), 'NfdAppWidget::getTopContributor', array("GROUP BY" => 'log_user', "ORDER BY"=>"C DESC", "LIMIT"=>1));
		$row = $dbr->fetchObject($res);
		$res->free();

		return $this->populateUserObject($row->log_user, $row->log_recent);
	}

	/**
	 * Provides names of javascript files used by this widget.
	 */
	public function getJSFiles() {
		return array('NfdAppWidget.js');
	}

	/**
	 * Provides names of CSS files used by this widget.
	 */
	public function getCSSFiles() {
		return array('NfdAppWidget.css');
	}

	/*
	 * Returns the number of images left to be added.
	 */
	public function getCount(&$dbr){
		return NFDGuardian::getUnfinishedCount($dbr);
	}

	public function getUserCount(){
		$standings = new NFDStandingsIndividual();
		$data = $standings->fetchStats();
		return $data['week'];
	}

	public function getAverageCount(){
		$standings = new NFDStandingsGroup();
		return $standings->getStandingByIndex(self::GLOBAL_WIDGET_MEDIAN);
	}

	/**
	 *
	 * Gets data from the Leaderboard class for this widget
	 */
	public function getLeaderboardData(&$dbr, $starttimestamp){

		$data = LeaderboardStats::getNfdsReviewed($starttimestamp);
		arsort($data);

		return $data;

	}

	public function getLeaderboardTitle(){
		return "<a href='/Special:Leaderboard/nfd?period=7'>" . $this->getTitle() . "</a>";
	}

	public function isAllowed($isLoggedIn, $userId=0){
		if(!$isLoggedIn)
			return false;
		else if($isLoggedIn && $userId == 0)
			return false;
		else{
			$user = new User();
			$user->setID($userId);
			return in_array( 'newarticlepatrol', $user->getRights()) || $user->isSysop();
		}
	}

}
