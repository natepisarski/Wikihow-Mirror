<?php

class Sitemap extends SpecialPage {

	function __construct() {
		parent::__construct( 'Sitemap' );
	}

	function getTopLevelCategories() {
		global $wgCategoriesArticle;
		$results = array ();
		$title = Categoryhelper::getCategoryTreeTitle();
		$revision = Revision::newFromTitle($title);
		if (!$revision) return $results;

		// INTL: If there is a redirect to a localized page name, follow it
		if(strpos($revision->getText(), "#REDIRECT") !== false) {
			$revision = Revision::newFromTitle( Title::newFromRedirect($revision->getText()));
		}

		$lines = explode("\n", $revision->getText() );
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

	function execute($par) {
		global $wgUser;
		$out = $this->getOutput();
		$out->setRobotPolicy('noindex,follow');
		$topcats = $this->getTopLevelCategories();

		$out->setHTMLTitle('wikiHow Sitemap');

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

		wfRunHooks( 'SitemapOutputHtml', array( &$html ) );

		$out->addHTML( $html );
	}

}

