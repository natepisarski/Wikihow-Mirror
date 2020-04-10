<?php

class Sitemap extends SpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'Sitemap' );
		$wgHooks['IsEligibleForMobileSpecial'][] = ['Sitemap::isEligibleForMobileSpecial'];
	}

	private function getTopLevelCategories() {
		global $wgCategoriesArticle;
		$results = array ();
		$title = CategoryHelper::getCategoryTreeTitle();
		$revision = Revision::newFromTitle($title);
		if (!$revision) return $results;

		// INTL: If there is a redirect to a localized page name, follow it
		if (strpos(ContentHandler::getContentText( $revision->getContent() ), "#REDIRECT") !== false) {
			$wikiPage = WikiPage::factory($title);
			$newTitle = $wikiPage->getRedirectTarget();
			$revision = Revision::newFromTitle( $newTitle );
		}

		$lines = explode("\n", ContentHandler::getContentText( $revision->getContent() ) );
		foreach ($lines as $line) {
			if (preg_match ('/^\*[^\*]/', $line)) {
				$line = trim(substr($line, 1)) ;
				switch ($line) {
					case "Other":
					case "wikiHow":
						break;
					default:
						$results [] = $line;
				}
			}
		}
		return $results;
	}

	private function getSubcategories($t) {
		$categoryViewer = new WikihowCategoryViewer($t, $this->getContext());
		$subcats = $categoryViewer->getSubcategories($t, false);
		return $subcats;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$out->setRobotPolicy('noindex,follow');
		$out->setHTMLTitle('wikiHow Sitemap');
		$out->addModuleStyles('ext.wikihow.sitemap_styles');
		$this->displayMobilePage();
	}

	private function displayMobilePage() {
		$topcats = $this->getTopLevelCategories();

		$data = ['cats' => []];
		$count = 0;
		foreach ($topcats as $cat) {
			$t = Title::newFromText($cat, NS_CATEGORY);
			if($t) {
				$catData = ['catname' => Linker::link($t, $t->getText()), 'subcats' => []];
				if($count % 2 == 0) {
					$catData['even'] = 1;
				} else {
					$catData['odd'] = 1;
				}
				$subcats = $this->getSubcategories($t);
				foreach ($subcats as $sub) {
					$catData['subcats'][] = ['subcatname' => Linker::link($sub, $sub->getText())];
				}
			} else {
				$catData = ['catname' => $cat];
			}
			$data['cats'][] = $catData;
			$count++;
		}

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render('Sitemap.mustache', $data);

		Hooks::run( 'SitemapOutputHtml', array( &$html ) );

		$this->getOutput()->addHTML( $html );
	}

	public static function isEligibleForMobileSpecial(&$isEligible) {
		$isEligible = true;
	}

	public function isAnonAvailable() {
		return true;
	}

}
