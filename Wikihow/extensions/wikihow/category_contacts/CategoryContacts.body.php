<?php

/*
CREATE TABLE category_contacts (
	cc_email varchar(255) NOT NULL DEFAULT '',
   	cc_subcat varchar(255) NOT NULL DEFAULT '',
  	cc_source varchar(255) NOT NULL DEFAULT '',
  	cc_creatdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  	cc_num_contacted int(4) NOT NULL DEFAULT 0,
  	cc_last_contacted varchar(20) NOT NULL DEFAULT '',
   	cc_sendflag tinyint(1) NOT NULL DEFAULT 1,
  	cc_nosend_reason varchar(255) NOT NULL DEFAULT ''
);
CREATE INDEX cc_email_index ON category_contacts (cc_email);
 */

global $IP;
require_once("$IP/extensions/wikihow/titus/GoogleSpreadsheet.class.php");

class CategoryContacts extends UnlistedSpecialPage {

	//worksheets
	const WH_MAILINGLIST_ADD = '1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/od6';
	const WH_MAILINGLIST_STOP = '1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/o8vdgg0';
	const WH_MAILINGLIST_CAT = '1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/og4ywqx';
	const WH_MAILINGLIST_SRC = '1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/or9qs29';
	const WH_MAILINGLIST_RSN = '1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/ovlfnwl';

	public function __construct() {
		parent::__construct('CategoryContacts');
		EasyTemplate::set_path( __DIR__ );
	}

	public function execute($par) {
		global $wgIsDevServer;

		$user = $this->getUser();
		$out = $this->getOutput();

		//gotta run on parsnip because of Google API
		if ($_SERVER['HTTP_HOST'] != 'parsnip.wikiknowhow.com' && !$wgIsDevServer) {
			$out->redirect('https://parsnip.wikiknowhow.com/Special:CategoryContacts');
		}

		// Check permissions
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$action = $this->getRequest()->getVal('action');
		if ($action) {
			$out->setArticleBodyOnly(true);

			if ($action == 'add') {
				list($good, $bad_count, $bad_cats) = $this->processNewContacts();
				$result['good'] = wfMessage('cc_add_result_good',$good)->text();
				$result['bad'] = $bad_count > 0 ? wfMessage('cc_add_result_bad',$bad_count, implode('<br />',$bad_cats))->text() : '';
			}
			elseif ($action == 'stop') {
				list($stopped, $bad_count, $bad_emails) = $this->processStopContacts();
				$result['good'] = wfMessage('cc_stop_result_good',$stopped)->text();
				$result['bad'] = $bad_count > 0 ? wfMessage('cc_stop_result_bad', $bad_count, implode('<br />',$bad_emails))->text() : '';
			}

			print(json_encode($result));
			return;
		}

		$out->setPageTitle('Category Contacts');
		$out->addModules('ext.wikihow.CategoryContacts');
		$out->addModuleStyles('ext.wikihow.CategoryContacts.styles');

		$vars = array(
			'add_link' => 'https://docs.google.com/spreadsheets/d/1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/edit#gid=0',
			'stop_link' => 'https://docs.google.com/spreadsheets/d/1ai1sUIyFOqJ6kHmOxVHOIXYrwTOuDEZxnEaj8k05fxU/edit#gid=536425050'
		);
		$html = EasyTemplate::html('CategoryContacts.tmpl.php',$vars);

		$out->addHTML($html);
	}

	//main spreadsheet function
	private function getDocData($sheet) {
		if (empty($sheet)) return '';

		$gs = new GoogleSpreadsheet();
		$gs->getToken();

		// $endCol = $sheet == self::WH_MAILINGLIST_ADD ? 3 : 2;
		$endCol = 3;

		$cols = $gs->getColumnDataJSON($sheet, 1, $endCol, 2);
		return $cols;
	}

