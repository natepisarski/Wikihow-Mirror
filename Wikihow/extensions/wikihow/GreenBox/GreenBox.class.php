<?php

class GreenBox {

	const GREENBOX_TEMPLATE_PREFIX = 'greenbox:';
	const GREENBOX_EXPERT_TEMPLATE_PREFIX = 'expertgreenbox:';

	const GREENBOX_EXPERT_STAFF = 'staff';

	const GREENBOX_EXPERT_SHORT_MARKER = 'SHORT';

	//here are the official flavors of our green boxes
	public static $green_box_types = [
		'green_box',							//uses {{greenbox}}
		'green_box_expert',				//uses {{expertgreenbox}}
		'green_box_expert_qa',		//uses {{expertgreenbox}}
		'green_box_expert_short'	//uses {{expertgreenbox}}
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

		if ($expert_id == self::GREENBOX_EXPERT_STAFF) {
			$expert_data = new VerifyData;
			$expert_data->imagePath = '/skins/WikiHow/wH-initials_152x152.png';
			$expert_data->name = wfMessage('green_box_staff_name')->text();
			$expert_data->blurb = wfMessage('green_box_staff_blurb')->text();
			$expert_data->initials = 'wH';
			$expert_data->hoverBlurb = wfMessage('sp_staff_reviewed_hover')->text();
			$expert_label = wfMessage('green_box_staff_label')->text();
		}
		else {
			$expert_data = !empty($expert_id) ? VerifyData::getVerifierInfoById($expert_id) : null;
			$expert_label = wfMessage('green_box_expert_label')->text();
		}

		if (empty($expert_data)) return [''];

		$vars = [
			'green_box_tab_label' => wfMessage('green_box_tab_label')->text(),
			'content' => self::formatBoxContents($parser, $wikitext),
			'content_2' => self::formatBoxContents($parser, $wikitext_2),
			'expert_display' => self::expertDisplayHtml($expert_data),
			'expert_label' => $expert_label,
			// 'expert_dialog' => self::expertDialog($expert_data),
			'expert_name' => $expert_data->name,
			'expert_title' => $expert_data->blurb,
			'questioner' => wfMessage('green_box_questioner')->text(),
			'mobile_class' => Misc::isMobileMode() ? 'mobile' : ''
		];

		if (empty($wikitext_2))
			$template = 'green_box_expert';
		elseif ($wikitext_2 == self::GREENBOX_EXPERT_SHORT_MARKER)
			$template = 'green_box_expert_short';
		else
			$template = 'green_box_expert_qa';

		$output = $m->render($template, $vars);
		$output = str_replace("\n",'',$output); //needs to be one line
		return [ trim($output), 'isHTML' => true ];
	}

	private static function formatBoxContents(Parser $parser, string $wikitext = ''): string {
		if (empty($wikitext)) return '';

		//== headline == >>> <span class="green_box_headline">headline</span>
		$wikitext = preg_replace('/==\s?(.*?)\s?==/', '<span class="green_box_headline">$1</span>', $wikitext);

		//bullets (*)
		$wikitext = preg_replace('/<br><br>\*(.*?)<br><br>/', '<p class="green_box_bullet">$1</p>', $wikitext);
		$wikitext = preg_replace('/\*/', '</p><p class="green_box_bullet">', $wikitext);

		//wrap in <p> tags
		$wikitext = '<p>'.preg_replace('/<br><br>/s', '</p><p>', $wikitext).'</p>';

		$html = $parser->recursiveTagParse($wikitext);
		return $html;
	}

	/**
	 * can return:
	 * - the expert's initials
	 * - the expert's image in an <img> tag
	 */
	private static function expertDisplayHtml(VerifyData $expert_data): string {
		$image_path = $expert_data->imagePath;
		if (empty($image_path)) return $expert_data->initials;
		return Html::rawElement('img', ['src' => $image_path, 'alt' => $expert_data->name]);
	}

	private static function expertDialog(VerifyData $expert_data): string {
		if ($expert_data->name == wfMessage('qa_staff_editor')->text()) return '';

		$dialog = $expert_data->hoverBlurb;

		$link = self::expertInternalLinkHTML($expert_data->name, wfMessage('sp_learn_more')->text());
		if ($link) $dialog .= ' ' . $link;

		return $dialog;
	}

	private static function expertInternalLinkHTML(string $name, string $link_text): string {
		$html = '';

		if ($name != wfMessage('qa_staff_editor')->text()) {
			$expert_link = ArticleReviewers::getLinkByVerifierName($name);
			if ($expert_link) $html = Html::rawElement('a', ['href' => $expert_link], $link_text);
		}

		return $html;
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
		// $out->addModules(['ext.wikihow.green_box','ext.wikihow.green_box.scripts']);
		$out->addModules(['ext.wikihow.green_box']);
	}

	//this uses the phpQuery object
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		$amp = GoogleAmp::isAmpMode($out);

		//move greenboxes to their proper places
		//--------------------------------
		if (pq('.green_box')->length) {
			foreach(pq('.green_box') as $green_box) {
				$step = pq($green_box)->parents('.step');
				pq($step)->after(pq($green_box));

				if ($amp) {
					//make amp-img
					$gb_img = pq($green_box)->find('.green_box_person_circle img');
					$amp_img = GoogleAmp::makeAmpImgElement(pq($gb_img)->attr('src'), 45, 45);

					//no dialog hover; make image a link
					$link = pq($green_box)->find('.green_box_expert_dialog a')->attr('href');
					if ($link) {
						//make relative because this can be a desktop link
						$link = preg_replace('/https?:\/\/www.wikihow.com/i','',$link);
						$amp_img = Html::rawElement('a', [ 'href' => $link, 'target' => '_blank' ], $amp_img);
					}

					pq($gb_img)->replaceWith($amp_img);
				}
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
