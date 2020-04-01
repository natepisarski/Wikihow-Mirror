<?php

class BibleCitation {

	private static $includes_bible_citation_widget = null;

	private static function pageIncludesBibleCitationWidget(): bool {
		if (!is_null(self::$includes_bible_citation_widget)) return self::$includes_bible_citation_widget;
		$context = RequestContext::getMain();
		$title = $context->getTitle();

		$android_app = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest();

		self::$includes_bible_citation_widget =
			$context->getLanguage()->getCode() == 'en' &&
			$title &&
			$title->getDBKey() == 'Cite-the-Bible' &&
			$title->inNamespace( NS_MAIN ) &&
			Action::getActionName($context) == 'view' &&
			$context->getRequest()->getInt('diff', 0) == 0 &&
			!Misc::isAltDomain() &&
			!GoogleAmp::isAmpMode( $context->getOutput() ) &&
			!$android_app;

		return self::$includes_bible_citation_widget;
	}

	private static function bibleCitationWidget(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/assets' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'header' 					=> wfMessage('bible_citation_header')->text(),
			'error'						=> wfMessage('bible_citation_error')->text(),
			'format' 					=> wfMessage('bible_citation_format')->text(),
			'formats'					=> self::getSelectOptions('format'),
			'title' 					=> wfMessage('bible_citation_title')->text(),
			'optional' 				=> wfMessage('bible_citation_optional')->text(),
			'volume_ph'				=> wfMessage('bible_citation_volume_placeholder')->text(),
			'edition_ph'			=> wfMessage('bible_citation_edition_placeholder')->text(),
			'section' 				=> wfMessage('bible_citation_section')->text(),
			'sections'				=> self::getSelectOptions('section'),
			'publication' 		=> wfMessage('bible_citation_publication')->text(),
			'publisher_ph'		=> wfMessage('bible_citation_publisher_placeholder')->text(),
			'city_ph'					=> wfMessage('bible_citation_city_placeholder')->text(),
			'year_ph'					=> wfMessage('bible_citation_year_placeholder')->text(),
			'submit' 					=> wfMessage('bible_citation_submit')->text(),
			'bible_citation' 	=> $loader->load('bible_citation'),
			'result_message'	=> wfMessage('bible_citation_complete')->text(),
			'copy'						=> wfMessage('bible_citation_copy')->text(),
			'create'					=> wfMessage('bible_citation_create')->text(),
			'edition_tip'			=> wfMessage('bible_citation_error_edition')->text(),
			'desktop_class'		=> Misc::isMobileMode() ? '' : 'desktop'
		];

		return $m->render('bible_citation_widget', $vars);
	}

	private static function getSelectOptions(string $type): array {
		if ($type == 'format')
			$options = wfMessage('bible_citation_format_options')->text();
		elseif ($type == 'section')
			$options = wfMessage('bible_citation_section_options')->text();
		else
			return [];

		$formatted_options = [];

		foreach (explode(',',$options) as $option) {
			$formatted_options[] = [ 'option' => $option ];
		}

		return $formatted_options;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::pageIncludesBibleCitationWidget()) {
			$out->addModules(['ext.wikihow.bible_citation.styles', 'ext.wikihow.bible_citation.scripts']);
		}
	}

	public static function addWidget(OutputPage $out) {
		if (self::pageIncludesBibleCitationWidget()) {
			pq('#intro')->after(self::bibleCitationWidget());
		}
	}
}
