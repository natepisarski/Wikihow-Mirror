<?php

class WikihowMobileHomepage extends Article {

	function __construct( Title $title, $oldId = null ) {
		parent::__construct($title, $oldId);
	}

	function view() {
		global $wgOut, $wgTitle;
		$out = $this->getContext()->getOutput();
		IOSHelper::addIOSAppBannerTag();

		$out->setHTMLTitle('wikiHow - '.wfMessage('main_title')->text());

		/* This will not work if any dependencies are added to the mobile homepage module.
		 * Perhaps an alternative would be creating a dependency module that loads the styles
		 * first to ensure the slider JS loads afterward.
		 */
		$out->addModuleStyles('zzz.mobile.wikihow.homepage'); // load styles first
		$out->addModuleScripts('zzz.mobile.wikihow.homepage'); // then scripts

		WikihowMobileHomepage::showTopImage();

		$wgOut->addHtml($this->getSearchHtml());

		$showHighDPI = WikihowMobileTools::isHighDPI($wgTitle);

		$fas = FeaturedArticles::getTitles(30); //Only want 24, but getting 30 to account for some titles that might not be usable
		$html = "<div id='fa_container_outer'><div id='fa_container'>";
		$count = 0;
        $boxes = array();
        $videoBoxes = array();
		foreach ($fas as $fa) {
			if ($count >= 24) {
				break;
			}
			$box = WikihowMobileTools::makeFeaturedArticlesBox($fa['title'],false,$showHighDPI);
			if (!$box || !$box->url) continue;
            $boxes[] = $box;
            $videoUrl = ArticleMetaInfo::getVideoSrc( $box->title );
            if ( $videoUrl ) {
                $videoBoxes[] = count($boxes) - 1;
            }
			$count++;
        }

        //limit the number of videos in this section
        $maxNumVideos = count( $boxes ) * 0.10;
        shuffle( $videoBoxes );
        for ( $i = 0; $i < count( $videoBoxes ) - $maxNumVideos; $i++ ) {
            $boxes[$videoBoxes[$i]]->noVideo = true;
        }

        foreach ( $boxes as $box ) {
			//On homepage we only want one size image, not mobile and tablet image
			$boxInnerHtml = WikihowMobileTools::getImageContainerBoxHtml( $box );
			$html .= $boxInnerHtml;
		}

		$html .= "</div></div><div class='clearall'></div>";
		$html2 = "";
		$html3 = "";

		Hooks::run( 'WikihowHomepageFAContainerHtml', array( &$html, &$html2, &$html3 ) );

		$wgOut->addHTML( $html );
		$wgOut->addHTML( $html2 );
		$wgOut->addHTML( $html3 );
		$wgOut->setRobotPolicy('index,follow', 'Main Page');
	}

	private function getSearchHtml() {
		return '<div class="search">
		<form action="/wikiHowTo" id="cse-search-box" _lpchecked="1">
			<div>
				<label for="hp_search" id="hp_search_label"></label>
				<input type="text" id="hp_search" name="search" value="" x-webkit-speech="">
				<input type="submit" value="" class="cse_sa" alt="">
			</div>
		</form>
		</div>';

		/* Google CSE - Not used as of June 2017 - Alberto
		'<div class="search">
			<form action="//cse.google.' .  wfMessage('cse_domain_suffix')->plain() . '/cse" id="cse-search-box" _lpchecked="1">
				<div>
					<input type="hidden" name="cx" value="' . wfMessage('cse_cx')->plain() . '">
					<label for="hp_search" id="hp_search_label">' . wfMessage('wikihow_to_dot')->plain() . '</label>
					<input type="text" id="hp_search" name="q" value="" x-webkit-speech="" ph="' . wfMessage('wikihow_to_dot')->plain() . '">
					<input type="submit" value="" class="cse_sa" alt="">
				</div>
			</form>
			</div>'
		 */
	}

	public static function removeBreadcrumb(&$showBreadcrumb) {
		$showBreadcrumb = false;
		return true;
	}

	/**
	 * NOTE: Much of this code is duplicated in WikihowHomepage.body.php (Alberto - 2018-09)
	 */
	public static function showTopImage() {
		global $wgOut;

		$items = array();

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(WikihowHomepageAdmin::HP_TABLE, array('*'), array('hp_active' => 1), __METHOD__, array('ORDER BY' => 'hp_order'));

		$i = 0;
		foreach ($res as $result) {
			$item = new stdClass();
			$title = Title::newFromID($result->hp_page);
			// Append Google Analytics tracking to slider URLs
			$item->url = $title->getLocalURL() . "?utm_source=wikihow&utm_medium=main_page_carousel&utm_campaign=mobile";
			$item->text = $title->getText() . wfMessage('howto_suffix')->showIfExists();
			$imageTitle = Title::newFromID($result->hp_image);
			if ($imageTitle) {
				$file = wfFindFile($imageTitle->getText());
				if ($file) {
					$item->imagePath = wfGetPad($file->getUrl());
					$item->itemNum = ++$i;
					$items[] = $item;
				}
			}
		}

		Hooks::run( 'WikihowHomepageAfterGetTopItems', array( &$items ) );

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'items' => $items,
			'imagePath' => wfGetPad('/skins/owl/images/home1.jpg'),
		));
		$html = $tmpl->execute('topmobile.tmpl.php');

		$wgOut->addHTML($html);
	}

}
