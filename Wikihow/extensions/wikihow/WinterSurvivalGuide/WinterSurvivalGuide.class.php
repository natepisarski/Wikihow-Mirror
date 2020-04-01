<?php

class WinterSurvivalGuide {

	public function homepageCTA() {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__ . '/assets')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$vars = [
			'header' => wfMessage('winter_survival_guide')->parse(),
			'link' => '/wikiHow:Winter-Survival-Guide',
			'button_text' => wfMessage('winter_survival_guide_button')->text()
		];

		$html = $m->render('winter_survival_guide_cta', $vars);
		return $html;
	}

	public static function onBeforePageDisplay($out, $skin) {
		$title = $out->getTitle();
		if ($title && $title->inNamespace(NS_PROJECT) && $title->getText() == 'Winter Survival Guide') {
			$out->addModules('ext.wikihow.winter_survival_guide');
		}
		return true;
	}

}