	//process the "Add" tab on the sheet
	private function processNewContacts() {
		$dbw = wfGetDB(DB_MASTER);
		$data = $this->getDocData(self::WH_MAILINGLIST_ADD);

		$added = 0;
		$bad_count = 0;
		$bad_cats = array();
		foreach ($data as $row) {
			$email = trim((string)$row[0]);
			$cat = trim((string)$row[1]);
			$cat = str_replace(' ','-',$cat);
			$src = trim((string)$row[2]);

			//gotta have an email
			if (!$email) continue;

			//   ^^
			//=(o.o)= gotta have a cat
			if ($cat == '') {
				$bad_cats[] = '- '.wfMessage('cc_bad_cat_none', $email)->text();
				$bad_count++;
				continue;
			}

			//validate category
			$t = Title::newFromText($cat, NS_CATEGORY);
			if (!$t || !$t->exists()) {
				$bad_cats[] = '- '.$cat.wfMessage('cc_bad_cat_invalid', $email)->text();
				$bad_count++;
				continue;
			}

			//do a couple sanity checks
			$sendflag = 1;
			$res = $dbw->select('category_contacts', array('cc_subcat','cc_sendflag'), array('cc_email' => $email), __METHOD__);
			foreach ($res as $row) {

				//already in there?
				if ($row->cc_subcat == $cat) {
					$bad_cats[] = '- '.$cat.wfMessage('cc_bad_cat_exists',$email)->text();
					$bad_count++;
					continue 2;
				}

				//do not send flag flipped?
				if ($row->cc_sendflag == 0) $sendflag = 0;
			}

			//do it
			$res = $dbw->insert('category_contacts', array(
							'cc_email' => $email,
							'cc_subcat' => $cat,
							'cc_source' => $src,
							'cc_sendflag' => $sendflag
							), __METHOD__);

			if ($res) $added++;
		}

		return array($added, $bad_count, $bad_cats);
	}

	//process the "Stop" tab on the sheet
	private function processStopContacts() {
		$dbr = wfGetDB(DB_REPLICA);
		$data = $this->getDocData(self::WH_MAILINGLIST_STOP);

		$stopped = 0;
		$bad_count = 0;
		$bad_emails = array();
		foreach ($data as $row) {
			$email = trim($row[0]);
			$reason = trim($row[1]);
			$cat = trim($row[2]);

			//gotta have these two
			if (!$email || !$reason) continue;

			$filters = array('cc_email' => $email);
			if ($cat) $filters['cc_subcat'] = $cat;

			//check to see if it's even in our table
			$res = $dbr->select('category_contacts', array('cc_sendflag'), $filters, __METHOD__);
			if (!is_object( $res ) || !$res->numRows()) {
				$bad_emails[] = '- '.$email;
				$bad_count++;
				continue;
			}
			// //now check the send flag
			// foreach ($res as $row) {
				// if ($row->cc_sendflag == 0) {
					// $bad_emails[] = '- '.$email.wfMessage('cc_err_already_flagged')->text();
					// $bad_count++;
					// continue 2;
				// }
			// }

			//do it
			$res = $this->stopContact($email, $reason, $cat);
			if ($res) $stopped++;
		}

		return array($stopped, $bad_count, $bad_emails);
	}

	public function stopContact($email, $reason, $cat) {
		if (!$email || !$reason) return false;
		$dbw = wfGetDB(DB_MASTER);

		$filters = array('cc_email' => $email);
		if ($cat) $filters['cc_subcat'] = $cat;

		$res = $dbw->update('category_contacts', array('cc_sendflag' => 0, 'cc_nosend_reason' => $reason), $filters, __METHOD__);
		return $res;
	}
 }

class CategoryContactMailer extends UnlistedSpecialPage {

	const MW_MSG_URL = 'https://www.wikihow.com/MediaWiki:';
	const MAX_TO_SEND = 3;

	public function __construct() {
		parent::__construct('CategoryContactMailer');
		EasyTemplate::set_path( __DIR__ );
	}

