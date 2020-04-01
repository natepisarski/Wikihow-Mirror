<?php

/*
* 	gets at a glance html
*/
class AtAGlance {
	public static function showSidebar( $title ) {
		$pageId = $title->getArticleID();

		if ( $pageId == 0 ) {
				return false;
		}

		if ( !$title->inNamespace(NS_MAIN) ) {
			return false;
		}

		// todo check for action=view
		if ( self::isInTestGroup( $title, 6 ) ) {
			return false;
		}

		return true;
	}

	public static function isInTestGroup( $title, $groupNumber) {
		$pageId = $title->getArticleID();
		$ret = ArticleTagList::hasTag( "SummaryV$groupNumber", $pageId );
		return $ret;
	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		global $wgTitle;
		if ( self::isInTestGroup( $wgTitle, 6 ) ) {
			$out->addModules('ext.wikihow.ataglance.slider');
		}
		$out->addModules('ext.wikihow.ataglance');
	}

	public static function getSidebarHTML() {
		// get any vars to pass to the template
		$vars = array();
		$vars['atAGlance'] = wfMessage('ataglance_sidebar_title')->text();
		EasyTemplate::set_path( __DIR__.'/' );
		return EasyTemplate::html( 'ataglance.desktop.sidebar.tmpl.php', $vars );
	}

	public static function runArticleHookTest( $title ) {
		if ( pq( '.ataglance' )->length > 0 ) {
			$mobile = Misc::isMobileMode();
			$pageId = $title->getArticleID();
			if ( self::isInTestGroup( $title, 5 ) ) {
				if ( pq( ".ataglance ol" )->length > 0 ) {
					$ul = pq( ".ataglance ol" )->wrap( "<ul></ul>" );
					pq( '.ataglance ol' )->replaceWith( $ul->html() );
				}
				if ( pq( "#ataglance" )->length > 0 ) {
					$p = pq('#ataglance')->wrap('<p></p>');
					$toc = pq( '#method_toc' );
					if ( pq( '.firstHeading' )->nextAll( 'p' )->length > 0 ) {
						pq( '.firstHeading' )->nextAll( 'p' )->remove();
						pq( '.firstHeading' )->after( $p->html() );
						pq( '.firstHeading' )->after( $toc );
						pq( '.ataglance' )->remove();
					} elseif ( pq( '#intro' )->children( 'div' )->children( 'p' )->length > 0 ) {
						pq( '#intro' )->children( 'div' )->children('p')->remove();
						pq( '#intro' )->children( 'div' )->html( $p->html() );
						pq( '.ataglance' )->remove();
					}
				}
			} elseif ( self::isInTestGroup( $title, 6 ) ) {
				if ( !$mobile ) {
					pq( '.ataglance' )->addClass( "hidden" );
					pq( '.ataglance' )->addClass( "aag_slideshow" );
				}
			} else {
				pq( '.ataglance' )->addClass( "hidden" );
			}
		}
	}
}
