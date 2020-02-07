<?php

class Sitemap extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Sitemap' );
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

		if(Misc::isMobileMode()) {
			$this->displayMobilePage();
			return;
		}

		$topcats = $this->getTopLevelCategories();

		$count = 0;
		$html = "
			<style>
				#catentry li {
					margin-bottom: 0;
				}
				table.cats {
					width: 100%;
				}
				.cats td {
					vertical-align: top;
					border: 1px solid #e5e5e5;
					padding: 10px;
					background: white;
					-moz-border-radius: 4px;
					-webkit-border-radius: 4px;
					-khtml-border-radius: 4px;
					border-radius: 4px;
				}
			</style>
			<table align='center' class='cats' cellspacing=10px>";

		foreach ($topcats as $cat) {
			$t = Title::newFromText($cat, NS_CATEGORY);
			if ($count % 2 == 0)
				$html .= "<tr>";
			if ($t) {
				$subcats = $this->getSubcategories($t);
				$html .= "<td><h3>" . Linker::link($t, $t->getText()) . "</h3><ul id='catentry'>";
				foreach ($subcats as $sub) {
					$html .= "<li>" . Linker::link($sub, $sub->getText()) . "</li>\n";
				}
				$html .= "</ul></td>\n";
			}
			else {
				if ($count % 2 == 1) {
					$html .= "<tr>";
				}
				$html .= "<td><h3 style=\"color:red;\">" . $cat . "</h3></td>\n";
			}
			if ($count % 2 == 1)
				$html .= "</tr>";
			$count++;

		}

		$html .= "</table>";

		Hooks::run( 'SitemapOutputHtml', array( &$html ) );

		$out->addHTML( $html );
	}

	private function displayMobilePage() {
		$topcats = $this->getTopLevelCategories();

		$htmlcss = "
			<style>
				.cat_list {
					border: 1px solid #e5e5e5;
				    padding: 10px;
				    background: white;
				    margin: 10px;
				}

				.cat_list ul {
					padding-left: 0;
				}

				.cat_list_ul {
					margin-left: 5px;
					background-color: #fff;
                    padding: 20px;
				}

				@media only screen and (min-width:728px) {
					.cat_container {
						display: table;
						border-spacing: 10px;
						width: 100%;
					}
					.cat_list {
						width: 50%;
						display: table-cell;
					}
					#content_inner { width: 100%; }
					#cat_outer { margin-top: -10px; }
				}

				@media only screen and (min-width:975px) {
					.content {
					    border: 1px solid #e5e5e5;
					    padding: 22px 27px;
					}

					#cat_outer {
						margin-top: 0px;
					}

					.cat_list h3 {
						padding: 0 0 0.5em 0;
					}
				}
			</style>";

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
			new Mustache_Loader_FilesystemLoader(__DIR__)
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$html = $m->render('Sitemap.mustache', $data);

		Hooks::run( 'SitemapOutputHtml', array( &$html ) );
		$html = $htmlcss . $html;

		$this->getOutput()->addHTML( $html );
	}

	public static function isEligibleForMobileSpecial(&$isEligible) {
		global $wgTitle;
		if ($wgTitle && strrpos($wgTitle->getText(), "Sitemap") === 0) {
			$isEligible = true;
		}

		return true;
	}

	public function isAnonAvailable() {
		return true;
	}

}
