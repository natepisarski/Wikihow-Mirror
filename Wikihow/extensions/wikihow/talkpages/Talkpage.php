<?php
$wgExtensionCredits['TalkPageFormatter'][] = array(
	'path' => __FILE__,
	'name' => 'TalkPageFormatter',
	'description' => 'Makes talk and discussion page date timestamps look pretty.',
	'author' => 'Lojjik Braughler'
);

$wgResourceModules['ext.wikihow.talkpages'] = array(
	'scripts' => array(
		'talkpages.js'
	),
	'dependencies' => array(
		'wikihow.common.jquery.dateformat'),
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/talkpages'
);

$wgHooks['BeforePageDisplay'][] = 'TalkPageFormatter::addTalkPageScripts';

class TalkPageFormatter {

	public static function addTalkPageScripts( &$out, &$skin ) {
		$title = RequestContext::getMain()->getTitle();
		if ( $title->isTalkPage() ) {
			$out->addModules( 'ext.wikihow.talkpages' );
		}
	}

/**
 * Use this function for creating any comments to be posted on a talk or discussion page
 * @param $user
 * @param $comment
 * @param $forKudos - whether this is being posted on a kudos page
 * @return string $formattedComment - the full wikitext markup of the comment
 */
	public static function createComment( User $user, $comment, $forKudos = false, $kudosTitle = null, $showFooter = true ) {
		$name = $user->getName();
		$realName = $user->getRealName();

		if ( empty( $realName ) ) {
			$realName = $name;
		}
		$date = wfTimestamp( TS_ISO_8601 );

		if ( $forKudos ) {
			$headerTemplate = "\n\n{{kudos_header|%s|%s|%s|date=%s}}\n";
			$formattedHeader = sprintf( $headerTemplate, $name, $realName, $kudosTitle->getFullText(), $date );
		} else {
			$headerTemplate = "\n\n{{comment_header|%s|%s|date=%s}}\n";
			$formattedHeader = sprintf( $headerTemplate, $name, $realName, $date );
		}

		if ($showFooter) {
			$footerTemplate = "\n{{comment_footer|%s|%s}}\n\n";
			$formattedFooter = sprintf( $footerTemplate, $name, $realName );
		}
		else {
			$footerTemplate = "\n{{comment_nofooter}}\n\n";
			$formattedFooter = sprintf( $footerTemplate );
		}

		$formattedComment = $formattedHeader . $comment . $formattedFooter;

		return $formattedComment;
	}
}
