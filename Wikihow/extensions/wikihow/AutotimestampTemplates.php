<?php

if ( ! defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['other'][] = array(
	'name' => 'AutotimestampTemplate',
	'author' => 'Travis Derouin',
	'description' => 'Provides a way of automatically adding a timestamp to a template.',
);

$wgHooks['PageContentSave'][] = 'wfAutotimestamp';

function wfAutotimestamp(&$wikiPage, &$user, &$content, $summary, $minor, $watch, $sectionanchor, $flags) {
	$text = ContentHandler::getContentText( $content );
	$oldtext = $text;
	if (strpos($text, "{{") !== false) {
		$t1 = preg_replace('/\<nowiki\>.*<\/nowiki>/', '', $text);
		preg_match_all('/{{[^}]*}}/im', $t1, $matches);
		$templates = explode( ' ', strtolower(wfMessage('templates_needing_autotimestamps')->text()) );
		$templates = array_flip($templates);
		foreach ($matches[0] as $m) {
			$mm = preg_replace('/\|[^}]*/', '', $m);
			$mm = preg_replace('/[}{]/', '', $mm);
			if (isset($templates[strtolower($mm)])) {
				if (strpos($m, "date=") === false) {
					$m1 = str_replace("}}", "|date=" . date('Y-m-d') . "}}", $m);
					$text = str_replace($m, $m1, $text);
				} else {
					preg_match('/date=(.*)}}/',$m,$mmatches);
					$mmm = $mmatches[1];
					if ($mmm !== date('Y-m-d',strtotime($mmm)))
						$text = str_replace($mmm,date('Y-m-d',strtotime($mmm)),$text);
				}
			} else {
				//echo "wouldn't substitute on $m<br/>";
			}
		}
	}
	if ($text != $oldtext) {
		$content = ContentHandler::makeContent( $text, $wikiPage->getTitle() );
	}
	return true;
}
