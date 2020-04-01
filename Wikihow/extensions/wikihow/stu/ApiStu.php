<?php

if (!defined('MEDIAWIKI')) die();

class ApiStu extends ApiBase {
	private static $subCommands = array (
		'list-domains', 'list-resets',
	);

	public function __construct($main, $action) {
		parent::__construct($main, $action);
		$this->mSubCommands = self::$subCommands;
	}

	public function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();
		$command = $params['subcmd'];

		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = '';
		// try to catch fatal errors
		ini_set('display_errors', 0);
		register_shutdown_function(function () {
			$error = error_get_last();
			if ($error !== null && ($error['type'] == E_ERROR || $error['type'] == E_CORE_ERROR)) {
				header('Content-Type: application/json');
				$result = [ 'whdata' => ['error' => print_r($error, true)] ];
				print json_encode($result);
				exit;
			}
		});

		switch ($command) {
			case 'list-domains':
				$type = $params['type'];
				$revid = !$random ? $params['oldid'] : 0;
				$mobile = wfGetAllCanonicalDomains(true, true);
				$desktop = wfGetAllCanonicalDomains(false, true);
				if ($type == 'mobile') {
					$result->addValue( null, $module, $mobile );
				} elseif ($type == 'desktop') {
					$result->addValue( null, $module, $desktop );
				} elseif ($type == 'both') {
					$result->addValue( null, $module, ['mobile' => $mobile, 'desktop' => $desktop] );
				} else {
					$out = [];
					foreach ($desktop as $lang => $domain) {
						$out[$lang] = [
							'desktop' => $domain,
							'mobile' => $mobile[$lang] ];
					}
					$result->addValue( null, $module, $out );
				}
				break;

			case 'list-resets':
				$ratingTool = new RatingArticle();
				$resets = $ratingTool->listRecentResets('2 days ago'); // list all those over past 2 days
				$result->addValue( null, $module, $resets );
				break;

			default:
				$error = 'no subcmd specified';
				break;
		}

		if ($error) {
			$result->addValue( null, $module, ['error' => $error] );
		}

		global $wgUseSquid, $wgSquidMaxage;
		if ($wgUseSquid) {
			$this->getMain()->setCacheMode("anon-public-user-private");
			$this->getMain()->setCacheMaxAge($wgSquidMaxage);
			$this->getMain()->setCacheControl(["must-revalidate"=>true]);
		}

		return true;
	}

	function getAllowedParams() {
		return [
			'subcmd' => array (
				ApiBase::PARAM_TYPE => $this->mSubCommands
			),

			'type' => '', // list-domains

			'_' => '', // for randomness
			];
	}
	public function getDescription() {
		return array ( 'Gets stats info used by the stu process');
	}

	public function getParamDescription() {
		return array (
			'subcmd' => 'The subcommand you are performing',
			'type' => 'type of domains to list',
			'_' => 'for random -- this param is thrown away, but used to make the url unique'
		);
	}

	protected function getExamples() {
		return [ 'api.php?action=whstu&subcmd=list-domains&format=jsonfm',
			'api.php?action=whstu&subcmd=list-resets&format=jsonfm'];
	}

	function getVersion() {
		return '1.0.0';
	}

}
