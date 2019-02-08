<?php

class InstagramArticleAdsAPI extends ApiBase {

	public function __construct($main, $action) {
		parent::__construct($main, $action);
	}

	function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$module = $this->getModuleName();

		$this->getMain()->setCacheMaxAge( 30 * 24 * 60 * 60 );
		$this->getMain()->setCacheMode( 'public' );

		$insta_ad = new InstagramArticleAds($params['type'], $params['version']);
		$result->addValue(NULL, $module, ['html' => $insta_ad->getAd()] );
	}

  function getAllowedParams() {
		return [
			'type' => '',
			'version' => ''
		];
	}
}
