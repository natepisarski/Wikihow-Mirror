<?php
/**
 * Process jobs in image_transfer_job table.
 *
 * In dry run mode, it will only simulate and it won't update database tables
 * or make real edits. The queries and edits will be shown in the standard output.
 *
 * Syntax:
 * scripts/whrun --user=apache -- php prod/maintenance/wikihow/runImageTransfer.php --start [live]
 */

require_once dirname( __DIR__ ) . '/Maintenance.php';

/**
 * @ingroup Maintenance
 */
class CopyArticleImagesMaintenance extends Maintenance {

	var $fetchCache = [];

	function __construct() {
		parent::__construct();
		$this->addDescription( 'Copy article images from English to international pages.' );
		// Useful for testing, do not run in production
		$this->addOption( 'purge', 'Purge all pending jobs' );
		// The initial invocation
		$this->addOption( 'start', 'Begin processing, omit when fetching' );
		// Subsequent invocation, called by 'start', data is passed via stdin/stdout
		$this->addOption( 'process', 'Process a page' );
		// Subsequent invocation, called by 'process', data is passed via option/stdout
		$this->addOption( 'fetch', 'Fetch content for a given article ID, use alone' );
		// Used together with start and process, will cause actual database changes
		$this->addOption( 'live', 'Make changes to database, omit for dry-run' );
		// Used together with start process
		$this->addOption( 'color', 'Use color console output' );
	}

	/**
	 * Execute maintenance script.
	 */
	public function execute() {
		if ( $this->hasOption( 'purge' ) ) {
			$this->onPurge();
		} else if ( $this->hasOption( 'start' ) ) {
			$this->onStart();
		} else if ( $this->hasOption( 'fetch' ) ) {
			$this->onFetch();
		} else if ( $this->hasOption( 'process' ) ) {
			$this->onProcess();
		} else {
			$this->maybeHelp( /* force = */ true );
		}
	}

	private function process( $transfer, $from, $to, $dryRun ) {
		global $IP;

		$command = MediaWiki\Shell\Shell::command( [
			dirname( $IP ) . '/scripts/whrun',
			'--user=apache',
			"--lang={$transfer->toLang}",
			'--',
			"php " . __FILE__ . " --process"
		] );
		$command->input( json_encode( [
			'transfer' => $transfer->toRow(),
			'from' => $from,
			'to' => $to,
			'dryRun' => $dryRun
		] ) );
 		$result = $command->execute();
		if ( $result->getExitCode() > 0 ) {
			$error = trim( $result->getStderr() );
			$this->output( "  CopyArticleImages error: failed to process: {$error}\n" );
			return [ 'error' => "failed to process: {$error}" ];
		} else {
			return json_decode( $result->getStdout(), JSON_OBJECT_AS_ARRAY );
		}
	}

	private function fetch( $lang, $articleId ) {
		global $IP;

		$command = MediaWiki\Shell\Shell::command( [
			dirname( $IP ) . '/scripts/whrun',
			'--user=apache',
			"--lang={$lang}",
			'--',
			"php " . __FILE__ . " --fetch={$articleId}"
		] );
 		$result = $command->execute();
		if ( $result->getExitCode() > 0 ) {
			$error = trim( $result->getStderr() );
			$this->output( "  CopyArticleImages error: failed to fetch: {$error}\n" );
			return [ 'error' => "failed to fetch: {$error}" ];
		} else {
			return json_decode( $result->getStdout(), JSON_OBJECT_AS_ARRAY );
		}
	}

	private function fetchAndCache( $lang, $articleId ) {
		if ( !isset( $this->fetchCache[$lang][$articleId] ) ) {
			$this->fetchCache[$lang][$articleId] = $this->fetch( $lang, $articleId );
		}
		return $this->fetchCache[$lang][$articleId];
	}

	private function onPurge() {
		$this->output( "Purging pending jobs..\n" );
		ImageTransfer::purgePending();
		$this->output( "Done.\n" );
	}

