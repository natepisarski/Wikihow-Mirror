<?php

$wgHooks['AddTopEmbedJavascript'][] = 'DeferImages::getJavascriptPaths';

class DeferImages {

	const SCRIPT_DIR = '/extensions/wikihow/load_images';
	const PLACEHOLDER = '/extensions/wikihow/load_images/images/defer_placeholder_white.gif';
	const IMG_SELECTOR = '.mwimg a.image img,img.defer';

    // this is used while we still use the old defer code
	const NEW_DEFER_SELECTOR = '.mwimg img.scrolldefer,.mwimg video.scrolldefer';

    // same as new defer selector but also includes mwimg..
	const SCROLL_LOAD_SELECTOR = '.mwimg:not(.floatright, .tcenter) a.image:not(.mwimg-caption-image) img';
	const BACKUP_DEFER_SELECTOR = '.mwimg.floatright a.image img,.mwimg.tcenter a.image img';
	const ANCHOR_SELECTOR = '.mwimg a.image';
	const GLOBAL_ENABLE = true;

	public static function modifyForScrollLoadDefer() {
		if ( !self::enabled() ) {
			return;
		}

		$items = pq( self::NEW_DEFER_SELECTOR );
		foreach ( $items as $node ) {
			$item = pq( $node );
			$link = $item->parent();
			$src = $item->attr('src');

			if ($src != '') {
				pq("<noscript></noscript>")->append($item->clone())->insertAfter($link);

				$id = self::setNodeId( $item );
				$script = "WH.shared.addScrollLoadItem('$id')";
				$item->removeAttr( 'src' );
				$item->attr( 'data-src', $src );

				// insert all the inline scripts into one script tag
				pq( Html::inlineScript( $script ) )->insertAfter( $item );
			}
		}
	}

    public static function modifyDOM() {
        global $wgRequest, $wgTitle;
        $pageId = $wgTitle->getArticleID();

        $rollout = true;

        if ( Misc::isMobileMode() ) {
			$rollout = true;
		} else {
			if ( ArticleTagList::hasTag( 'lazyload_destkop_images_disabled', $pageId ) ) {
				$rollout = false;
			}
		}

        $forceDefer = $wgRequest && $wgRequest->getInt('deferi') === 1;

        if ( !( $rollout || $forceDefer ) ) {
            self::modifyDOMForOriginalDefer( self::IMG_SELECTOR );
            return;
        }

        if ( !self::isArticlePage() ) {
            return;
        }

		if ( !self::enabled() ) {
			return;
		}

        $items = pq( self::SCROLL_LOAD_SELECTOR );
        foreach ( $items as $node ) {
            $item = pq( $node );
            $item->wrap("<div class='content-spacer'></div>");
            $item->addClass('content-fill');
            $link = $item->parent();
            $src = $item->attr('src');

            if ( !$src ) {
                continue;
            }
            pq("<noscript></noscript>")->append($item->clone())->insertAfter($link);

            $id = self::setNodeId( $item );
            $script = "WH.shared.addScrollLoadItem('$id')";
            $item->removeAttr( 'src' );
            $item->attr( 'data-src', $src );
            pq( Html::inlineScript( $script ) )->insertAfter( $item );
        }

        // now use original defer on any backup items we may have excluded
        self::modifyDOMForOriginalDefer( self::BACKUP_DEFER_SELECTOR );
    }

    /*
     * use the old defer images code
     */
    public static function modifyDOMForOriginalDefer( $selector ) {
        // do nothing if not an article page
        if ( !self::isArticlePage() ) {
            return;
        }

        $images = pq( $selector );

        foreach ($images as $node) {
            $img = pq($node);
            $link = $img->parent();
            $src = $img->attr('src');


            if ($src != '') {
                // add fallback image for no js browsers
                // Note: if $img already has an ID and it gets duplicated for the clone
                // in the <noscript> element, that's fine from a JS perspective, as
                // <noscript> contents are simply treated as raw text anyway.
                // If this is an issue, just removeAttr('id') on the clone.
                pq("<noscript></noscript>")->append($img->clone())->insertAfter($link);

                $id = self::setNodeId($img);
                $script = self::getScript($id);

                // add swapem script for mobile
                if (Misc::isMobileMode()) {
                    $img->attr('src', '')->attr('data-src', $src);
                }

                // if defer is turned on
                if (self::enabled()) {
                    $img->removeAttr('src');
                    $img->attr('data-src', $src);
                    // note: tried to set class to hidden first, which worked in most browsers but broke IE 6,7
                    $img->attr('style', 'display:none');
                    //$img->attr('src', self::PLACEHOLDER)->attr('class', 'placeholder');
                }

                // insert all the inline scripts into one script tag
                pq(Html::inlineScript($script))->insertAfter($img);
            }
        }

        if ( $selector == self::IMG_SELECTOR ) {
            self::modifyForScrollLoadDefer();

            // defer is not enabled but we still want to profile the first img load time
            if (!self::enabled()) {
                pq('a.image:first img:first')->attr('onload', "WH.timingProfile.recordTime('firstImageLoaded');");
            }
        }
	}

