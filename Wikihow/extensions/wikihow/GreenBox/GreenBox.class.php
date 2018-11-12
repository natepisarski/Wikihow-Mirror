<?php

class GreenBox {

	const GREENBOX_TEMPLATE_PREFIX = 'greenbox:';
	const GREENBOX_EXPERT_TEMPLATE_PREFIX = 'expertgreenbox:';

	//here are the official flavors of our green boxes
	public static $green_box_types = [
		'green_box',					//uses {{greenbox}}
		'green_box_expert',		//uses {{expertgreenbox}}
		'green_box_expert_qa'	//uses {{expertgreenbox}}
	];

	public static function renderBox(Parser $parser, string $wikitext = ''): array {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'content' => self::formatBoxContents($parser, $wikitext),
			'mobile_class' => Misc::isMobileMode() ? 'mobile' : ''
		];

		$output = $m->render('green_box', $vars);
		return [ trim($output), 'isHTML' => true ];
	}

	public static function renderExpertBox(Parser $parser, string $expert_id = '',
		string $wikitext = '', string $wikitext_2 = ''): array
	{
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$expert_data = !empty($expert_id) ? VerifyData::getVerifierInfoById($expert_id) : null;
		if (empty($expert_data)) return [''];

		$amp = GoogleAmp::isAmpMode( RequestContext::getMain()->getOutput() );

		$vars = [
			'green_box_tab_label' => wfMessage('green_box_tab_label')->text(),
			'content' => self::formatBoxContents($parser, $wikitext),
			'content_2' => self::formatBoxContents($parser, $wikitext_2),
			'expert_display' => self::expertDisplayHtml($expert_data, $amp),
			'expert_label' => wfMessage('green_box_expert_label')->text(),
			'questioner' => wfMessage('green_box_questioner')->text(),
			'mobile_class' => Misc::isMobileMode() ? 'mobile' : '',
			'amp' => $amp
		];

		$template = empty($wikitext_2) ? 'green_box_expert' : 'green_box_expert_qa';

		$output = $m->render($template, $vars);
		$output = str_replace("\n",'',$output); //needs to be one line
		return [ trim($output), 'isHTML' => true ];
	}

	private static function formatBoxContents(Parser $parser, string $wikitext = ''): string {
		if (empty($wikitext)) return '';

		//== headline == >>> <span class="green_box_headline">headline</span>
		$wikitext = preg_replace('/==\s?(.*?)\s?==/', '<span class="green_box_headline">$1</span>', $wikitext);

		//wrap in <p> tags
		$wikitext = '<p>'.preg_replace('/<br><br>/s', '</p><p>', $wikitext).'</p>';

		$html = $parser->recursiveTagParse($wikitext);
		return $html;
	}

	/**
	 * can return:
	 * - the expert's initials
	 * - the expert's image in an <img> tag
	 * - the expert's image in an <amp-img> tag (for AMP, obv)
	 */
	private static function expertDisplayHtml(VerifyData $expert_data, bool $amp): string {
		$image_path = $expert_data->imagePath;
		if (empty($image_path)) return $expert_data->initials;

		if ($amp)
			$img = GoogleAmp::makeAmpImgElement($image_path, 45, 45);
		else
			$img = Html::rawElement('img', ['src' => $image_path, 'alt' => $expert_data->name]);

		return $img;
	}

	private static function unauthorizedExpertGreenBoxEdits(WikiPage $wikiPage, Content $new_content, User $user): bool {
		if (GreenBoxEditTool::authorizedUser($user)) return false;

		$edited = false;
		$expert_magic = MagicWord::get('expertgreenbox');
		$old_content = $wikiPage->getContent();

		$old_content_has_expert_green_box = empty($old_content) ? false : $old_content->matchMagicWord($expert_magic);
		$new_content_has_expert_green_box = empty($new_content) ? false : $new_content->matchMagicWord($expert_magic);

		if ($old_content_has_expert_green_box || $new_content_has_expert_green_box) {
			$old_wikitext = empty($old_content) ? '' : ContentHandler::getContentText($old_content);
			$new_wikitext = empty($new_content) ? '' : ContentHandler::getContentText($new_content);

			preg_match_all('/{{'.self::GREENBOX_EXPERT_TEMPLATE_PREFIX.'.*?}}/is', $old_wikitext, $old_expertGBs);
			preg_match_all('/{{'.self::GREENBOX_EXPERT_TEMPLATE_PREFIX.'.*?}}/is', $new_wikitext, $new_expertGBs);

			if (count($old_expertGBs[0]) != count($new_expertGBs[0])) {
				$edited = true;
			}
			else {
				foreach ($old_expertGBs[0] as $key => $val) {
					if (strcmp($val, $new_expertGBs[0][$key])) {
						$edited = true;
						break;
					}
				}
			}
		}

		return $edited;
	}

	public static function onParserFirstCallInit(Parser &$parser) {
		$parser->setFunctionHook( 'greenbox', 'GreenBox::renderBox', SFH_NO_HASH );
		$parser->setFunctionHook( 'expertgreenbox', 'GreenBox::renderExpertBox', SFH_NO_HASH );
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

		$show_green_box_cta = $out->getLanguage()->getCode() == 'en' &&
													$action == 'view' &&
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

	public static function onPageContentSave(WikiPage $wikiPage, User $user, Content $content,
			string $summary, int $minor, $null1, $null2, int $flags, Status $status = null)
	{
		if (self::unauthorizedExpertGreenBoxEdits($wikiPage, $content, $user)) {
			$status->fatal('green_box_article_edit_expert');
			return false;
		}
	}
}
