<?php

/**
 * Table Schema
 *
CREATE TABLE image_transfer_job(
	itj_from_lang varchar(2) NOT NULL,
	itj_from_aid int NOT NULL,
	itj_to_lang varchar(2) NOT NULL,
	itj_to_aid int NOT NULL,
	itj_creator varchar(255) NOT NULL,
	itj_time_started varchar(12) NOT NULL, -- Time when it was added to queue
	itj_time_finished varchar(12) NULL, -- Time when the article is wikiphotoed
	itj_error text,
	itj_warnings text
	primary key(itj_from_lang, itj_from_aid, itj_to_lang, itj_to_aid)
)
CREATE TABLE image_transfer_invalids(
	iti_from_url varchar(255) NOT NULL,
	iti_to_lang varchar(2) NOT NULL,
	iti_creator varchar(255) NOT NULL,
	iti_time_started varchar(12) NOT NULL,
	iti_time_finished varchar(12) NULL,
	primary key(iti_from_url, iti_to_lang)
);
 */

/**
 * Class for tracking image transfers for taking images from English to international articles
 */
class ImageTransfer {
	// Page we are getting the steps images from
	public $fromLang;
	public $fromAID;
	public $fromTitle;
	// Page where we are putting the images
	public $toLang;
	public $toAID;
	public $toTitle;
	// User who began the image transfer request
	public $creator;
	// Time when the image transfer was started
	public $timeStarted;
	// Time when the image transfer is finished
	public $timeFinished;
	// Error if applicable
	public $error;
	// Warnings if applicable
	public $warnings = [];

	// Database that has the image_transfer_job table
	const DB_NAME = WH_DATABASE_NAME;
	// Table used for storing the image transfer info
	const TABLE_NAME = 'image_transfer_job';

	/**
	 * Get a regex for matching images including foreign image tags.
	 *
	 * @param {string[]} $altImageTags Language specific image tags
	 * @param {bool} $matchSpaces Match spaces before and after image tags
	 * @return {string} Regular expression that matches images
	 */
	public static function getImageRegex( $altImageTags = [], $matchSpaces = false ) {
		$patterns = [
			'\[\[ *Image:[^\]]* *\]\]',
			'\{\{ *largeimage\|[^\}]* *\}\}',
			'\{\{ *whvid\|[^\}]* *\}\}'
		];
		foreach ( $altImageTags as $altImageTag ) {
			$patterns[] = "\[\[ *" . preg_quote( $altImageTag ) . ":[^\]]* *\]\]";
		}
		return $matchSpaces ?
			'@\s*(' . implode( '|', $patterns ) . ')\s*@im' :
			'@(' . implode( '|', $patterns ) . ')@im';
	}

	/**
	 * Purge pending jobs.
	 *
	 * @return {bool} Pending jobs were successfully deleted
	 */
	public static function purgePending() {
		global $wgDBname;

		$dbw = wfGetDB( DB_MASTER );
		return $dbw->delete(
			self::DB_NAME . '.' . self::TABLE_NAME,
			[ 'itj_time_finished' => null ],
			__METHOD__
		);
	}

	/**
	 * Insert into the table.
	 *
	 * @throws {Exception} if run on the destination database
	 * @return {bool} Row was successfully inserted
	 */
	public function insert() {
		global $wgDBname;

		if ( $wgDBname != self::DB_NAME ) {
			throw new Exception(
				"ImageTransfer::insert must be run from the source database ({self::DB_NAME}) " .
				"not the target {$wgDBname}"
			);
		}
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->upsert(
			// Table to insert into
			self::DB_NAME . '.' . self::TABLE_NAME,
			// Row to insert
			[
				'itj_from_lang' => $this->fromLang,
				'itj_from_aid' => $this->fromAID,
				'itj_to_lang' => $this->toLang,
				'itj_to_aid' => $this->toAID,
				'itj_creator' => $this->creator,
				'itj_time_started' => $this->timeStarted,
			],
			// Unique indexes
			[ 'itj_from_lang', 'itj_from_aid', 'itj_to_lang', 'itj_to_aid' ],
			// Updates perform if row with matching unique indexes already exists
			[
				'itj_creator' => $this->creator,
				'itj_time_started' => $this->timeStarted,
				'itj_error' => null,
				'itj_warnings' => null,
				'itj_time_finished' => null,
			],
			__METHOD__
		);
	}

