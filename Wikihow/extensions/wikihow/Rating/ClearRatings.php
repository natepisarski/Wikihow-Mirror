<?php

use MethodHelpfulness\ArticleMethod;

/**
 * Special page to clear the ratings of an article. Accessed via the list
 * of low ratings pages.
 */
class ClearRatings extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ClearRatings' );
	}

	public function addClearForm($target, $type, $err) {
		$out = $this->getOutput();

		$blankme = Title::makeTitle(NS_SPECIAL, "ClearRatings");

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array('actionUrl' => $blankme->getFullURL(), 'target' => htmlspecialchars($target), 'type' => $type, 'err' => $err));

		$out->addHTML($tmpl->execute('selectForm.tmpl.php'));
	}

	public function execute($par) {
		global $wgLang;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$err = "";
		$target = isset( $par ) ? $par : $req->getVal( 'target' );
		$restore = $req->getVal('restore', null);

		$out->setHTMLTitle('Clear Ratings - Accuracy Patrol');
		$type = $req->getVal('type', 'article');

		$rateItem = new RateItem();
		$ratingTool = $rateItem->getRatingTool($type);

		if ($ratingTool) $t = $ratingTool->makeTitle($target);
		if ($t == '') {
			$out->addHTML(wfMessage('clearratings_notitle'));
			$this->addClearForm($target, $type, $err);
			return;
		}
		$me =  SpecialPage::getTitleFor( 'ClearRatings', $target );
		if ($user->getID() == 0) {
			return;
		}

		if ($req->wasPosted()) {
			// clearing ratings
			$clearId = $req->getVal('clearId', null);

			if ($clearId != null) {
				$ratingTool->clearRatings($clearId, $user);

				// Also delete star ratings w/ articles
				if ($type == 'article') {
					$starRatingTool = new RatingStar();
					$starRatingTool->clearRatings($clearId, $user);

					// clear data about the summary section (videos and helpfulness)
					AdminClearRatings::resetSummaryData( $target );
				}

				$out->addHTML(wfMessage('clearratings_clear_finished') . "<br/><br/>");
			}
		}

		if ($restore != null && $req->getVal('reason', null) == null) {
			//ask why the user wants to resotre
			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars(array('postUrl' => $me->getFullURL(), 'params' => $_GET,));
			$out->addHTML($tmpl->execute('restore.tmpl.php'));
			return;
		} elseif ($restore != null) {
			$userParam = $req->getVal('user');
			$page = $req->getVal('page');
			$reason = $req->getVal('reason');
			$u = User::newFromId($userParam);
			$up = $u->getUserPage();
			$hi = $req->getVal('hi');
			$low = $req->getVal('low');

			$count = $ratingTool->getUnrestoredCount($page);

			$ratingTool->restore($page, $userParam, $hi, $low);

			$out->addHTML("<br/><br/>" . wfMessage('clearratings_clear_restored', Linker::link($up, $u->getName()), '') . "<br/><br/>");

			// add the log entry
			$ratingTool->logRestore($page, $low, $hi, $reason, $count);
		}

		if ($target != null && $type != null) {
			$id = $ratingTool->getId($t);
			if ($id === 0) {
				$err = wfMessage('clearratings_no_such_title', $target);
			} elseif ($type == "article" && !$t->inNamespace(NS_MAIN)) {
				$err = wfMessage('clearratings_only_main', $target);
			} else {
				// clearing info
				$ratingTool->showClearingInfo($t, $id, $me, $target);
				$ap = Title::makeTitle(NS_SPECIAL, "AccuracyPatrol");
				$out->addHTML( Linker::link($ap, "Return to accuracy patrol") );
			}
		}

		$this->addClearForm($target, $type, $err);
	}

}
