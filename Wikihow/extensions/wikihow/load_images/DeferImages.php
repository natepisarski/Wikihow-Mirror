<?php

class DeferImages {
	const SCROLL_LOAD_SELECTOR = '.mwimg:not(.floatright, .tcenter) a.image:not(.mwimg-caption-image) img';

    public static function modifyDOM() {
        if ( !self::validPage() ) {
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
    }

  public static function validPage(): bool {
		$title = RequestContext::getMain()->getTitle();
  	return self::isArticlePage() ||
  		($title && $title->inNamespaces(NS_USER, NS_USER_TALK, NS_USER_KUDOS));
  }

	public static function isArticlePage() {
		$context = RequestContext::getMain();
		$title = $context->getTitle();

		return $title && $title->inNamespace(NS_MAIN) && Action::getActionName($context) == 'view';
	}

	public static function enabled() {
		global $wgRequest;
		if ( $wgRequest->getVal("printable") == "yes" ) {
			return false;
		}
		return true;
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
}