	/**
	 * Report error transferring image.
	 *
	 * @param {string} $error Error message
	 * @param {bool} [$dryRun] Bypass database writes
	 * @return {bool} Error was successfully reported
	 */
	public function reportError( $error, $dryRun = true ) {
		$this->error = $error;

		echo $this->error . "\n";

		if ( !$dryRun ) {
			$dbw = wfGetDB( DB_MASTER );
			return $dbw->update(
				// Table to update
				self::DB_NAME . "." . self::TABLE_NAME,
				// Values to update
				[ 'itj_error' => $this->error ],
				// Conditions to find matching row
				[
					'itj_from_lang' => $this->fromLang,
					'itj_from_aid' => $this->fromAID,
					'itj_to_lang' => $this->toLang,
					'itj_to_aid' => $this->toAID,
				],
				__METHOD__
			);
		}
		return true;
	}

	/**
	 * Report success transferring image.
	 *
	 * @param {bool} [$dryRun] Bypass database writes
	 * @return {bool} Success was successfully reported
	 */
	public function reportSuccess( $dryRun = true ) {
		$this->timeFinished = wfTimestampNow();

		echo "success";
		if ( count( $this->warnings ) ) {
			echo " with warnings, (images were still transfered where possible):" .
				"\n  -" . implode( "\n  - ", $this->warnings );
		}
		echo "\n";

		if ( !$dryRun ) {
			$dbw = wfGetDB( DB_MASTER );
			return $dbw->update(
				// Table to update
				self::DB_NAME . "." . self::TABLE_NAME,
				// Values to update
				[
					'itj_time_finished' => $this->timeFinished,
					'itj_warnings' => implode( ',', $this->warnings )
				],
				// Conditions to find matching row
				[
					'itj_from_lang' => $this->fromLang,
					'itj_from_aid' => $this->fromAID,
					'itj_to_lang' => $this->toLang,
					'itj_to_aid' => $this->toAID,
				],
				__METHOD__
			);
		}
		return true;
	}

	/**
	 * Report a warning transferring image.
	 *
	 * Will be included in success report.
	 *
	 * @param {string} $warning Warning message
	 */
	public function reportWarning( $warning ) {
		$this->warnings[] = $warning;
	}

	/**
	 * Convert to database row object.
	 *
	 * @return [Object] Image transfer row object
	 */
	public function toRow() {
		return (object)[
			'itj_from_lang' => $this->fromLang,
			'itj_from_aid' => $this->fromAID,
			'itj_to_lang' => $this->toLang,
			'itj_to_aid' => $this->toAID,
			'itj_creator' => $this->creator,
			'itj_time_started' => $this->timeStarted,
			'itj_time_finished' => $this->timeFinished,
			'itj_error' => $this->error,
			'from_title' => $this->fromTitle,
			'to_title' => $this->toTitle,
		];
	}

	/**
	 * Create new ImageTransfer from a database row object.
	 *
	 * @param {Object} $row Database row object
	 * @return {ImageTransfer} ImageTransfer object
	 */
	public static function newFromRow( $row ) {
		$it = new ImageTransfer();

		$it->fromLang = $row->itj_from_lang;
		$it->fromAID = (int)$row->itj_from_aid;
		$it->toLang = $row->itj_to_lang;
		$it->toAID = (int)$row->itj_to_aid;
		$it->creator = $row->itj_creator;
		$it->timeStarted = $row->itj_time_started;
		$it->timeFinished = $row->itj_time_finished;
		$it->error = $row->itj_error;
		$it->fromTitle = $row->from_title;
		$it->toTitle = $row->to_title;

		return $it;
	}