	private function onStart() {
		global $wgUser, $wgLanguageCode, $wgActiveLanguages, $wgLanguageNames;

		$wgUser = User::newFromName( 'AlfredoBot' );
		$dryRun = !$this->hasOption( 'live' );
		$useColor = !$this->hasOption( 'color' );
		$this->output(
			$this->colorize(
				"Copying article images" . ( $dryRun ? ' (dry-run)' : '' ) . "...\n",
				'task',
				$useColor
			)
		);

		$errors = [];
		$successes = [];
		$creators = [];
		$langIds = [];

		foreach ( $wgActiveLanguages as $lang ) {
			$transfers = ImageTransfer::getUpdatesForLang( $lang );

			if ( count( $transfers ) ) {
				$fromLangName = isset( $wgLanguageNames[$wgLanguageCode] ) ?
					$wgLanguageNames[$wgLanguageCode] : "({$wgLanguageCode})";
				$toLangName = isset( $wgLanguageNames[$lang] ) ?
					$wgLanguageNames[$lang] : "({$lang})";
				$this->output(
					$this->colorize(
						"⚙ Processing transfers from {$fromLangName} ({$wgLanguageCode}) " .
							"to {$toLangName} ({$lang})\n",
						'task-details',
						$useColor
					)
				);
			}

			foreach ( $transfers as $transfer ) {
				$langIds[] = [ 'lang' => $transfer->fromLang, 'id' => $transfer->fromAID ];
				$langIds[] = [ 'lang' => $transfer->toLang, 'id' => $transfer->toAID ];
				$creators[] = $transfer->creator;

				$from = $this->fetchAndCache( $transfer->fromLang, $transfer->fromAID );
				if ( isset( $from['error'] ) ) {
					// $this->output(
					// 	$this->colorize(
					// 		"✗ {$transfer->fromLang}/{$transfer->fromAID} → " .
					// 			"{$transfer->toLang}/{$transfer->toAID}\n",
					// 		'error',
					// 		$useColor
					// 	) .
					// 	$this->colorize(
					// 		"  Error: {$from['error']}\n",
					// 		'error-details',
					// 		$useColor
					// 	)
					// );
					$errors[$transfer->creator][] = $transfer;
					$transfer->reportError( $from['error'], $dryRun, false );
					continue;
				}
				$to = $this->fetch( $transfer->toLang, $transfer->toAID );
				if ( isset( $to['error'] ) ) {
					// $this->output(
					// 	$this->colorize(
					// 		"✗ {$transfer->fromLang}/({$transfer->fromAID}) → " .
					// 			"{$transfer->toLang}/({$transfer->toAID})\n",
					// 		'error',
					// 		$useColor
					// 	) .
					// 	$this->colorize(
					// 		"  Error: {$to['error']}\n",
					// 		'error-details',
					// 		$useColor
					// 	)
					// );
					$errors[$transfer->creator][] = $transfer;
					$transfer->reportError( $to['error'], $dryRun, false );
					continue;
				}

				$result = $this->process( $transfer, $from, $to, $dryRun );
				if ( isset( $result['error'] ) ) {
					$error = trim( $result['error'] );
					$errors[$transfer->creator][] = $transfer;
					$transfer->reportError( $result['error'], $dryRun, false );
					$toTitle = isset( $to['title'] ) ? $to['title'] : '(not found)';
					$this->output(
						$this->colorize(
							"✗ {$transfer->fromLang}/{$transfer->fromAID} ({$from['title']}) → " .
								"{$transfer->toLang}/{$transfer->toAID} ({$to['title']})\n",
							'error',
							$useColor
						) .
						$this->colorize(
							"  - Error: {$error}\n",
							'error-details',
							$useColor
						)
					);
				} else {
					$message = trim( $result['message'] );
					$successes[$transfer->creator][] = $transfer;
					$this->output(
						$this->colorize(
							"✔ {$transfer->fromLang}/{$transfer->fromAID} ({$from['title']}) → " .
								"{$transfer->toLang}/{$transfer->toAID} ({$to['title']})\n",
							'success',
							$useColor
						) .
						$this->colorize(
							"  - Success: {$message}\n",
							'success-details',
							$useColor
						)
					);
				}
			}
		}

		// Build a list of pages collated by lang and page_id
		$pages = [];
		foreach ( Misc::getPagesFromLangIds( $langIds ) as $page ) {
			$pages[$page['lang']][$page['page_id']] = $page;
		}

		// Get a list of failed URLs collated by creator
		$errorURLs = ImageTransfer::getErrorURLsByCreator( $wgLanguageCode, $dryRun );

		// Add in creators of failed URLs
		$creators = array_merge( $creators, array_keys( $errorURLs ) );

		// Remove duplicates
		$creators = array_unique( $creators );

		// Email users
		if ( count( $errors ) || count( $successes ) || count( $errorURLs ) ) {
			$this->output( $this->colorize( "Emailing transfer creators...\n", 'task', $useColor ) );
			$this->emailUsers( $errors, $successes, $creators, $pages, $errorURLs );
		} else {
			$this->output( $this->colorize( "No transfers, skipping emails.\n", 'task', $useColor ) );
		}
	}

