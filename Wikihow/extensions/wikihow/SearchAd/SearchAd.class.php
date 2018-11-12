<?php

class SearchAd extends UnlistedSpecialPage {

	const GENERIC_AD = 'wikihow_generic_ad';

	private $versions = ['1','2','3','4'];
	private $ad_type = '';
	private $ad_version = '';

	public function __construct() {
		parent::__construct( 'SearchAd');
	}

	public function execute($par) {
		global $wgSquidMaxage;

		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$out->setSquidMaxage($wgSquidMaxage); //make sure this caches

		$this->ad_type = $this->getRequest()->getText('t', '');
		if ($this->ad_type != 'bnr' && $this->ad_type != 'sq') return;

		$this->ad_version = $this->getVersion();

		$page_title = $this->getRequest()->getText('a', '') ?: self::GENERIC_AD;
		$ad = self::searchAdHtml($page_title);

		$out->addHTML($ad);
	}

	public function searchAdHtml(string $page_title): string {
		if ($this->getLanguage()->getCode() != 'en') return '';

		if ($page_title !== self::GENERIC_AD) {
			$title = $page_title ? Title::newFromText($page_title) : $this->getTitle();
			if (!$title || !$title->exists() || !$title->inNamespace(NS_MAIN)) $page_title = self::GENERIC_AD;
		}

		if ($page_title == self::GENERIC_AD) {
			//generic result
			$title_txt = wfMessage('searchad_generic')->text();
			$title_link = 'Main-Page';
		}
		else {
			$title_txt = $title->getText();
			$title_link = wfMessage('searchad_link', str_replace(' ','+',$title_txt))->text();
		}

		$vars = [
			'title' => $title_txt,
			'link' => $title_link.$this->getTrackingQuerystring(),
			'version' => $this->ad_version,
			'css' => $this->getStyle(),
			'searchad_tag' => wfMessage('searchad_tag')->text(),
			'searchad_wh2' => wfMessage('searchad_wh2')->text(),
			'search_button' => wfMessage('search')->text()
		];

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render('search_ad_'.$this->ad_type, $vars);
	}

	private function getVersion(): string {
		$num = $this->getRequest()->getInt('v'); //to force a version

		if (empty($num) || count($num) != 1) {
			$num = mt_rand(1, count($this->versions));
		}

		return $this->versions[$num-1];
	}

	private function getStyle(): string {
		$style = Misc::getEmbedFile('css', __DIR__ . '/css/search_ad_'.$this->ad_type.'.css');
		$style = ResourceLoader::filter('minify-css', $style);
		$style = HTML::inlineStyle($style);
		return $style;
	}

	private function getTrackingQuerystring(): string {
		$param = '&ha=';
		$param .= $this->ad_type == 'bnr' ? 'intro_728x90_' : 'rr_300x250_';
		$param .= 'v'.$this->ad_version;
		return $param;
	}
}
