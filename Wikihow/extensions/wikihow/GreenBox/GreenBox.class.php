<?php

class GreenBox {

	const GREENBOX_TEMPLATE_PREFIX = 'greenbox:';

	public static function renderBox(Parser $parser, string $wikitext = ''): array {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'text' => self::formatBoxContents($wikitext),
			'mobile_class' => Misc::isMobileMode() ? 'mobile' : ''
		];

		$output = $m->render('green_box', $vars);
		return [ $output ];
	}

	private static function formatBoxContents(string $wikitext): string {
		//== headline == >>> <span class="green_box_headline">headline</span>
		$wikitext = preg_replace('/==\s?(.*?)\s?==/', '<span class="green_box_headline">$1</span>', $wikitext);

		//wrap in <p> tags
		$wikitext = '<p>'.preg_replace('/<br><br>/s', '</p><p>', $wikitext).'</p>';

		return $wikitext;
	}

	public static function onParserFirstCallInit(Parser &$parser) {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			$parser->setFunctionHook( 'greenbox', 'GreenBox::renderBox', SFH_NO_HASH );
		}
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$out->addModules('ext.wikihow.green_box');
	}

	//this uses the phpQuery object
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		//move greenboxes to their proper places
		//--------------------------------
		if (pq('.green_box')->length) {
			foreach(pq('.green_box') as $green_box) {
				$step = pq($green_box)->parents('.step');
				pq($step)->after(pq($green_box));
			}
		}

		//add the green box edit links (for authorized users)
		//--------------------------------
		$action = $out->getRequest()->getText('action', 'view');
		$diff_num = $out->getRequest()->getVal('diff', '');
		$title = $out->getTitle();
		$article_page = !empty($title) ? $title->inNamespace(NS_MAIN) : false;

		$show_green_box_cta = $action == 'view' &&
													empty($diff_num) &&
													$article_page &&
													!Misc::isMobileMode() &&
													GreenBoxEditTool::authorizedUser($out->getUser());

		if ($show_green_box_cta) {
			$out->addModules('ext.wikihow.green_box_cta');

			$green_box_cta = Html::element(
				'a',
				[
					'href' => '#',
					'class' => 'green_box_cta'
				],
				wfMessage('green_box_cta_text')->text()
			);

			foreach (pq('.steps_list_2 li .step') as $step) {
				pq($step)->append($green_box_cta);
			}
		}
	}
}