	public function execute($par) {
		global $wgCanonicalServer;

		// Fudge the canonical server since we're operating on the wikiknowhow.com
		// domain, but want to send emails from wikihow.com
		$wgCanonicalServer = 'https://www.wikihow.com';

		$user = $this->getUser();
		$out = $this->getOutput();

		// Check permissions
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if (count($this->getRequest()->getValues()) > 1) {
			$out->setArticleBodyOnly(true);

			if ($this->getRequest()->getVal('getmax')) {
				$cat = trim($this->getRequest()->getVal('getmax'));
				$src = trim($this->getRequest()->getVal('src'));
				$ctd = trim($this->getRequest()->getVal('ctd'));
				$validate = ($this->getRequest()->getVal('validate')) ? true : false;
				$max_count = $this->getTotalMaxNum($cat, $src, $ctd, $validate);
				print $max_count;
			}
			elseif ($this->getRequest()->getVal('testIt')) {
				$mwm = trim($this->getRequest()->getVal('mwm'));
				$sub = trim($this->getRequest()->getVal('sub'));
				$email = trim($this->getRequest()->getVal('testIt'));
				$result = $this->sendTestMail($email, $sub, $mwm);
				print($result);
			}
			elseif ($this->getRequest()->getVal('sendEm')) {
				$cat = trim($this->getRequest()->getVal('cat'));
				$sub = trim($this->getRequest()->getVal('sub'));
				$max = trim($this->getRequest()->getVal('max'));
				$mwm = trim($this->getRequest()->getVal('mwm'));
				$src = trim($this->getRequest()->getVal('src'));
				$ctd = trim($this->getRequest()->getVal('ctd'));
				$result = $this->sendToContacts($cat, $sub, $max, $mwm, $src, $ctd);
				print($result);
			}
			elseif ($this->getRequest()->getVal('slidermax')) {
				$result = $this->getMaxSent();
				print($result);
			}

			return;
		}

		$out->setPageTitle('Category Contact Mailer');
		$out->addModules('jquery.ui.slider');
		$out->addModules('ext.wikihow.CategoryContactMailer');
		$out->addModuleStyles('ext.wikihow.CategoryContacts.styles');
		$vars = array(
			'max_num' => 100,
		);
		$html = EasyTemplate::html('CategoryContactMailer.tmpl.php',$vars);

		$out->addHTML($html);
	}

	//max possible email addresses to which send this based on category
	public function getTotalMaxNum($cat, $src, $ctd, $validate) {
		if (!$cat || $cat == 'no_cat') return 0;
		if ($validate) $this->updateSendFlags();

		$dbr = wfGetDB(DB_REPLICA);
		$filters = array('cc_subcat' => $cat, 'cc_sendflag' => 1);

		if ($src) $filters['LOWER(cc_source)'] = strtolower($src);
		if ($ctd != 'any') {
			$range = explode('-',$ctd);
			if (count($range) == 2) {
				$filters[] = 'cc_num_contacted >= '.$range[0];
				$filters[] = 'cc_num_contacted <= '.$range[1];
			}
		}
		$count = $dbr->selectField('category_contacts', 'count(*)', $filters, __METHOD__);
		return $count;
	}

	//max times we've sent someone an email
	public function getMaxSent() {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField('category_contacts', 'max(cc_num_contacted)', array('cc_sendflag' => 1), __METHOD__);
		return $count;
	}

	private function sendTestMail($email, $sub, $mwm) {
		//can only send to wikihow.com addresses
		if (!preg_match('/@wikihow\.com$/',$email)) return wfMessage('ccm_bad_email')->text();

		if ($sub == '') return wfMessage('ccm_err_sub')->text();
		if ($mwm == '') return wfMessage('ccm_err_mwm')->text();

		$sent = $this->sendIt($email, $sub, $mwm);
		$result = $sent ? wfMessage('ccm_sent', 1, 'Test email')->text() : wfMessage('ccm_test_failed')->text();
		return $result;
	}

