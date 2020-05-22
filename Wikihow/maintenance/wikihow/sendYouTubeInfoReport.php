<?php
/**
 * Send an email about YouTubeInfo
 */

require_once __DIR__ . '/../Maintenance.php';

class SendYouTubeInfoReport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generate and send report about YouTubeInfo';
	}

	public function execute() {
		global $wgIsDevServer;

		$dbr = wfGetDB( DB_REPLICA );
		$report = [ 'warnings' => [], 'errors' => [], 'oks' => 0 ];
		$tags = [
			Misc::YT_WIKIHOW_VIDEOS,
			Misc::YT_GUIDECENTRAL_VIDEOS
		];

		foreach ( $tags as $tag ) {
			$results = AdminYouTubeIds::getAllResults( $tag );
			foreach ( $results as $result ) {
				if ( $result['status'] === 'ok' ) {
					$status = SchemaMarkup::getYouTubeVideoStatus( $result['youtube_id'] );
					if (
						$status['apiResponseStatus'] == 'valid' &&
						(
							stripos( $status['apiCacheStatus'], 'ok' ) === 0 ||
							$status['apiCacheStatus'] == 'expired - refresh pending'
						)
					) {
						$report['oks']++;
					} else {
						$result['emoji'] = '⚠️';
						$result['api_response'] = $status['apiCacheStatus'];
						$result['api_cache'] = $status['apiResponseStatus'];
						$report['warnings'][] = $result;
					}
				} else {
					$result['emoji'] = '❌';
					$result['api_response'] = 'none';
					$result['api_cache'] = 'none';
					$report['errors'][] = $result;
				}
			}
		}

		function getRows( $items, $tdStyle = '' ) {
			$text = '';
			$html = '';
				foreach ( $items as $item ) {
					$text .= "{$item['emoji']} {$item['page_title']} ({$item['page_id']})\n" .
							( array_key_exists( 'youtube_id', $item ) ? "- youtube_id: {$item['youtube_id']}\n" : '' ) .
							"- api_response: {$item['api_response']}\n" .
							"- api_cache: {$item['api_cache']}\n\n";
					$html .= '<tr>' .
							"<td style='{$tdStyle}'>{$item['emoji']}</td>" .
							"<td style='{$tdStyle}'><a href='https://wikihow.com/{$item['page_title']}'>{$item['page_title']} ({$item['page_id']})</a></td>" .
							( array_key_exists( 'youtube_id', $item ) ?
								"<td style='{$tdStyle}'><a href='https://www.youtube.com/watch?v={$item['youtube_id']}'>{$item['youtube_id']}</td>" :
								"<td style='{$tdStyle}'>none</td>" ) .
							"<td style='{$tdStyle}'>{$item['api_response']}</td>" .
							"<td style='{$tdStyle}'>{$item['api_cache']}</td>" .
						'</tr>';
				}
			return [ 'text' => $text, 'html' => $html ];
		}

		// Send report
		$tdStyle = 'padding: 0.5em 0.75em;';
		$thStyle = 'text-align: left;background-color:#fafafa;';
		$today = date( 'Y-m-d' );
		$oksCount = $report['oks'];
		$warningsCount = count( $report['warnings'] );
		$errorsCount = count( $report['errors'] );
		$warningsRows = getRows( $report['warnings'], $tdStyle );
		$errorsRows = getRows( $report['errors'], $tdStyle );
		$legend = [
			'text' => "Legend for status labels:\n\n" .
				"api_response:\n" .
				"- valid: Info is in cached and valid\n" .
				"- none: Info not in cache\n" .
				"- invalid: Info is invalid\n" .
				"- unparsable: Info cannot be parsed\n" .
				"- empty: Info has no items\n" .
				"api_cache\n" .
				"- ok: Info is in cache and fresh\n" .
				"- not-found: Info not in cache\n" .
				"- expired: Cache has not been refreshed on time\n" .
				"- refresh pending: Cache will be refreshed within 24 hours\n" .
				"- retry pending: A retry will occur within 24 hours\n",
			'html' => "<small><h3>Legend</h3>" .
				"<h4>api_response:</h4>" .
					"<ul>" .
						"<li><strong>valid</strong>: Info is in cached and valid</li>" .
						"<li><strong>none</strong>: Info not in cache</li>" .
						"<li><strong>invalid</strong>: Info is invalid</li>" .
						"<li><strong>unparsable</strong>: Info cannot be parsed</li>" .
						"<li><strong>empty</strong>: Info has no items</li>" .
					"</ul>" .
				"<h4>api_cache</h4>" .
					"<ul>" .
						"<li><strong>ok</strong>: Info is in cache and fresh</li>" .
						"<li><strong>not-found</strong>: Info not in cache</li>" .
						"<li><strong>expired</strong>: Cache has not been refreshed on time</li>" .
						"<li><strong>refresh pending</strong>: A scheduled refresh within 24 hours</li>" .
						"<li><strong>retry pending</strong>: A retry will occur within 24 hours</li>" .
					"</ul></small>"
		];
		$body = [
			'text' => "YouTubeInfo Report for {$today}\n" .
				"{$oksCount} oks, {$warningsCount} warnings, {$errorsCount} errors\n\n" .
				( $errorsCount ? "Errors:\n\n" . $errorsRows['text'] . "\n\n" : '' ) .
				( $warningsCount ? "Warnings:\n\n" . $warningsRows['text'] . "\n\n" : '' ) .
				( $warningsCount + $errorsCount ? $legend['text'] : "👍 Lookin' good." ),
			'html' => "<h2>YouTubeInfo Report for {$today}</h2>" .
				"<p>{$oksCount} oks, {$warningsCount} warnings, {$errorsCount} errors</p>" .
				(
					$warningsCount + $errorsCount ?
					"<table style='border: solid 1px #eee;border-radius:4px;'>" .
							"<tr>" .
								"<th style='{$tdStyle} {$thStyle}'></th>" .
								"<th style='{$tdStyle} {$thStyle}'>page</th>" .
								"<th style='{$tdStyle} {$thStyle}'>youtube_id</th>" .
								"<th style='{$tdStyle} {$thStyle}'>api_response</th>" .
								"<th style='{$tdStyle} {$thStyle}'>api_cache</th>" .
							"</tr>" .
							$errorsRows['html'] .
							$warningsRows['html'] .
						'</table>' .
						$legend['html'] :
					"👍 Lookin' good."
				)
		];

		// Report here
		$subject = "YouTubeInfo Report";
		$from = new MailAddress("reports@wikihow.com");
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			$to = new MailAddress("trevor@wikihow.com");
		} else {
			$to = new MailAddress("trevor@wikihow.com, elizabeth@wikihow.com");
		}

		print "Sending email to to:$to\nfrom:$from\nsubject:$subject\n{$body['text']}\n ";
		UserMailer::send( $to, $from, $subject, $body );
	}
}

$maintClass = 'SendYouTubeInfoReport';
require_once RUN_MAINTENANCE_IF_MAIN;
