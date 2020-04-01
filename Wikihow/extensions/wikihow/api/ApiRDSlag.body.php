<?php

class ApiRDSlag extends ApiBase {
	public function __construct($main, $action) {
		parent::__construct($main, $action);
	}

	function execute() {
		global $wgMemc, $wgIsImageScaler;

		// Get the parameters
		$params = $this->extractRequestParams();

		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = '';

		if ($wgIsImageScaler) {
			$cachekey = wfMemcKey('apirdslag');
			$db = wfGetDB(DB_REPLICA);
			$lag = $db->getLag();
			$note = 'fromdb';
			if ($lag === false) {
				$lag = $wgMemc->get($cachekey);
				if (!is_string($lag) || $lag === '') {
					$note = 'error: unable to retrieve; replication may be broken';
				} else {
					$note = 'fromcache; unable to retrieve from db';
				}
			} else {
				$expiry = 6 * 60 * 60;
				$wgMemc->set($cachekey, $lag, $expiry);
			}
		} else {
			$lag = false;
			$note = 'error: wrong host';
		}

		$resultProps = array('lag' => $lag, 'note' => $note);
		$result->addValue(null, $module, $resultProps);

		//if ($error) {
		//    $result->addValue(null, $module, array('error' => $error));
		//}

		return true;
	}

	public function getResultProperties() {
		return array(
			'rdslag' => array(
				'lag' => array(
					ApiBase::PROP_TYPE => array('string' => 'number')),
				'note' => array(
					ApiBse::PROP_TYPE => array('string' => 'string')),
			),
		);
	}

	public function getAllowedParams() {
		return array(
		);
	}

	public function getParamDescription() {
		return array(
		);
	}

	public function getDescription() {
		return 'An API extension to get display the lag of our RDS database';
	}

	public function getPossibleErrors() {
		return parent::getPossibleErrors();
	}

	public function getExamples() {
		return array(
			'api.php?action=rdslag'
		);
	}

	public function getHelpUrls() {
		return '';
	}

	public function getVersion() {
		return '0.0.1';
	}
}

