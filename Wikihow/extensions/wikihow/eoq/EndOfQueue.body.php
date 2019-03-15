<?php

class EndOfQueue extends UnlistedSpecialPage {

	public $next_tools_user; //stored in mw message 'next_tools_user
	public $next_tools_anon; //stored in mw message 'next_tools_anon
	public $next_tools_mobile; //stored in mw message 'next_tools_mobile
	public $done_tool = '';

	public function __construct() {
		parent::__construct('EndOfQueue');
		EasyTemplate::set_path( __DIR__ );
		$this->next_tools_user = array_map('trim', explode("\n", trim(wfMessage("next_tools_user")->text())));
		$this->next_tools_anon = array_map('trim', explode("\n", trim(wfMessage("next_tools_anon")->text())));
		$this->next_tools_mobile = array_map('trim', explode("\n", trim(wfMessage("next_tools_mobile")->text())));
	}

	public function getMessage($dt) {
		if (!$dt) return '';
		$this->done_tool = $dt;

		$isMobile = MobileContext::singleton()->shouldDisplayMobileView();
		$mobile_suffix = $isMobile ? '_mobile' : '';

		//and the suggested next tool is...[drumroll]...
		$next_tool = $this->getNextTool();

		$from =  wfMessage('cd-'.$this->done_tool.'-phrase-prog')->text();
		if ($next_tool) {
			$to = $next_tool ? wfMessage('cd-'.$next_tool.'-phrase')->text() : '';
			$msg = $next_tool ? wfMessage('eoq_message'.$mobile_suffix,$from,$to)->text() : wfMessage('eoq_message_default'.$mobile_suffix,$from)->text();
			$link = wfMessage('cd-'.$next_tool.'-link')->text();
			$btn_msg = wfMessage('eoq_button',$to)->text();
		}
		else {
			//defaults
			$msg = wfMessage('eoq_message_default'.$mobile_suffix,$from)->text();
			$link = wfMessage('eoq_link_default')->text();
			$btn_msg = wfMessage('eoq_button_default')->text();
		}

		$vars = array(
			'styles' => "<link type='text/css' rel='stylesheet' href='".wfGetPad('/extensions/wikihow/eoq/end_of_queue.css?rev=' . WH_SITEREV) . "' />",
			'msg' => $msg,
			'link' => $link,
			'button_text' => $btn_msg,
			'e_type' => wfMessage('cd-'.$this->done_tool.'-event-type')->text(),
			'redirect' =>  wfMessage('cd-'.$next_tool.'-event-type')->text()
		);

		$msg = EasyTemplate::html('end_of_queue.tmpl.php',$vars);

		// usage logs
		UsageLogs::saveEvent(
			array(
				'event_type' => wfMessage('cd-'.$this->done_tool.'-event-type')->text(),
				'event_action' => 'end_of_queue_prompt',
				'serialized_data' => json_encode(
					array('redirect' => wfMessage('cd-'.$next_tool.'-event-type')->text())
				)
			)
		);

		return $msg;
	}

	public function getCounts() {
		$counts = array();

		//use dashboard counts
		$data = DashboardData::getStatsData();

		if ($data) {
			//only grab the abbreviation and the count
			foreach ($data['widgets'] as $type => $val) {
				$ct = $val['ct'];
				$counts[$type] = intval($ct);
			}

			//sort by count
			arsort($counts);
		}

		return $counts;
	}

	//grab the proper array of tools for this user
	public function getNextToolList() {
		$isMobile = MobileContext::singleton()->shouldDisplayMobileView();
		if ($isMobile) {
			$next_tools = $this->next_tools_mobile;
		}
		else {
			$user = $this->getUser();
			$next_tools = $user->isAnon() ? $this->next_tools_anon : $this->next_tools_user;
		}

		//remove the tool we're coming from
		if ($this->done_tool) {
			$key = array_search($this->done_tool,$next_tools);
			if ($key !== false) {
				array_splice($next_tools, $key, 1);
			}
		}

		return $next_tools;
	}

	//get the next tool
	public function getNextTool() {
		$next = '';
		$list = $this->getNextToolList();
		$counts = $this->getCounts();

		if ($counts) {
			foreach ($counts as $type => $val) {
				if (in_array($type,$list)) {
					$next = $type;
					break;
				}
			}
		}

		return $next;
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		//gotta have the tool send its tool abbreviation
		$dt = $this->getRequest()->getVal('this_tool');
		if (!$dt) return;

		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$out->addModules('ext.wikihow.UsageLogs');
		$out->addHTML($this->getMessage($dt));
		return;
	}
}
