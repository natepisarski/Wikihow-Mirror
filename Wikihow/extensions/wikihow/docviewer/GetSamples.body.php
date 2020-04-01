<?php

class GetSamples extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'GetSamples' );
	}

	private static $doc_array = array('doc','html','txt','pdf');
	private static $xls_array = array('xls','pdf');

	private static function getForm() {
		global $wgServer;
		$html = '<style type="text/css">
				#gd_url { width: 100%; }
				</style>
				<form action="'.$wgServer.'/Special:GetSamples" method="post">
				<p>Google Drive URL<br /><input type="text" id="gd_url" name="url" /></p>
				<p style="text-align: center;"><input type="submit" id="submit" value="Get Samples" />
				</form>';
		return $html;
	}

	private static function processForm($url,$sample_name) {
		global $wgServer;

		//parse url for the id and sample type
		$pieces = explode('/',$url);
		foreach ($pieces as $key => $p) {
			if (preg_match('/document/',$p)) {
				$dl_array = self::$doc_array;
				$id = $pieces[$key+2];
				$url_piece = 'documents/export/Export?id='.$id;
				break;
			}
			elseif (preg_match('/spreadsheet/',$p)) {
				$dl_array = self::$xls_array;
				$id = $pieces[$key+1];
				$id = preg_replace('/ccc\?key=|#gid.*$/','',$id);
				$url_piece = 'spreadsheets/Export?key='.$id;
				break;
			}
		}

		if (!$id) return '<p>invalid url</p><p><a href="'.$wgServer.'/Special:GetSamples">Try again</a>.</p>';

		//assemble all the download urls
		$dl_url = array();
		foreach ($dl_array as $format) {
			$dl_url[] = 'https://docs.google.com/feeds/download/'.$url_piece.'&exportFormat='.$format;
		}

		//DOWNLOAD!
		foreach ($dl_url as $dl) {
			$js .= 'window.open("'.$dl.'");'."\n";
		}

		$html = '<script type="text/javascript">
				'.$js.'
				</script>';

		return $html;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$html = self::processForm($req->getVal('url'),$req->getVal('sample'));
		}

		$html .= self::getForm();

		$out->addHTML($html);
	}

}