	/**
	 * Generate HTML for deferring a single image given its HTML attrs.
	 *
	 * Use this when pq() isn't available.
	 *
	 * @param array $imageAttrs associative array of HTML attributes and their
	 *   values. Assumes at least 'src' is given. You may also want to give it a
	 *   'defer' class.
	 *
	 * @return array a two-element array containing HTML for the deferred image,
	 *   and the corresponding noscript tag, respectively. If no 'src' provided,
	 *   you get empty strings back.
	 */
	public static function generateDeferredImageHTML($imageAttrs) {
		$isMobileMode = Misc::isMobileMode();
		$isEnabled = self::enabled();

		if (!$imageAttrs['src']) {
			return ['', ''];
		}

		$noscriptHTML =
			Html::openElement('noscript')
			. Html::element('img', ['src' => $imageAttrs['src']])
			. Html::closeElement('noscript');

		$id = self::generateIdIfNull($imageAttrs['id']);
		$script = self::getScript($id);

		$imageAttrs['id'] = $id;

		if ($isEnabled || $isMobileMode) {
			$imageAttrs['data-src'] = $imageAttrs['src'];
			unset($imageAttrs['src']);
		}

		if ($isEnabled) {
			$imageAttrs['style'] = 'display:none';
		}

		$imageHTML =
			Html::openElement('img', $imageAttrs)
			. Html::inlineScript($script);

		return [$imageHTML, $noscriptHTML];
	}

	public static function isArticlePage() {
		global $wgTitle, $wgRequest;
		if (!$wgTitle || !$wgRequest) {
			return false;
		}

		$action = $wgRequest->getVal('action', 'view');
		return $wgTitle->inNamespace(NS_MAIN) && $action == 'view';
	}

	public static function enabled() {
		global $wgRequest;
		if ( $wgRequest->getVal("printable") == "yes" ) {
			return false;
		}
		return true;
	}

	public static function getScript($id) {
		if (self::isArticlePage()) {
			$script = "var img = document.getElementById('$id');\n";

			if (Misc::isMobileMode()) {
				$script .= "WH.sizer.detectSize(img);\n";
				// look for a gif
				$script .= "WH.sizer.detectGif(img);\n";
			}

			if (self::enabled()) {
				$script .= "defer.add(img);\n";
			}

			return $script;
		}
	}

	/**
	 * @param string $id
	 *
	 * @return a fresh ID if null value provided, otherwise argument is just
	 *   returned.
	 */
	private static function generateIdIfNull($id=null) {
		return is_null($id) ? 'img_' . wfRandomString(10) : $id;
	}

	private static function setNodeId($img) {
		$id = self::generateIdIfNull($img->attr('id'));
		$img->attr('id', $id);
		return $id;
	}

	/**
	 * Get the paths of the JavaScript files required by this extension
	 * @return array
	 */
	public static function getJavascriptPaths(&$paths) {
		global $IP;

		if (self::isArticlePage()) {
			$basePath = $IP . self::SCRIPT_DIR;

			# About defer.js:
			# - Make sure to run "make" in this directory after making changes
			# - We compile defer.js using Google Closure for efficiency
			# - Same goes for sizer.js and timing-profile.js too
			if (self::enabled()) {
				$paths[] = "$basePath/defer.compiled.js";
			}

			$paths[] = "$basePath/timing-profile.compiled.js";

			if (Misc::isMobileMode()) {
				$paths[] = "$basePath/sizer.compiled.js";
			}
		}
	}
}