	/**
	 * Get list of articles to update for a given language.
	 *
	 * @param {string} $lang Language to get updates for
	 * @return {ImageTransfer[]} Pending ImageTransfer objects
	 */
	public static function getUpdatesForLang( $lang ) {
		$fromDB = self::DB_NAME;
		$toDB = "wikidb_{$lang}";
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[
				$fromDB . '.' . self::TABLE_NAME,
				"{$fromDB}.page",
				"{$toDB}.page",
			],
			[
				'*',
				'from_title' => "{$fromDB}.page.page_title",
				'to_title' => "{$toDB}.page.page_title",
			],
			[
				'itj_to_lang' => $lang,
				'itj_error' => null,
				'itj_time_finished' => null,
			],
			__METHOD__,
			[],
			[
				"{$fromDB}.page" => [ 'LEFT JOIN', [ 'page_id=itj_from_aid' ] ],
				"{$toDB}.page" => [ 'LEFT JOIN', [ "{$toDB}.page.page_id=itj_to_aid" ] ],
			]
		);
		$its = array();
		foreach ( $res as $row ) {
			$its[] = self::newFromRow( $row );
		}
		return $its;
	}

	/**
	 * Process ImageTransfer.
	 *
	 * This does the actual transfer. It must be run on the target language database.
	 *
	 * $from and $to are arrays with `steps`, `intro` and `altImageTags` elements.
	 * @see maintenance/wikihow/runImageTransfer.php
	 *
	 * @param {array} $from Page images are being transferred from
	 * @param {array} $to Page images are being transferred to
	 * @param {bool} $dryRun Bypass database writes
	 */
	public function addImages( $from, $to, $dryRun = true ) {
		global $wgLanguageCode;

		if (
			!isset( $this->fromLang ) ||
			!isset( $this->fromAID ) ||
			!isset( $this->toLang ) ||
			!isset( $this->toAID )
		) {
			$this->reportError( 'missing article information', $dryRun );
			return false;
		}
		if ( $this->toAID == 0 ) {
			$this->reportError( 'missing translation link', $dryRun );
			return false;
		}
		if ( !$from ) {
			$this->reportError( "missing {$this->fromLang} content", $dryRun );
			return false;
		}
		if ( !isset( $from['steps'] ) || $from['steps'] == '' ) {
			$this->reportError( "missing {$this->fromLang} step content", $dryRun );
			return false;
		}
		if ( !$to ) {
			$this->reportError( "missing {$this->toLang} content",  $dryRun );
			return false;
		}
		if ( !isset( $to['steps'] ) || $to['steps'] == '' ) {
			$this->reportError( "missing {$this->toLang} content",  $dryRun );
			return false;
		}
		$altImageTags = array_merge( $from['altImageTags'], $to['altImageTags'] );
		if (
			self::haveSameImages( $from['intro'], $to['intro'] ) &&
			self::haveSameImages( $from['steps'], $to['steps'], $altImageTags )
		) {
			$this->reportError( 'articles have the same images', $dryRun );
			return false;
		}

		$token = Misc::genRandomString();
		$templates = self::getImageTemplatesFromSteps( $from['steps'], $token );
		if ( !$templates ) {
			$this->reportError(
				'unable to generate image template from article, ' .
					'no steps images or steps section not found',
				$dryRun
			);
			return false;
		}
		$badSubsteps = 0;
		foreach ( $templates as $template ) {
			if ( $template == '' ) {
				$badSubsteps++;
			}
		}
		if ( $badSubsteps > 0 ) {
			$this->reportWarning(
				"there are {$badSubsteps} steps or substeps, where image templates are unparsable"
			);
		}

		$introTemplate = self::getIntroTemplate( $from['intro'], $token );
		if ( $introTemplate == '' ) {
			$this->reportWarning( "unable to generate template for {$this->fromLang} intro" );
		}

		// Check for HTML structure
		if ( self::hasHTMLSteps( $from['steps'] ) ) {
			$this->reportError(
				"unable to parse {$this->fromLang} steps, steps are in HTML format",
				$dryRun
			);
			return false;
		}
		if ( self::hasHTMLSteps( $to['steps'] ) ) {
			$this->reportError(
				"unable to parse {$this->toLang} steps, steps are in HTML format", $dryRun
			);
			return false;
		}

		// Check structure signatures to ensure section/step/sub-step patterns match
		if ( self::getSignature( $from['steps'] ) !== self::getSignature( $to['steps'] ) ) {
			$this->reportError( 'step structures do not match', $dryRun );
			// Disable verbose output
			// echo "  - from: " . self::getSignature( $from['steps'] ) . "\n";
			// echo "  - to:   " . self::getSignature( $to['steps'] ) . "\n";
			return false;
		}

		$newToSteps = self::replaceStepsImages(
			$to['steps'], $templates, $token, $to['altImageTags']
		);
		if ( !$newToSteps ) {
			$this->reportError( 'step counts do not match', $dryRun );
			return false;
		}
		if ( preg_match( '@<\s*center\s*>|<\s*font\s*>@mi', $from['intro'] ) ) {
			$this->reportWarning(
				"font and center tag are unsupported in {$this->fromLang} intro"
			);
		}
		if ( preg_match( '@<\s*center\s*>|<\s*font\s*>@mi', $from['steps'] ) ) {
			$this->reportWarning(
				"font and center tag are unsupported in {$this->fromLang} steps"
			);
		}
		$newIntro = self::replaceIntroImages(
			$to['intro'], $introTemplate, $token, $to['altImageTags']
		);

		$title = Title::newFromId( $this->toAID );
		if ( !$title || !$title->exists() ) {
			$this->reportError( "title not found {$this->toLang}:{$this->toAID}", $dryRun );
			return false;
		}
		$text = ContentHandler::getContentText( Revision::newFromTitle( $title )->getContent() );
		$initialText = $text;
		if ( preg_match( '@' . preg_quote( $to['steps'],'@' ) .  '@', $text ) ) {
			$text = preg_replace_callback(
				'@\s*' . preg_quote( $to['steps'],  '@' ) . '\s*@',
				function ( $matches ) use ( $newToSteps ) {
					return "\n" . $newToSteps . "\n";
				},
				$text
			);

			// Ensure we have a real intro before doing replacement
			if ( strlen( $to['intro'] ) > 10 ) {
				$text = preg_replace_callback(
					'@\s*' . preg_quote( $to['intro'], '@' ) . '\s*@',
					function ( $matches ) use ( $newIntro ) {
						return $newIntro . "\n";
					},
					$text
				);
			}
			if ( $text == $initialText ) {
				$this->reportError( 'transfer does not change article', $dryRun );
				return false;
			}
			if ( $text ) {
				$dbkey = $title->getDBKey();
				$wikiPage = WikiPage::factory( $title );
				$content = ContentHandler::makeContent( $text, $title );
				if ( !$dryRun ) {
					$wikiPage->doEditContent( $content, wfMessage( 'alfredo-editsummary' ) );
				}

				$this->reportSuccess( $dryRun );
				return true;
			} else {
				$this->reportError( 'empty article', $dryRun );
				return false;
			}
		}
		$this->reportError(
			"could not find steps section in {$this->fromLang} article, " .
				"possibly steps section is too big to process",
			$dryRun
		);
		return false;
	}

	/**
	 * Get template for intro.
	 *
	 * @param {string} $intro Intro content
	 * @param {string} $token Random string to use as a placeholder in the template
	 * @return {string} Intro template
	 */
	private static function getIntroTemplate( $intro, $token ) {
		$emptyIntro = preg_replace( self::getImageRegex(), '', $intro, -1, $count );

		// We ignore wiki-templates besides {{largeimage}} when creating our template pattern
		$numMatches = preg_match_all(
			'@(\s*\{\{[^}]*\}\}\s*|\s*\[\[Category:[^\]]+\]\])@im',
			$emptyIntro,
			$matches
		);
		if ( $numMatches ) {
			for ( $i = 1; $i < count( $matches ); $i++ ) {
				$intro = str_replace( $matches[$i], '', $intro );
				$emptyIntro = str_replace( $matches[$i], '', $emptyIntro );
			}
		}

		$emptyIntro = preg_replace( '@<\s*br[\/]\s*>|^#\*?@im', '', $emptyIntro );

		$template = str_replace( $emptyIntro, $token, $intro );

		// Put back replaced text inside images, because it is likely the image name
		// Also, remove any caption from image
		$template = preg_replace_callback(
			self::getImageRegex(),
			function ( $matches ) use ( $token ) {
				// Remove caption only from image tags, but not largeimage template
				if ( preg_match( '@\[\[\s*Image\s*@', $matches[0] ) ) {
					$matches[0] = Wikitext::removeImageCaption( $matches[0] );
				}
				// NOTE: In previous version, replacement was $emptyStep, but that was not a defined
				// variable in this scope, so str_replace fell-back to empty string
				return str_replace( $token, '', $matches[0] );
			},
			$template
		);

		if (
			!preg_match( '@' . preg_quote($token) . '@', $template ) ||
			( strlen( $template ) < 6 )
		) {
			return '';
		} else {
			return $template;
		}
	}

	/**
	 * Get template for steps.
	 *
	 * @param {string} $stepsText Steps content
	 * @param {string} $token Random string to use as a placeholder in the template
	 * @return {array|bool} List of templates for each step or false if no images found. If we can't
	 *	  parse out a template for a step, we return an empty string for that template
	 */
	private static function getImageTemplatesFromSteps( $stepsText, $token ) {
		// Ignore interwiki links in steps text
		$stepsText = preg_replace( '@\[\[ *[[:alpha:]][[:alpha:]] *:[^\]]+\]\]@', '', $stepsText );

		$stepTemplates = [];
		$numImages = 0;
		$stepNum = 1;
		$sections = preg_split( "@[\r\n]\s*===@", $stepsText );
		$emptyStep = '';

		foreach ( $sections as $section ) {
			$steps = preg_split( "@[\r\n]+#@", $section );
			if ( count( $steps ) > 1 ) {
				for ( $i = 1; $i < count( $steps ); $i++ ) {
					$haveImage = false;
					// Ignore sub-sections for calculating steps info
					$steps[$i] = preg_replace( '@[\r\n]===[^=]+===@', '', $steps[$i] );
					// Add in back in '#' and remove newline characters
					$steps[$i] = '#' . preg_replace( '@[\r\n]@', '', $steps[$i] );
					// Extract step without images,  associated formatting, and count images
					$emptyStep = preg_replace( self::getImageRegex(), '', $steps[$i], -1, $count );
					$numImages += $count;
					$emptyStep = preg_replace( '@<\s*br[\/]?\s*>|^#\*?@im', '', $emptyStep );

					//Remove leading and trailing whitespace
					$emptyStep = preg_replace( '@^\s+@', '', $emptyStep );
					$emptyStep = preg_replace( '@\s+$@', '', $emptyStep );
					if ( $emptyStep != '' ) {
						//Change step to token
						$template = str_replace( $emptyStep, $token, $steps[$i] );

						// Put back replaced text inside images, because it is likely the image name
						$template = preg_replace_callback(
							self::getImageRegex(),
							function ( $matches ) use( $token, $emptyStep ) {
								// Remove thumbnail text, and mark if we have found an image
								if ( preg_match('@\[\[\s*Image\s*@', $matches[0] ) ) {
									$haveImage = true;
									$matches[0] = Wikitext::removeImageCaption( $matches[0] );
								}
								return str_replace( $token, $emptyStep, $matches[0] );
							},
							$template
						);

						if ( $template == $steps[$i] ) {
							if ( $haveImage ) {
								$stepTemplates[$stepNum] = '';
							} else {
								$stepTemplates[$stepNum] = $token;
							}
						} else {
							$stepTemplates[$stepNum] = $template;
						}
					} else {
						$stepTemplates[$stepNum] = $token;
					}
					$stepNum++;
				}
			}
		}
		if ( $numImages == 0 ) {
			return false;
		}
		// Check for finished step, which was created from intro image
		if ( preg_match( '@^Finished\.@', $emptyStep ) ) {
			$stepTemplates[$stepNum - 1] = str_replace(
				$token,
				self::getFinishedToken( $token ),
				$stepTemplates[$stepNum - 1]
			);
		}
		return $stepTemplates;
	}

	/**
	 * Make a special token for the finished step based off our token.
	 *
	 * @return {string} Finshed-step token
	 */
	private static function getFinishedToken( $token ) {
		return substr( $token, 0, 5 ) . 'finished' . substr( $token, 5 );
	}

	/**
	 * Replace images and formatting in the intro.
	 *
	 * @param {string} $introText Intro content
	 * @param {string} $introTemplate Intro template
	 * @param {string} $token Template token
	 * @param {string[]} $altImageTags List of alternative image tag names
	 * @return {string} Intro content with the template applied
	 */
	private static function replaceIntroImages( $introText, $introTemplate, $token, $altImageTags ) {
		$introText = preg_replace( self::getImageRegex( $altImageTags, true ), '', $introText );
		$introText = preg_replace( '@<\s*br[\/]?\s*>@im','', $introText );
		//Remove extra leading and trailing spaces
		$introText = preg_replace( '@== ([^\s]+) ==\s+@', "== \\1 ==\n", $introText );
		$introText = preg_replace( '@[\s]+$@', '\n', $introText );
		$introText = rtrim( $introText );
		if ( $introTemplate == '' ) {
			return $introText;
		} else {
			$intro = str_replace( $token, $introText, $introTemplate );
			return $intro;
		}
	}

	/**
	 * Replace the images in steps according to a defined template.
	 *
	 * @param {string} $stepsText Steps content
	 * @param {string} $stepTemplates Steps templates
	 * @param {string} $token Template token
	 * @param {string[]} $altImageTags List of alternative image tag names
	 * @return {string|false} Steps content with the template applied, false if unsuccessful
	 */
	private static function replaceStepsImages( $stepsText, $stepTemplates, $token, $altImageTags ) {
		$steps = preg_split( '@[\r\n]+#@m', $stepsText );
		//Special token for finished last step, which came from intro
		$finishedToken = self::getFinishedToken( $token );
		$addFinishedStep = false;
		$origStepsOnly = self::getStepsOnly( $steps, '*' );
		$templStepsOnly = self::getStepsOnly( $stepTemplates, '#*' );

		if ( ( count( $origStepsOnly ) - 1 ) != count( $templStepsOnly ) ) {
			if (
				count( $origStepsOnly )  == count( $templStepsOnly ) &&
				preg_match( '@' . preg_quote( $finishedToken, '@' ) . '@', end( $stepTemplates ) )
			) {
				$addFinishedStep = true;
			} else {
				return false;
			}
		}

		$sections = preg_split( '@[\r\n]\s*===@', $stepsText );
		$tmplIdx = 0;

		$txt = '';

		$sectionNum = 0;
		foreach ( $sections as $section ) {
			$steps = preg_split( '@[\r\n]+#@m', $section );
			// Clean up newline characters, add back === and add headings
			// Remove images from before steps
			$steps[0] = preg_replace( self::getImageRegex( $altImageTags, false ), '', $steps[0] );
			$steps[0] = preg_replace( '@^\s+@', '', $steps[0] );
			$steps[0] = rtrim( $steps[0] );

			if ( $sectionNum != 0 ) {
				$steps[0] = preg_replace( '@(===+)\s*@', "$1\n", $steps[0] );
				$txt .= "\n===" . $steps[0] . "\n";
			} else {
				$steps[0] = preg_replace( '@^\s+@', '', $steps[0] );
				$txt .= "\n" . $steps[0] . "\n";
			}

			if ( count( $steps ) > 1 ) {
				for ( $i = 1; $i < count( $steps ); $i++ ) {
					$steps[$i] = '#' . rtrim( $steps[$i] );

					if ( substr( $steps[$i], 0, 2 ) !== '#*' ) {
						// Skip substeps: '#*...'
						// If we have a template to image this step, and it is going to add
						// additional images, apply it
						if (
							$templStepsOnly[$tmplIdx] != '' &&
							!self::haveSameImages( $templStepsOnly[$tmplIdx], $steps[$i] )
						) {
							$step = preg_replace(
								self::getImageRegex( $altImageTags ), '', $steps[$i]
							);
							$step = preg_replace( '@<\s*br[\/]?\s*>|^#\*?@im', '', $step );
							$step = preg_replace( '@[\r\n]*@im','',$step );
							$step = preg_replace( '@(#[*]?)\s*(.+)\s*$@', '\\1 \\2', $step );
							$steps[$i] = str_replace( $token, $step, $templStepsOnly[$tmplIdx] );
							$steps[$i] = str_replace( $finishedToken, $step, $steps[$i] );
						}
						$tmplIdx++;
					}

					$txt .= "\n" . $steps[$i];
				}
			}
			$sectionNum++;
		}
		if ( $addFinishedStep ) {
			$txt .=  "\n" .
				str_replace( $finishedToken, wfMessage( 'finished' ), end( $stepTemplates ) );
		}

		return $txt;
	}

	/**
	 * Extract the steps from an array containing steps and substeps.
	 *
	 * @param {array} $items List of steps with substeps
	 * @param {string} $substepPrefix Prefix for substeps
	 * @return {array} List of top-level steps
	 */
	private static function getStepsOnly( array $items, string $substepPrefix ) : array {
		$prefixLength = strlen( $substepPrefix );
		$steps = [];
		foreach ( $items as $item ) {
			if ( substr( $item, 0, $prefixLength ) !== $substepPrefix ) {
				$steps[] = $item;
			}
		}
		return $steps;
	}

	/**
	 * Check if content contains steps written in HTML instead of wikitext.
	 *
	 * @param {array} $text Wikitext content
	 * @return {bool} Wikitext contains HTML steps
	 */
	private static function hasHTMLSteps( string $text ) : bool {
		$sections = preg_split( '@[\r\n]\s*===[^\r\n=]+===\s*[\r\n]\s*@', $text );
		foreach ( $sections as $section ) {
			// Section starts with an HTML list
			if ( substr( $section, 0, 4 ) === '<ol>' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate a structure signature, including sections, steps and substeps.
	 *
	 * @param {array} $text Wikitext content
	 * @return {string} Structure signature
	 */
	private static function getSignature( string $text ) : string {
		$sections = preg_split( '@[\r\n]\s*===@', $text );
		$signature = [];
		foreach ( $sections as $section ) {
			$signature[] = "=";
			$steps = preg_split( '@[\r\n]+#@m', $section );
			foreach ( $steps as $step ) {
				if ( $step[0] === '*' ) {
					$signature[] = "*";
				} else {
					$signature[] = "#";
				}
			}
		}
		return implode( '', $signature );
	}

	/**
	 * Check if wikitext has images in it.
	 *
	 * @param {string} $txt Wikitext to check
	 * @param {string[]} $altImageTags List of alternative image tag names
	 * @return {bool} Wikitext contains images
	 */
	public static function hasImages( $txt, $altImageTags = [] ) {
		return preg_match_all( self::getImageRegex( $altImageTags ), $txt, $dummy ) > 0;
	}

	/**
	 * Check if two bits of wikitext have the same images.
	 *
	 * @param {string} $a First wikitext to test
	 * @param {string} $b Second wikitext to test
	 * @param {string[]} $altImageTags List of alternative image tag names
	 * @return {bool} Images in $a and $b are the same
	 */
	public static function haveSameImages( $a, $b, $altImageTags = [] ) {
		preg_match_all( self::getImageRegex( $altImageTags ), $a, $aMatches );
		preg_match_all( self::getImageRegex( $altImageTags ), $b, $bMatches );
		if ( count( $aMatches ) != count( $bMatches ) ) {
			return false;
		}
		for ( $aI = 0; $aI < count( $aMatches ); $aI++ ) {
			if ( is_array( $aMatches[$aI] ) ) {
				if ( count( $aMatches[$aI] ) != count( $bMatches[$aI] ) ) {
					return false;
				}
				for ( $bI = 0; $bI < count( $aMatches[$aI] ); $bI++ ) {

					$aMatches[$aI][$bI] = self::removeImageCaption( $aMatches[$aI][$bI] );
					$bMatches[$aI][$bI] = self::removeImageCaption( $bMatches[$aI][$bI] );

					if ( $aMatches[$aI][$bI] != $bMatches[$aI][$bI] ) {
						return false;
					}
				}
			} else {
				$aMatches[$aI][$bI] = self::removeImageCaption( $aMatches[$aI][$bI] );
				$bMatches[$aI][$bI] = self::removeImageCaption( $bMatches[$aI][$bI] );
				if ( $aMatches[$aI] != $bMatches[$aI] ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Wrapper for Wikitext::removeImageCaption which bypasses for whvid templates.
	 *
	 * @param {string} $img Image wikitext
	 * @return {string} Image wikitext without caption
	 */
	static function removeImageCaption( $img ) {
		if ( substr( $img, 0, 8 ) === '{{whvid|' ) {
			return $img;
		}
		return Wikitext::removeImageCaption( $img );
	}

	/**
	 * Add bad URLs to database.
	 */
	static public function addBadURL( $url, $lang, $error ) {
		global $wgUser;

		$dbw = wfGetDB( DB_MASTER );
		return $dbw->upsert(
			// Table to insert into
			self::DB_NAME . '.image_transfer_invalids',
			// Row to insert
			[
				'iti_from_url' => $url,
				'iti_to_lang' => $lang,
				'iti_creator' => $wgUser->getName(),
				'iti_time_started' => wfTimestampNow()
			],
			// Unique indexes
			[ 'iti_from_url', 'iti_to_lang' ],
			// Updates perform if row with matching unique indexes already exists
			[
				'iti_time_finished' => null,
			],
			__METHOD__
		);
	}

	/**
	 * Get a list of bad URLs entered for a given language
	 * If not a dry run, we will update this to only email once
	 *
	 * @param {string} $lang Language to get URLs for
	 * @param {bool} [$dryRun] Bypass database writes
	 * @return [string[][]] List of URLs collated by creator name
	 */
	public static function getErrorURLsByCreator( $lang, $dryRun ) {
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			// Table to select from
			self::DB_NAME . '.image_transfer_invalids',
			// Columns to select
			[ 'iti_from_url', 'iti_creator' ],
			// Conditions for rows to include
			[
				'iti_to_lang' => $lang,
				'iti_time_finished' => null
			],
			__METHOD__
		);

		$urls = [];
		$result = [];
		foreach ( $res as $row ) {
			$urls[] = $row->iti_from_url;
			$result[$row->iti_creator][] = $row->iti_from_url;
		}

		if ( !empty( $urls ) && !$dryRun ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->update(
				// Tables to update
				self::DB_NAME . '.image_transfer_invalids',
				// Values to update
				[ 'iti_time_finished' => wfTimestampNow() ],
				// Conditions to find matching row
				[
					'iti_to_lang' => $lang,
					'iti_from_url in (' . $dbw->makeList( $urls ) . ')'
				],
				__METHOD__
			);
		}
		return $result;
	}
}
