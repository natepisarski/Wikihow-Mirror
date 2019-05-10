<?php

// TODO: this whole class could be cleaned up to be use classes properly
// something akin to SpecialPage and QueryPage

class Leaderboard extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Leaderboard' );
	}

	private function getTabs($section, $tab) {
			$tabs = '';
			if ($section == 'Writing') {
				$tab1 = $tab=='articles_written' ? "class='on'" : "";
				$tab2 = $tab=='risingstars_received' ? "class='on'" : "";
				$tab3 = $tab=='requested_topics' ? "class='on'" : "class='tab_129'";
				$tab4 = $tab=='spellchecked' ? "class='on'" : "";

				$tabs = " <ul class='sub_tabs'> <li><a href='/Special:Leaderboard/articles_written' $tab1 >Articles Written</a></li> <li><a href='/Special:Leaderboard/risingstars_received' $tab2 >Rising Stars</a></li> <li><a href='/Special:Leaderboard/requested_topics' $tab3>Requests</a></li> <li><a href='/Special:Leaderboard/spellchecked' $tab4 >Spell Checked</a></li></ul>";
				return $tabs;

			} elseif ($section == "RCNAB") {
				$tab1 = $tab=='articles_nabed' ? "class='on'" : "";
				$tab2 = $tab=='risingstars_nabed' ? "class='on'" : "";
				$tab3 = $tab=='rc_edits' ? "class='on'" : "";
				$tab4 = $tab=='rc_quick_edits' ? "class='on'" : "";
				$tab5 = $tab=='qc' ? "class='on'" : "";

				$tabs = " <ul class='sub_tabs'> <li><a href='/Special:Leaderboard/articles_nabed' $tab1 >Articles NABed</a></li> <li><a href='/Special:Leaderboard/risingstars_nabed' $tab2 >RS NABed</a></li><li><a href='/Special:Leaderboard/rc_edits' $tab3 >Edits Patrolled</a></li> <li><a href='/Special:Leaderboard/rc_quick_edits' $tab4 >RC Quick Edits</a></li> <li><a href='/Special:Leaderboard/qc' $tab5 >Top Guardians</a></li> </ul>";
				return $tabs;
			} elseif ($section == "Other") {
				$tab1 = $tab=='total_edits' ? "class='on'" : "";
				$tab2 = $tab=='thumbs_up' ? "class='on'" : "";
				$tab3 = $tab=='articles_categorized' ? "class='on'" : "";
				$tab4 = $tab=='welcomewagon_indiv1' ? "class='on'" : "";
				$tab5 = $tab=='tiptool_indiv1' ? "class='on'" :"";
				$tab6 = $tab=='nfd' ? "class='on'" : "";
				$tab7 = $tab=='CategoryGuardian' ? "class='on'" : "";
				$tab8 = $tab=='questionssorted' ? "class='on'" : "";
				$tab9 = $tab=='techfeedbackreviewed' ? "class='on'" : "";
				$tab10 = $tab=='techarticletested' ? "class='on'" : "";
				$tab11 = $tab=='duplicatetitles' ? "class='on'" : "";
				$tab12 = $tab=='articlefeedbackreviewed' ? "class='on'" : "";
				$tab13 = $tab=='fixflaggedanswers' ? "class='on'" : "";
				$tab14 = $tab=='qap' ? "class='on'" : "";
				$tab15 = $tab=='topicstagged' ? "class='on'" : "";

				$tabs = " <ul class='sub_tabs'> <li><a href='/Special:Leaderboard/total_edits' $tab1 >All Edits</a></li> <li><a href='/Special:Leaderboard/thumbs_up' $tab2 >Thumbs Up</a></li> <li><a href='/Special:Leaderboard/articles_categorized' $tab3 >Categorization</a></li><li><a href='/Special:Leaderboard/welcomewagon_indiv1' $tab4 >Welcome Wagon</a></li> <li><a href='/Special:Leaderboard/tiptool_indiv1' $tab5>Tips Patrol</a> </ul> <ul class='sub_tabs'></li><li><a href='/Special:Leaderboard/nfd' $tab6 >NFD</a></li> <li><a href='/Special:Leaderboard/CategoryGuardian' $tab7 >Category Guardian</a></li> <li><a href='/Special:Leaderboard/questionssorted' $tab8 >Approve Questions</a></li> <li><a href='/Special:Leaderboard/techfeedbackreviewed' $tab9 >Review Tech Feedback</a></li><li><a href='/Special:Leaderboard/techarticletested' $tab10 >Test Tech Articles</a></li><li><a href='/Special:Leaderboard/duplicatetitles' $tab11 >Find Duplicate Titles</a></li><li><a href='/Special:Leaderboard/articlefeedbackreviewed' $tab12 >Review Article Feedback</a></li><li><a href='/Special:Leaderboard/fixflaggedanswers' $tab13 >Fix Flagged Answers</a></li><li><a href='/Special:Leaderboard/qap' $tab14 >Q&A Patrol</a></li><li><a href='/Special:Leaderboard/topicstagged' $tab15 >Topic Tagging</a></li></ul>";
				return $tabs;
			} elseif ($section == "Greenhouse") {
				$tab1 = $tab=='repair_format' ? "class='on'" : "";
				// $tab2 = $tab=='repair_stub' ? "class='on'" : "";
				$tab3 = $tab=='repair_cleanup' ? "class='on'" : "";
				$tab4 = $tab=='repair_copyedit' ? "class='on'" : "";
				$tab5 = $tab=='repair_topic' ? "class='on'" : "";

				$tabs .= "<ul class='sub_tabs'><li><a href='/Special:Leaderboard/repair_format' $tab1 >Formatting</a></li> <li><a href='/Special:Leaderboard/repair_cleanup' $tab3 >Cleanup</a></li> <li><a href='/Special:Leaderboard/repair_copyedit' $tab4 >Copyedit</a></li> <li><a href='/Special:Leaderboard/repair_topic' $tab5 >Topic</a></li></ul>";
				return $tabs;
			} elseif ($section == "Imagevideo") {
				$tab1 = $tab=='images_added' ? "class='on'" : "";
				$tab2 = $tab=='videos_reviewed' ? "class='on'" : "";
				$tab3 = $tab=='ucitool_indiv1' ? "class='on'" :"";

				$tabs = " <ul class='sub_tabs'><li><a href='/Special:Leaderboard/images_added' $tab1 >Images Added</a></li> <li><a href='/Special:Leaderboard/videos_reviewed' $tab2 >Videos Reviewed</a></li> <li><a href='/Special:Leaderboard/ucitool_indiv1' $tab3 >Pictures Patrolled</a></li> </ul>";
				return $tabs;
			}
		return '';
	}

	private function showArticles($page, $starttimestamp, $user) {
		$data = array();
		switch ($page) {
			case 'articles_written':
				$data = LeaderboardStats::getArticlesWritten($starttimestamp, $user, true);
				break;
			case 'risingstars_received':
				$data = LeaderboardStats::getRisingStar($starttimestamp, $user, true);
				break;
			case 'requested_topics':
				$data = LeaderboardStats::getRequestedTopics($starttimestamp, $user, true);
				break;
			case 'articles_nabed':
				$data = LeaderboardStats::getArticlesNABed($starttimestamp, $user, true);
				break;
			case 'risingstars_nabed':
				$data = LeaderboardStats::getRisingStarsNABed($starttimestamp, $user, true);
				break;
			case 'rc_edits':
				$data = LeaderboardStats::getRCEdits($starttimestamp, $user, true);
				break;
			case 'rc_quick_edits':
				$data = LeaderboardStats::getRCQuickEdits($starttimestamp, $user, true);
				break;
			case 'qc':
				$data = LeaderboardStats::getRCQuickEdits($starttimestamp, $user, true);
				break;
			case 'total_edits':
				$data = LeaderboardStats::getTotalEdits($starttimestamp, $user, true);
				break;
			case 'tiptool_indiv1':
				$data = LeaderboardStats::getTipsAdded($starttimestamp, $user, true);
				break;
			case 'CategoryGuardian':
				$data = LeaderboardStats::getCategoryguarded($starttimestamp, $user, true);
				break;
			case 'questionssorted':
				$data = LeaderboardStats::getQuestionsSorted($starttimestamp, $user, true);
				break;
			case 'articles_categorized':
				$data = LeaderboardStats::getArticlesCategorized($starttimestamp, $user, true);
				break;
			case 'images_added':
				$data = LeaderboardStats::getImagesAdded($starttimestamp, $user, true);
				break;
			case 'ucitool_indiv1':
				$data = LeaderboardStats::getUCIAdded($starttimestamp, $user, true);
				break;
			case 'videos_reviewed':
				$data = LeaderboardStats::getVideosReviewed($starttimestamp, $user, true);
				break;
			case 'repair_format':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'format', $user, true);
				break;
			case 'repair_topic':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'topic', $user, true);
				break;
			case 'repair_cleanup':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'cleanup', $user, true);
				break;
			case 'repair_copyedit':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'copyedit', $user, true);
				break;
			case 'nfd':
				$data = LeaderboardStats::getNfdsReviewed($starttimestamp, $user, true);
				break;
			case 'spellchecked':
				$data = LeaderboardStats::getSpellchecked($starttimestamp, $user, true);
				break;
			case 'techfeedbackreviewed':
				$data = LeaderboardStats::getTechFeedbackReviewed($starttimestamp, $user, true);
				break;
			case 'techarticletested':
				$data = LeaderboardStats::getTechArticleTested($starttimestamp, $user, true);
				break;
			case 'welcomewagon_indiv1':
				$data = LeaderboardStats::getWelcomeWagon($starttimestamp, $user, true);
				break;
			case 'duplicatetitles':
				$data = LeaderboardStats::getDuplicateTitlesReviewed($starttimestamp, $user, true);
				break;
			case 'articlefeedbackreviewed':
				$data = LeaderboardStats::getArticleFeedbackReviewed($starttimestamp, $user, true);
				break;
			case 'fixflaggedanswers':
				$data = LeaderboardStats::getFixFlaggedAnswers($starttimestamp, $user, true);
				break;
			case 'topicstagged':
				$data = LeaderboardStats::getTopicsTagged($starttimestamp, $user, true);
				break;
			default:
				return;
		}

		$out->addHTML("<ul>\n");
		foreach ($data as $key => $value) {
			$out->addHTML("<li><a href='/$key' onclick='window.location=\"/$key\";'>$value</a></li>\n");
		}
		$out->addHTML("</ul>\n");
	}

	private function showArticlesPage($page, $period, $starttimestamp, $user) {
		$out = $this->getOutput();

		$out->addHTML("
			<script>
				var lb_page = '$target';
				var lb_period = '$period';
			</script>\n");

		$data = array();
		$subtitle = '';
		switch ($page) {
			case 'articles_written':
				$data = LeaderboardStats::getArticlesWritten($starttimestamp, $user, true);
				$subtitle = 'Articles Written by ';
				break;
			case 'risingstars_received':
				$data = LeaderboardStats::getRisingStar($starttimestamp, $user, true);
				$subtitle = 'Articles that received a Risingstar by ';
				break;
			case 'requested_topics':
				$data = LeaderboardStats::getRequestedTopics($starttimestamp, $user, true);
				$subtitle = 'Articles from requested topics by ';
				break;
			case 'articles_nabed':
				$data = LeaderboardStats::getArticlesNABed($starttimestamp, $user, true);
				$subtitle = 'New Articles Boosted by ';
				break;
			case 'risingstars_nabed':
				$data = LeaderboardStats::getRisingStarsNABed($starttimestamp, $user, true);
				$subtitle = 'New Articles nominated for Risingstar by ';
				break;
			case 'rc_edits':
				$data = LeaderboardStats::getRCEdits($starttimestamp, $user, true);
				$subtitle = 'Articles Patrolled - ';
				break;
			case 'rc_quick_edits':
				$data = LeaderboardStats::getRCQuickEdits($starttimestamp, $user, true);
				$subtitle = 'Quick Edits made while patrolling - ';
				break;
			case 'total_edits':
				$data = LeaderboardStats::getTotalEdits($starttimestamp, $user, true);
				$subtitle = 'Total Edits - ';
				break;
			case 'articles_categorized':
				$data = LeaderboardStats::getArticlesCategorized($starttimestamp, $user, true);
				$subtitle = 'Articles Categorized - ';
				break;
			case 'images_added':
				$data = LeaderboardStats::getImagesAdded($starttimestamp, $user, true);
				$subtitle = 'Images Added - ';
				break;
			case 'ucitool_indiv1':
				$data = LeaderboardStats::getUCIAdded($starttimestamp, $user, true);
				$subtitle = 'Pictures Patrolled - ';
				break;
			case 'videos_reviewed':
				$data = LeaderboardStats::getVideosReviewed($starttimestamp, $user, true);
				$subtitle = 'Videos Added - ';
				break;
			case 'repair_format':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'format', $user, true);
				$subtitle = 'Formats Fixed - ';
				break;
			case 'repair_topic':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'topic', $user, true);
				$subtitle = 'Fixed by Topic - ';
				break;
			case 'repair_copyedit':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'copyedit', $user, true);
				$subtitle = 'Copyedit Fixed - ';
				break;
			case 'repair_cleanup':
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp, 'cleanup', $user, true);
				$subtitle = 'Cleanup Fixed - ';
				break;
			case 'nfd':
				$data = LeaderboardStats::getNfdsReviewed($starttimestamp, $user, true);
				$subtitle = 'NFDs Reviewed - ';
				break;
			case 'spellchecked':
				$data = LeaderboardStats::getSpellchecked($starttimestamp, $user, true);
				$subtitle = 'Articles Spell Checked - ';
				break;
			case 'welcomewagon_indiv1':
				$data = LeaderboardStats::getWelcomeWagon($starttimestamp, $user, true);
				$subtitle = 'Welcome Wagon Messages Sent - ';
				break;
			default:
				return;
		}

		switch ($period) {
			case 7:
				$subtitle .= $user ." in the last week";
				break;
			case 31:
				$subtitle .= $user ." in the last month";
				break;
			default:
				$subtitle .= $user ." in the last day";
				break;
		}

		$u = User::newFromName( $user );
		if (isset($u)) {
			$u->load();
		}

		$userlink = Linker::link($u->getUserPage(), $u->getName()) ;
		$regdate = "Jan 1, 1970";
		$userReg = $u->getRegistration();
		if ($userReg) {
			$regdate = gmdate('M d, Y',wfTimestamp(TS_UNIX, $userReg));
		} else {
			$regdate = gmdate('M d, Y',wfTimestamp(TS_UNIX, '20060725043938'));
		}
		$contributions = number_format(WikihowUser::getAuthorStats($u->getName()), 0, "", ",");

		$contribsPage = SpecialPage::getTitleFor( 'Contributions', $user );
		$contriblink = Linker::link( $contribsPage , 'contrib' );
		$talkpagelink = Linker::link($u->getTalkPage(), 'talk');
		$otherlinks = "($contriblink | $talkpagelink)";


		$out->addHTML("\n<div id='Leaderboard'>\n");
		$out->addHTML("<br />$subtitle<br/>" .
			wfMessage('leaderboard_articlespage_msg', $userlink, $regdate, $contributions, $otherlinks)->text() ."<br/>\n");

		$out->addHTML("<table class='leader_table' style='width:475px; margin:0 auto;'>" );

		$index = 1;
		$out->addHTML("<tr>
			<td class='leader_title'>Article</td>
			</tr>");
		$index = 1;
		foreach ($data as $key => $value) {
			$class = "";
			if ($index % 2 == 1)
				$class = 'class="odd"';
			$t = Title::newFromText( $value );
			if ($page == 'images_added_NOT_SETUP_YET') {
				// in the future we can display the actual image added on this page.
				$out->addHTML("<tr $class><td style='text-align:left;'><img src='/$key' /><a href='/$key' >$value</a></td</tr>\n");
			} elseif ($page == 'welcomewagon_indiv1') {
				$out->addHTML("<tr><td class='leader_image'><a href='/$key' >$value</a></td</tr>\n");
			} else {
				$out->addHTML("<tr><td class='leader_image'><a href='/$key' >$value</a> (<a href='".$t->getLocalURL( 'action=history' )."' >history</a>)</td</tr>\n");
			}
			$index++;
		}
		$out->addHTML("</table></center>");

		$out->addHTML("<br /><a href='/Special:Leaderboard/$page?period=$period' >Back</a></div>\n");
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		$target = !empty($par) ? $par : $req->getVal('target');
		$target = filter_var( $target, FILTER_SANITIZE_STRING );
		$action = $req->getVal( 'action' );

		if (!$target) {
			$out->redirect( "/Special:Leaderboard/articles_written");
			return;
		}

		$out->setPageTitle( wfMessage('leaderboard_title') );
		$out->setRobotPolicy('noindex,nofollow');

		$wgHooks["pageTabs"][] = "wfLeaderboardTabs";

		$dbr = wfGetDB(DB_REPLICA);

		$me = Title::makeTitle(NS_SPECIAL, "Leaderboard");

		$period = $req->getVal('period');
		$period = filter_var( $period, FILTER_SANITIZE_NUMBER_INT );


		$startdate = '000000';
		if ($period == 31) {
			$startdate = strtotime('31 days ago');
			$period31selected = 'selected="selected"';
		} elseif ($period == 7) {
			$startdate = strtotime('7 days ago');
			$period7selected = 'selected="selected"';
		} else {
			$period = 24;
			$startdate = strtotime('24 hours ago');
			$period24selected = 'selected="selected"';
		}
		$starttimestamp = date('YmdG',$startdate) . floor(date('i',$startdate)/10) . '00000';

		$out->addModules('ext.wikihow.leaderboard');

		if ($action == 'articles') {
			$out->setArticleBodyOnly(true);
			$this->showArticles( $target, $starttimestamp, $req->getVal( 'lb_name' ) );
			return;
		}

		$noLearnLinks = array('CategoryGuardian','tiptool_indiv1', 'ucitool_indiv1', 'nfd','questionssorted');
		$data = array();
		// WHICH LB TO SHOW
		switch( $target ) {
			case 'CategoryGuardian':
				$section = 'Other';
				$learnlink = '/wikiHow:LB-Category-Guardian'; //doesn't exist
				$columnHeader = 'Categories Guarded';
				$data = LeaderboardStats::getCategoryguarded($starttimestamp);
				break;
			case 'questionssorted':
				$section = 'Other';
				$learnlink = '/wikiHow:LB-Sort-Questions'; //doesn't exist
				$columnHeader = 'Questions Approved';
				$data = LeaderboardStats::getQuestionsSorted($starttimestamp);
				break;
			case 'tiptool_indiv1':
				$section ='Other';
				$learnlink = '/wikiHow:LB-Tips-Patrol'; //doesn't exist
				$columnHeader = 'Tips Patrol';
				$data = LeaderboardStats::getTipsAdded($starttimestamp);
				break;
			case 'total_edits':
				$section = 'Other';
				$learnlink = '/wikiHow:LB-Total-Edits';
				$columnHeader = 'TotalEdits';
				$data = LeaderboardStats::getTotalEdits($starttimestamp);
				break;
			case 'articles_categorized':
				$section = 'Other';
				$learnlink = '/wikiHow:LB-Articles-Categorized';
				$columnHeader = 'Articles Categorized';
				$data = LeaderboardStats::getArticlesCategorized($starttimestamp);
				break;
			case 'images_added':
				$section = 'Imagevideo';
				$learnlink = '/wikiHow:LB-Images-Added';
				$columnHeader = 'Images Added';
				$data = LeaderboardStats::getImagesAdded($starttimestamp);
				break;
			case 'ucitool_indiv1':
				$section = 'Imagevideo';
				$learnlink = '/wikiHow:LB-Picture_Patrol'; //doesn't exist
				$columnHeader = 'Pictures Patrolled';
				$data = LeaderboardStats::getUCIAdded($starttimestamp);
				break;
			case 'videos_reviewed':
				$section = 'Imagevideo';
				$learnlink = '/wikiHow:LB-Videos-Reviewed';
				$columnHeader = 'Videos Reviewed';
				$data = LeaderboardStats::getVideosReviewed($starttimestamp);
				break;
			case 'articles_nabed':
				$section = 'RCNAB';
				$learnlink = '/wikiHow:LB-Articles-NABed';
				$columnHeader = 'Articles NABed';
				$data = LeaderboardStats::getArticlesNABed($starttimestamp);
				break;
			case 'risingstars_nabed':
				$section = 'RCNAB';
				$learnlink = '/wikiHow:LB-Rising-Stars-NABed';
				$columnHeader = 'Rising Stars NABed';
				$data = LeaderboardStats::getRisingStarsNABed($starttimestamp);
				break;
			case 'rc_edits':
				$section = 'RCNAB';
				$learnlink = '/wikiHow:LB-RC-Edits';
				$columnHeader = 'RC Edits';
				$data = LeaderboardStats::getRCEdits($starttimestamp);
				break;
			case 'rc_quick_edits':
				$section = 'RCNAB';
				$learnlink = '/wikiHow:LB-RC-Quick-Edits';
				$columnHeader = 'RC Quick Edits';
				$data = LeaderboardStats::getRCQuickEdits($starttimestamp);
				break;
			case 'qc':
				$section = 'RCNAB';
				$learnlink = '/wikiHow:Top-Guardians';
				$columnHeader = 'Top Guardians';
				$data = LeaderboardStats::getQCPatrols($starttimestamp);
				break;
			case 'requested_topics':
				$section = 'Writing';
				$learnlink = '/wikiHow:LB-Requested-Topics';
				$columnHeader = 'Requested Topics';
				$data = LeaderboardStats::getRequestedTopics($starttimestamp);
				break;
			case 'risingstars_received':
				$section = 'Writing';
				$learnlink = '/wikiHow:Rising-Star';
				$columnHeader = 'Rising Stars Received';
				$data = LeaderboardStats::getRisingStar($starttimestamp);
				break;
			case 'articles_written':
				$section = 'Writing';
				$learnlink = '/wikiHow:LB-Articles-Written';
				$columnHeader = 'Articles Written';
				$data = LeaderboardStats::getArticlesWritten($starttimestamp);
				break;
			case 'repair_format':
				$section = 'Greenhouse';
				$learnlink = '/wikiHow:LB-Repair_Format';
				$columnHeader = 'Formats Fixed';
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp,'format');
				break;
			case 'repair_topic':
				$section = 'Greenhouse';
				$learnlink = '/wikiHow:LB-Repair_Topic';
				$columnHeader = 'Cultivated by Topic';
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp,'topic');
				break;
			case 'repair_cleanup':
				$section = 'Greenhouse';
				$learnlink = '/wikiHow:LB-Repair_Cleanup';
				$columnHeader = 'Cleanups Fixed';
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp,'cleanup');
				break;
			case 'repair_copyedit':
				$section = 'Greenhouse';
				$learnlink = '/wikiHow:LB-Repair_Copyedit';
				$columnHeader = 'Copyedits Fixed';
				$data = LeaderboardStats::getArticlesRepaired($starttimestamp,'copyedit');
				break;
			case 'nfd':
				$section = 'Other';
				$learnlink = '/wikiHow:NFD-Guardian'; //doesn't exist
				$columnHeader = 'NFDs Reviewed';
				$data = LeaderboardStats::getNfdsReviewed($starttimestamp);
				break;
			case 'thumbs_up':
				$section = 'Other';
				$learnlink = '/wikiHow:Thumbs-Up';
				$columnHeader = 'Thumbs Up';
				$data = LeaderboardStats::getThumbsUp($starttimestamp);
				break;
			case 'spellchecked':
				$section = 'Writing';
				$learnlink = '/wikiHow:Spellchecker';
				$columnHeader = 'Articles Spell Checked';
				$data = LeaderboardStats::getSpellchecked($starttimestamp);
				break;
			case 'welcomewagon_indiv1':
				$section = 'Other';
				$learnlink = '/wikiHow:LB-Welcome-Wagon';
				$columnHeader = 'Welcome Wagon';
				$data = LeaderboardStats::getWelcomeWagon($starttimestamp);
				break;
			case 'techfeedbackreviewed':
				$section = 'Other';
				$learnlink = '/wikiHow:techfeedback';
				$columnHeader = 'Review Tech Feedback';
				$data = LeaderboardStats::getTechFeedbackReviewed($starttimestamp);
				break;
			case 'techarticletested':
				$section = 'Other';
				$learnlink = '/wikiHow:techverify';
				$columnHeader = 'Test Tech Articles';
				$data = LeaderboardStats::getTechArticleTested($starttimestamp);
				break;
			case 'duplicatetitles':
				$section = 'Other';
				$learnlink = '/wikiHow:duplicatetitles';
				$columnHeader = 'Duplicate Titles';
				$data = LeaderboardStats::getDuplicateTitlesReviewed($starttimestamp);
				break;
			case 'articlefeedbackreviewed':
				$section = 'Other';
				$learnlink = '/wikiHow:articlefeedback';
				$columnHeader = 'Review Article Feedback';
				$data = LeaderboardStats::getArticleFeedbackReviewed($starttimestamp);
				break;
			case 'fixflaggedanswers':
				$section = 'Other';
				$learnlink = '/wikiHow:fixflaggedanswers';
				$columnHeader = 'Fix Flagged Answers';
				$data = LeaderboardStats::getFixFlaggedAnswers($starttimestamp);
				break;
			case 'qap':
				$section = 'Other';
				$learnlink = '/wikiHow:QAPatrol';
				$columnHeader = 'Q&A Patrol';
				$data = LeaderboardStats::getQAPatrollers($starttimestamp);
				break;
			case 'topicstagged':
				$section = 'Other';
				$learnlink = '/wikiHow:TopicTagging';
				$columnHeader = 'Topic Tagging';
				$data = LeaderboardStats::getTopicsTagged($starttimestamp);
				break;
			default:
				$out->redirect("/Special:Leaderboard/articles_written");
				return;
		}

		switch ($section) {
			case 'Other':
				$sectionStyleOther = "class='on'";
				break;
			case 'RCNAB':
				$sectionStyleRCNAB = "class='on'";
				break;
			case 'Writing':
				$sectionStyleWriting = "class='on'";
				break;
			case 'Greenhouse':
				$sectionStyleGreenhouse = "class='on'";
				break;
			case "Imagevideo":
				$sectionStyleImagevideo = "class='on'";
				break;
		}


		// Note from Vu: Due to the reskin adding elements above the
		//   article_tab_line, I had use javascript to inject the tabs
		//   above the article_inner.  hacky i know, but otherwise it
		//   would have to go into the skin which is worse.
		$dropdown = " <span style='float:right;'>In the last <select id='period' onchange='WH.Leaderboard.changePeriod(this);'> <option $period24selected value='24'>24 hours</option> <option $period7selected value='7'>7 days</option> <option $period31selected value='31'>31 days</option> </select> </span>";
		$tabs_main = "<ul id='tabs'><li><a href='/Special:Leaderboard/articles_written' $sectionStyleWriting >Writing</a></li><li><a href='/Special:Leaderboard/articles_nabed' $sectionStyleRCNAB >RC and NAB</a></li><li><a href='/Special:Leaderboard/repair_format' $sectionStyleGreenhouse >Greenhouse</a></li><li><a href='/Special:Leaderboard/images_added' $sectionStyleImagevideo >Images/Videos</a></li><li><a href='/Special:Leaderboard/total_edits' $sectionStyleOther >Other</a></li></ul>";
		$tabs_sub = $this->getTabs($section, $target);
		if ($action != 'articlelist') {
			$tabs_sub .= "<div class='clearall'></div>";
		}
		$tab_sub .= "<div style='clear:both;'></div>";

		$out->addHTML("<div id='leaderboard_tabs'>{$dropdown}{$tabs_main}{$tabs_sub}</div>");

		$out->addHTML("  <style type='text/css' media='all'>/*<![CDATA[*/ @import '" . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.css?rev=') . WH_SITEREV . "'; /*]]>*/</style>
			<script src='" . wfGetPad('/extensions/min/f/extensions/wikihow/winpop.js?rev=') . WH_SITEREV . "'></script>");

		$out->addHTML("
			<script>
				var lb_page = '$target';
				var lb_period = '$period';
			</script>\n");

		// MAIN PAGE SECTION
		if ($action == 'articlelist') {
			$u = User::newFromName( $req->getVal( 'lb_name' ) );
			if (isset($u)) {
				$this->showArticlesPage( $target, $period, $starttimestamp, $u->getName() );
			} else {
				$out->addHTML( wfMessage('leaderboard-invalid-user') );
			}
			return;
		}

		$out->addHTML("<div id='Leaderboard' class='section_text'>
			<p class='leader_head'>Leaders: $columnHeader</p>");

		// don't show learn link for categories that don't have learning pages
		if (!in_array($target, $noLearnLinks)) {
			$out->addHTML("<span class='leader_learn'><img src='" . wfGetPad('/skins/WikiHow/images/icon_help.jpg') .
				"'><a href='$learnlink'>Learn about this activity</a></span>");
		}

		$out->addHTML("
			<table class='leader_table'>
				<tr> <td colspan='3' class='leader_title'>$columnHeader:</td> </tr> ");

		// display difference in only new articles
		// don't sort nfd b/c numbers can be big and include "," so don't sort nicely
		// don't sort ucitool_indiv1 for same reason as above
		if ($target != 'rc_edits' && $target != 'nfd' && $target != 'ucitool_indiv1') {
			arsort($data);
		}
		$index = 1;
		foreach ($data as $key => $value) {
			$u = new User();
			$u->setName($key);
			if ($value > 0 && $key != '' && $u->getName() != "WRM") {
				$class = "";
				if ($index % 2 == 1)
					$class = 'class="odd"';

				$img = Avatar::getPicture($u->getName(), true);
				if ($img == '') {
					$img = Avatar::getDefaultPicture();
				}

				$out->addHTML("
				<tr $class>
					<td class='leader_image'>" . $img . "</td>
					<td class='leader_user'>" . Linker::link($u->getUserPage(), $u->getName()) . "</td>
					<td class='leader_count'>".$value."</td>
				</tr> ");
				$data[$key] = $value * -1;
				$index++;
			}
			if ($index > 20) break;
		}
		$out->addHTML("</table></div>");
	}
}
