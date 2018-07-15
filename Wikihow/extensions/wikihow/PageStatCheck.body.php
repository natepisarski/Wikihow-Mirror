<?php

class PageStatCheck extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('PageStatCheck');
	}

	public function execute($par) {
		global $wgOut, $wgRequest;
	
	
		if ($wgRequest->wasPosted()) {
			// this may take a while...
			set_time_limit(0);

			$wgOut->setArticleBodyOnly(true);
			
			$username = trim($wgRequest->getVal('username'));
			$u = User::newFromName($username);
			if ($u && $u->getID())
				$html = '<br /><h3>Page views in past 30 days on articles</h3>'.
						'<p>Edited in past 30 days: '.self::getStatsByUser($u,true).'</p>'.
						'<p>All time: '.self::getStatsByUser($u,false).'</p>';
			else
				$html = 'invalid user name';
				
			print json_encode(array('result' => $html));
			return;
		}
		
		$wgOut->setHTMLTitle('Page Stat Check - wikiHow');

$tmpl = <<<EOHTML
<form id="page-stat-form" method="post" action="/Special:PageStatCheck">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
Page Stat Check
</div>

<div style="font-size: 13px; margin: 20px 0 7px 0;">
Enter a user name to get page view stats on all their created and edited pages.
</div>
<input type="input" id="username" name="username" />
<button id="submit" disabled="disabled">submit</button><br />
<div id="pages-result"></div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#submit')
			.removeAttr('disabled')
			.click(function () {
				var form = $('#page-stat-form').serializeArray();
				$('#pages-result').html('loading ...');
				$.ajax({
					type: 'POST',
					url: '/Special:PageStatCheck',
					data: form,
					dataType: 'json',
					timeout: 120000,
					success: function(data) {
						$('#pages-result').html(data['result']);
					}
				});
				return false;
			});
	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
	
	public function getStatsByUser($u,$past30days) {
		$html = '';
     	$dbr = wfGetDB(DB_SLAVE);
		
		if ($past30days) {
			//cutoff = past 30 days
			$cutoff = wfTimestamp(TS_MW, time() - 60 * 60 * 24 * 30);
		}
		else {
			$cutoff = 0;
		}
		
		$conds = array('page_id=rev_page', 'rev_user' => $u->getID(), 'page_is_redirect' => 0, 'page_namespace' => NS_MAIN);
		if ($cutoff) $conds[] = 'rev_timestamp > ' . $cutoff;
		
		//grab all started articles in the past timeframe
		$res = $dbr->select(array('revision','page'),
			array('rev_page','page_title'), 
			$conds,
			__METHOD__,
			array('GROUP BY' => 'rev_page'));
		
		foreach ($res as $row) {
			$stats = Pagestats::get30day($row->rev_page,$dbr);
			$total += (int)$stats;
		}
		
		return (int)$total;
	}
}