	private function sendToContacts($cat, $sub, $max, $mwm, $src, $ctd) {
		if (!$cat) return wfMessage('ccm_err_cat')->text();
		$cat = str_replace(' ','-',$cat);
		if ($sub == '') return wfMessage('ccm_err_sub')->text();
		if ($mwm == '') return wfMessage('ccm_err_mwm')->text();
		$max = (int)$max;
		if ($max < 1) return wfMessage('ccm_err_max')->text();

		$filters = array('cc_subcat' => $cat, 'cc_sendflag' => 1);

		if ($src) $filters['LOWER(cc_source)'] = strtolower($src);
		if ($ctd != 'any') {
			$range = explode('-',$ctd);
			if (count($range) == 2) {
				$filters[] = 'cc_num_contacted >= '.$range[0];
				$filters[] = 'cc_num_contacted <= '.$range[1];
			}
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('category_contacts',
					array('cc_email'),
					$filters,
					__METHOD__,
					array('ORDER BY' => 'cc_num_contacted', 'LIMIT' => $max));

		$count = 0;
		foreach ($res as $row) {
			if (!$row->cc_email) continue;

			//unsubscribed?
			if (OptoutHandler::hasOptedOut($row->cc_emaill)) {
				$reason = wfMessage('ccm_unsub_reason')->text();
				CategoryContacts::stopContact($row->cc_email, $reason);
				continue;
			}

			$result = $this->sendIt($row->cc_email, $sub, $mwm);
			if ($result) {
				$this->markSent($row->cc_email, $cat);
				$count++;
			}
		}

		return wfMessage('ccm_sent', $count, $cat)->text();
	}

	//sync up sendflags with our main unsubscribe table
	private function updateSendFlags() {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select('category_contacts', array('cc_email'), array('cc_sendflag' => 1), __METHOD__);

		foreach ($res as $row) {
			if (!$row->cc_email) continue;

			//unsubscribed?
			if (OptoutHandler::hasOptedOut($row->cc_email)) {
				$reason = wfMessage('ccm_unsub_reason')->text();
				CategoryContacts::stopContact($row->cc_email, $reason);
			}
		}

		return;
	}

	private function markSent($email, $cat) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->update('category_contacts',
					array('cc_num_contacted = cc_num_contacted+1', 'cc_last_contacted = CURRENT_TIMESTAMP'),
					array('cc_email' => $email, 'cc_subcat' => $cat), __METHOD__);

		// //don't send too many...
		// $num = $dbw->selectField('category_contacts', array('cc_num_contacted'), array('cc_email' => $email, 'cc_subcat' => $cat), __METHOD__);
		// if ($num >= self::MAX_TO_SEND) {
			// //flip that flag
			// $reason = wfMessage('ccm_max_sent')->text();
			// CategoryContacts::stopContact($email, $reason);
		// }

		return $res;
	}

	//main sendmail function
	private function sendIt($to_email, $subject, $mwm) {
		global $wgIsDevServer;

		$result = false;
		if ($to_email == '' || $subject == '' || $mwm == '') return false;

		$unsub_link = UnsubscribeLink::newFromEmail($to_email)->getLink();
		$mwm = str_replace(self::MW_MSG_URL, '', $mwm);
		$body = wfMessage($mwm,$unsub_link)->text();
		if (stripos($body,self::MW_MSG_URL)) return false;

		$from_email = 'wikiHow Community Team <communityteam@wikihow.com>';
		$from = new MailAddress($from_email);
		$to = new MailAddress($to_email);
		$content_type = "text/html; charset=UTF-8"; //HTML email

		if (!$wgIsDevServer) {
			$sent = UserMailer::send($to, $from, $subject, $body, null, $content_type, "cat_contacts");
			$result = $sent->isGood() ? true : false;
		}

		return $result;
	}
}