	private function onProcess() {
		global $wgUser;

		$wgUser = User::newFromName( 'AlfredoBot' );

		$input = json_decode( file_get_contents( 'php://stdin' ), JSON_OBJECT_AS_ARRAY );
		$row = (object)$input['transfer'];
		$transfer = ImageTransfer::newFromRow( $row );

		ob_start();
		$status = $transfer->addImages( $input['from'], $input['to'], $input['dryRun'] );
		$output = ob_get_clean();

		if ( !$status ) {
			$this->fatalError( $output );
			return;
		}
		$this->output( json_encode( [ 'message' => $output ] ) );
	}

	private function onFetch() {
		global $wgContLang;

		$articleId = (int)$this->getOption( 'fetch' );
		$code = $wgContLang->getCode();

		$dbr = wfGetDB( DB_REPLICA );

		if ( !is_numeric( $articleId ) ) {
			$this->fatalError( "Invalid or missing article ID, likely a software bug" );
			return;
		}
		$rev = Revision::loadFromPageId( $dbr, $articleId );
		if ( !$rev ) {
			$this->fatalError( "Could not load revision, article may have been deleted" );
			return;
		}
		$title = Title::newFromId( $articleId );
		if ( !$title ) {
			$this->fatalError( "Could not load title, article may have been deleted" );
			return;
		}
		$txt = ContentHandler::getContentText( $rev->getContent() );
		$intro = Wikitext::getIntro( $txt );
		$text = Wikitext::getStepsSection( $txt, true );
		$lines = preg_split( "/\n/", $text[0] );
		$text = '';

		// Sometimes there are problems with the wikitext that cause the steps section
		// to not be findable, such as mismatching heading marks (e.g. '==Steps===') or
		// not using the right section name (e.g. '==Steps to take==')
		if ( implode( $lines ) === '' ) {
			$this->fatalError( 'Steps cannot be found, look for mistakes in the wikitext' );
			return;
		}

		// We remove extra lines technically in the 'steps' section, but which don't
		// actually contain steps
		// Find the last line starting with a '#'
		$lastLine = 0;
		$n = 0;
		foreach ( $lines as $line ) {
			if ( $line[0] == '#') {
				$lastLine = $n;
			}
			$n++;
		}

		// Truncate lines after the last line with a '#'
		$n = 0;
		foreach ( $lines as $line ) {
			if ( $n > $lastLine ) {
				break;
			}
			if ( $n != 0 ) {
				$text .= "\n";
			}
			$text .= $line;
			$n++;
		}
		if ( !strlen( $text ) ) {
			$this->fatalError( "Failed parsing steps, look for mistakes in the wikitext" );
			return;
		}
		$this->output( json_encode( [
			'title' => $title->getDBKey(),
			'steps' => $text,
			'intro' => $intro,
			'altImageTags' => [ $wgContLang->getNSText( NS_IMAGE ) ]
		] ) );
	}

