<?php

/**
 * Helper class to manage article videos from third-party providers
 */
class ExternalVideoProvider {

	private static $supportedArticles = [
		'Apply-Conditional-Formatting-in-Excel' => 1,
		'Build-a-Fire' => 1,
		'Calculate-Interest-Payments' => 1,
		'Calculate-Pi' => 1,
		'Calculate-Square-Meters' => 1,
		'Draw' => 1,
		'Draw-a-Cartoon-Dog' => 1,
		'Draw-a-Cat' => 1,
		'Dye-Hair' => 1,
		'Finger-Knit' => 1,
		'Get-Rid-of-Acne' => 1,
		'Get-Six-Pack-Abs' => 1,
		'Hack' => 1,
		'Hard-Reset-an-iPhone' => 1,
		'Keep-a-Conversation-Going' => 1,
		'Learn-American-Sign-Language' => 1,
		'Make-a-Line-Graph-in-Microsoft-Excel' => 1,
		'Make-a-Pie-Chart-in-Excel' => 1,
		'Make-Buttermilk-from-Milk' => 1,
		'Make-Chocolate' => 1,
		'Make-Money' => 1,
		'Massage-Your-Partner' => 1,
		'Multiply-in-Excel' => 1,
		'Organize-Your-Life' => 1,
		'Paint' => 1,
		'Play-American-Football' => 1,
		'Play-Baseball' => 1,
		'Play-Basketball' => 1,
		'Play-Chess' => 1,
		'Play-Poker' => 1,
		'Play-Soccer' => 1,
		'Play-Tennis' => 1,
		'Post-on-Instagram' => 1,
		'Run-Faster' => 1,
		'Set-Goals' => 1,
		'Shoot-a-Soccer-Ball' => 1,
		'Slow-Cook-a-Roast' => 1,
		'Speak-Professionally-on-the-Phone' => 1,
		'Start-a-Blog' => 1,
		'Teach-Yourself-to-Play-the-Piano' => 1,
		'Use-FaceTime' => 1,
		'Wrap-Wontons' => 1,
	];

	/**
	 * Return the HTML necessary to embed the video in the article page,
	 * or false if no video is available for the article requested.
	 */
	public static function getVideoHtml($articleTitle) {
		if (!isset(ExternalVideoProvider::$supportedArticles[$articleTitle])) {
			return false;
		}
		$tmpl = new EasyTemplate(dirname(__FILE__));
		return $tmpl->execute('ExternalVideo.tmpl.php');
	}

}
