<?php
class MotionToStatic {
	public static function handlePageStatsPost( $request, $user ) {
		$textBox = $request->getVal( 'textbox' );
		$pageId = $request->getVal( 'pageid' );
		$editSummary = $request->getVal( 'editsummary' );

		$type = $request->getVal( 'type' );
		if ( $type == "select" ) {
			$steps = $request->getVal( 'steps' );
			if ( !is_numeric( str_replace( ",", "", $steps ) ) ) {
				$result = "invalid steps:". $steps;
				return $result;
			}
			$steps = explode( ",", $steps );
			$result = self::changeVideosToStatic( $pageId, $steps, $user, $editSummary );
		} elseif ( $type == "removeall" ) {
			$result = self::removeAllMedia( $pageId, $user, $editSummary );
		} elseif ( $type == 'changeall' ) {
			$result = self::changeAllVideosToStatic( $pageId, $user, $editSummary );
		}
		return $result;
	}

    public static function getMotionToStaticHtmlForPageStats() {
        $menuTitle = Html::element( 'p', array( 'id' => 'mts-title' ), 'Motion To Static Tools' );
        $options = '';
        $options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'select' ), 'Select videos to static' );
        $options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'changeall' ), 'All videos to static' );
        $options .= Html::rawElement( 'a', array( 'href' => '#', 'role' => 'menuitem', 'data-type' => 'removeall' ), 'Remove all images and videos' );
        $menuContent = Html::rawElement( 'div', array( 'id'=> 'mts-content', 'class' => 'menu' ), $options );

        $textArea = Html::rawElement( 'textarea', array( 'id'=> 'mts-textarea', 'class' => 'mts-h', 'placeholder' => 'add edit message here' ) );

        $checkBox = Html::rawElement( 'input', array( 'id'=> 'mts-stu-box', 'type' => 'checkbox', 'checked' ) );
        $checkBoxLabel = Html::rawElement( 'label', array(), "Reset Stu" );
        $checkBoxWrap = Html::rawElement( 'div', array( 'id' => 'mts-stu', 'class' => 'mts-h' ), $checkBox . $checkBoxLabel );

        $submit .= Html::rawElement( 'a', array( 'id' => 'mts-submit', 'class' => 'mts-h', 'href' => '#' ), 'Do edit' );
        $cancel .= Html::rawElement( 'a', array( 'id' => 'mts-cancel', 'class' => 'mts-h', 'href' => '#' ), 'Cancel' );
        $menuWrap = Html::rawElement( 'div', array( 'id' => 'motion-to-static' ), $menuTitle . $menuContent );
        return $menuWrap . $type . $textArea . $checkBoxWrap . $submit . $cancel;
    }

	// text - the final text to save on the title
	// title - the title on which we are doing the edit
	// returns result of the doEditContent call
	private static function editContent( $text, $title, $user, $editSummary ) {
		$content = ContentHandler::makeContent( $text, $title );

		// we do not use the EDIT_SUPPRESS_RC flag because that prevents the edit from
		// being auto patrolled
		$editFlags = EDIT_UPDATE | EDIT_MINOR | EDIT_FORCE_BOT;

		$page = WikiPage::factory( $title );
		$result = $page->doEditContent( $content, $editSummary, $editFlags, false, $user);
		return $result;
	}

	private static function getLatestGoodRevisionText( $title ) {
		$gr = GoodRevision::newFromTitle( $title );
		if ( !$gr ) {
			return "";
		}

		$latestGood = $gr->latestGood();
		if ( !$latestGood ) {
			return "";
		}
		$r = Revision::newFromId( $latestGood );
		if ( !$r ) {
			return "";
		}
		return ContentHandler::getContentText( $r->getContent() );
	}

	/*
	* change all videos to static
	*/
	public static function changeAllVideosToStatic( $pageId, $user, $editSummary ) {
		$title = Title::newFromId( $pageId );
		if ( !$title ) {
			return;
		}

		$text = self::getLatestGoodRevisionText( $title );
		// get this for comparison later
		$originalText = $text;

		$text = preg_replace_callback(
			"@\{\{ *whvid\|[^\}]+ *\}\}@",
			function ($matches) {
				$result = $matches[0];
				$templateArgs = explode( "|", $matches[0] );
				$staticImage = '';
				$previewImage = '';
				foreach( $templateArgs as $arg ) {
					if ( substr_count( $arg, '.jpg' ) && !substr_count( $arg, 'preview' ) ) {
						$staticImage = '[[Image:'.$arg."|center]]";
					} elseif ( substr_count( $arg, '.jpg' ) && substr_count( $arg, 'preview' ) ) {
						$previewImage = '[[Image:'.$arg."|center]]";
					}
				}
				// fallback to the preview image
				if ( $previewImage ) {
					$result = $previewImage;
				}
				// but use the static image if we have it
				if ( $staticImage ) {
					$result = $staticImage;
				}
				return $result;
			},
			$text
		);
		$result = '';
		if ( $text != $originalText ) {
			self::editContent( $text, $title, $user, $editSummary );
		} else {
			$result = "no edit was made";
		}
		return $result;
	}

	/*
	* remove all videos and images
	*/
	public static function removeAllMedia( $pageId, $user, $editSummary ) {
		$title = Title::newFromId( $pageId );
		if ( !$title ) {
			return;
		}

		$text = self::getLatestGoodRevisionText( $title );
		// get this for comparison later
		$originalText = $text;

		$text = preg_replace( "@\{\{ *whvid\|[^\}]+ *\}\}@", '', $text);
		$text = preg_replace('@\[\[Image:[^\]]*\]\]@im', '', $text);
		$text = preg_replace('@\{\{largeimage\|[^\}]*\}\}@im', '', $text);
		$result = '';
		if ( $text != $originalText ) {
			self::editContent( $text, $title, $user, $editSummary );
		} else {
			$result = "no edit was made";
		}
		return $result;
	}

	public static function changeVideosToStatic( $pageId, $steps, $user, $editSummary ) {
		$title = Title::newFromId( $pageId );
		if ( !$title ) {
			return;
		}

		$text = self::getLatestGoodRevisionText( $title );
		// get this for comparison later
		$originalText = $text;

		$text = preg_replace_callback(
			"@\{\{ *whvid\|[^\}]+ *\}\}@",
			function ($matches) use ($steps) {
				$result = $matches[0];
				foreach( $steps as $step ) {
					$stepString = 'Step ' . $step .' ';
					$stepStringDot = 'Step ' . $step .'.';
					if ( substr_count( $matches[0], $stepString ) || substr_count( $matches[0], $stepStringDot ) ) {
						$templateArgs = explode( "|", $matches[0] );
						$staticImage = '';
						$previewImage = '';
						foreach( $templateArgs as $arg ) {
							if ( substr_count( $arg, '.jpg' ) && !substr_count( $arg, 'preview' ) ) {
								$staticImage = '[[Image:'.$arg."|center]]";
								//decho("replacing {$matches[0]} with", $result);
							} elseif ( substr_count( $arg, '.jpg' ) && substr_count( $arg, 'preview' ) ) {
								$previewImage = '[[Image:'.$arg."|center]]";
							}
						}
						// fallback to the preview image
						if ( $previewImage ) {
							$result = $previewImage;
						}
						// but use the static image if we have it
						if ( $staticImage ) {
							$result = $staticImage;
						}
					}
				}
				// if we found a preview image but no regular image then use the preview image
				if ( $previewResult && !$result ) {
					$result = $previewResult;
				}
				return $result;
			},
			$text
		);

		$result = '';
		if ( $text != $originalText ) {
			self::editContent( $text, $title, $user, $editSummary );
		} else {
			$result = "no edit was made";
		}
		return $result;
	}
}