	private function emailUsers( $errors, $successes, $creators, $pages, $errorURLs ) {
		// Get a URL from a page object
		function getUrl( $page ) {
			return $page && isset( $page['page_title'] ) ?
				( Misc::getLangBaseURL( $page['lang'] ) . '/' . $page['page_title'] ) : '';
		}

		// Send email to each person who entered images about what happened with them
		foreach ( $creators as $creator ) {
			$user = User::newFromName( $creator );
			$email = $user->getEmail();
			if ( $email == null || $email == '' ) {
				$this->output(
					"  Skipping email to {$creator} (no email address)\n"
				);
				continue;
			}

			// Build message
			$csv = [];
			$divCss = 'border: solid 1px #ddd;border-radius: 4px;overflow: hidden;';
			$tableCss = 'width:100%;border-collapse: collapse;';
			$thCss = 'text-align: left;padding: 0.5em 0.75em;;background-color:#ecebe8;';
			$tdCss = 'padding: 0.5em 0.75em;';
			$errorCss = 'color: red;';
			$successCss = 'color: green;';
			$tdAltCss = [
				'odd' => 'background-color:#ffffff;',
				'even' => 'background-color:#fafafa;'
			];
			$alt = 'even';
			$msg = "<div style='{$divCss}'><table style='{$tableCss}'>" .
				'<thead>' .
					'<tr>' .
						"<th style='{$thCss}'>Submitted URL</td>" .
						"<th style='{$thCss}'>Translated URL</td>" .
						"<th style='{$thCss}'>Status</td>" .
					'</tr>' .
				"</thead>\n";
			if ( isset( $errorURLs[$creator] ) ) {
				foreach ( $errorURLs[$creator] as $url ) {
					$csv[] = [ $url, '', 'URL Not Found' ];
					$alt = $alt === 'even' ? 'odd' : 'even';
					$msg .= "<tr style='{$errorCss}''>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'><a href='{$url}'>{$url}</a></td>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'></td>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'>URL Not Found</td>" .
					"</tr>\n";
				}
			}
			if ( isset( $errors[$creator] ) ) {
				foreach ( $errors[$creator] as $transfer ) {
					$fromPage = $pages[$transfer->fromLang][$transfer->fromAID];
					$toPage = $pages[$transfer->toLang][$transfer->toAID];
					$fromUrl = getUrl( $fromPage );
					$toUrl = getUrl( $toPage );
					$statusText = $transfer->error ? ucfirst( $transfer->error ) : 'Error';
					$statusHtml = nl2br( $statusText );
					$csv[] = [ $fromUrl, $toUrl, $statusText ];
					$alt = $alt === 'even' ? 'odd' : 'even';
					$msg .= "<tr style='{$errorCss}''>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'><a href='{$fromUrl}'>{$fromUrl}</a></td>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'><a href='{$toUrl}'>{$toUrl}</a></td>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'>{$statusHtml}</td>" .
					"</tr>\n";
				}
			}
			if ( isset( $successes[$creator] ) ) {
				foreach ( $successes[$creator] as $transfer ) {
					$fromPage = $pages[$transfer->fromLang][$transfer->fromAID];
					$toPage = $pages[$transfer->toLang][$transfer->toAID];
					$fromUrl = getUrl( $fromPage );
					$toUrl = getUrl( $toPage );
					$statusText = "Success";
					$csv[] = [ $fromUrl, $toUrl, $statusText ];
					$alt = $alt === 'even' ? 'odd' : 'even';
					$msg .= "<tr style='{$successCss}''>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'><a href='{$fromUrl}'>{$fromUrl}</a></td>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'><a href='{$toUrl}'>{$toUrl}</a></td>" .
						"<td style='{$tdCss}{$tdAltCss[$alt]}'>{$statusText}</td>" .
					"</tr>\n";
				}
			}
			$msg .= "</table></div>";

			// Generate a unique boundary hash for the multipart e-mail body
			$boundaryHash = md5( date( 'r', time() ) );
			$attachment = chunk_split( base64_encode( array2csv( $csv ) ) );
			$timestamp = date( 'Ymd' ) . 'T' . date( 'his' );

			$body = <<<BODY
--PHP-mixed-$boundaryHash
Content-Type: multipart/alternative; boundary="PHP-alt-$boundaryHash"

--PHP-alt-$boundaryHash
Content-Type: text/html; charset="UTF-8"

$msg

--PHP-alt-$boundaryHash--

--PHP-mixed-$boundaryHash
Content-Type: text/comma-separated-values; name="CopyArticleImages-Report-{$timestamp}.csv"
Content-Transfer-Encoding: base64
Content-Disposition: attachment

$attachment

--PHP-mixed-$boundaryHash--
BODY;

			$from = new MailAddress( 'alerts@wikihow.com' );
			$to = new MailAddress( $email );
			$subject = 'CopyArticleImages Jobs Complete';
			$opts = [ 'contentType' => 'multipart/mixed; boundary="PHP-mixed-' . $boundaryHash . '"' ];
			$this->output( "  Sending email to {$email}\n" );
			echo UserMailer::send( $to, $from, $subject, $body, $opts ) . "\n";
		}
	}

	private function colorize( $text, $colorName, $bypass ) {
		$colors = [
			'success' => '1;32',
			'success-details' => '0;32',
			'error' => '1;31',
			'error-details' => '0;31',
			'task' => '1;36',
			'task-details' => '0;36',
		];
		if ( $bypass || !isset( $colors[$colorName] ) ) {
			return $text;
		}
		return "\033[{$colors[$colorName]}m{$text}\033[0m";
	}
}

function array2csv( $data, $delimiter = ',', $enclosure = '"', $escape_char = "\\" ) {
    $f = fopen( 'php://memory', 'r+' );
    foreach ( $data as $item ) {
        fputcsv( $f, $item, $delimiter, $enclosure, $escape_char );
    }
    rewind( $f );
    return stream_get_contents( $f );
}

$maintClass = CopyArticleImagesMaintenance::class;
require RUN_MAINTENANCE_IF_MAIN;
