<?php

class WikihowMobileHomepage extends Article {
	var $faStream;

	const FA_STARTING_CHUNKS = 6;

	const SINGLE_WIDTH = 163; // (article_shell width - 2*article_inner padding - 3*SINGLE_SPACING)/4
	const SINGLE_HEIGHT = 119; //should be .73*SINGLE_WIDTH
	const SINGLE_SPACING = 16;

	function __construct( Title $title, $oldId = null ) {
		parent::__construct($title, $oldId);
	}

	function view() {
		$out = $this->getContext()->getOutput();
		IOSHelper::addIOSAppBannerTag();

		$out->setHTMLTitle('wikiHow - '.wfMessage('main_title')->text());

		/* This will not work if any dependencies are added to the mobile homepage module.
		 * Perhaps an alternative would be creating a dependency module that loads the styles
		 * first to ensure the slider JS loads afterward.
		 */
		$out->addModuleStyles(['zzz.mobile.wikihow.homepage.styles']);
		$out->addModules(['zzz.mobile.wikihow.homepage.scripts']);

		$faViewer = new FaViewer($this->getContext());
		$this->faStream = new WikihowArticleStream($faViewer, $this->getContext());
		$html = $this->faStream->getChunks(self::FA_STARTING_CHUNKS, self::SINGLE_WIDTH, self::SINGLE_SPACING, self::SINGLE_HEIGHT, WikihowArticleStream::MOBILE);

		$html2 = "";
		$html3 = "";

		Hooks::run( 'WikihowHomepageFAContainerHtml', array( &$html, &$html2, &$html3 ) );

		$clearIt = Html::rawElement( 'div', ['class' => 'clearall']);

		$container = Html::rawElement( 'div', ['id' => 'article_blocks_container'], $html.$html2.$html3.$clearIt );
		$out->addHTML( $container );

		$out->setRobotPolicy('index,follow', 'Main Page');
	}

	private static function getSearchHtml() {
		return '<div class="search">
		<form action="/wikiHowTo" id="cse-search-box" _lpchecked="1">
			<div>
				<label for="hp_search" id="hp_search_label"></label>
				<input type="text" id="hp_search" name="search" value="" x-webkit-speech="">
				<input type="submit" value="" class="cse_sa" alt="">
			</div>
		</form>
		</div>';
	}

	public static function removeBreadcrumb(&$showBreadcrumb) {
		$showBreadcrumb = false;
		return true;
	}

	/**
	 * NOTE: Much of this code is duplicated in WikihowHomepage.body.php (Alberto - 2018-09)
	 */
	public static function showTopImage() {
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
		$tmpl->set_vars([
			'items' => $items,
			'imagePath' => wfGetPad('/skins/owl/images/home1.jpg'),
			'search_box' => self::getSearchHtml()
		]);
		$html = $tmpl->execute('topmobile.tmpl.php');

		return $html;
	}

}
