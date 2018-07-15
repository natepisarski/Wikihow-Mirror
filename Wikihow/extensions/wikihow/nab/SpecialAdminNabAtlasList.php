<?php

if (!defined('MEDIAWIKI')) die();

/**
 * TEMP Class - Disable once Nab is live
 */
class SpecialAdminNabAtlasList extends QueryPage {

	function __construct( $name = 'AdminNabAtlasList' ) {
		global $wgRequest;
		parent::__construct( $name );
		$this->limit = $wgRequest->getVal('limit', 1000);
		$this->offset = $wgRequest->getVal('offset', 0);

		// little easter egg for debugging
		if ($wgRequest->getVal('debug')) {
			print_r( NabAtlasList::getNewRevisions() );exit;
		}
	}

    function sortDescending() {
        return true;
    }

    function getQueryInfo() {
        return array(
            'tables' => array( 'nab_atlas', 'page' ),
            'fields' => array(
                'namespace' => 'page_namespace',
                'title' => 'page_title',
				'score' => 'na_atlas_score',
				'score_updated' => 'na_atlas_score_updated',
            ),
            'conds' => array(
				'na_page_id = page_id',
                'page_namespace' => NS_MAIN,
                'page_is_redirect' => 0
            )
        );
    }

    function getOrderFields() {
		return array('na_atlas_score');
    }

	function isExpensive() {
		return false;
	}

	/**
	 * Reuben: took this from PageQueryPage.php.
	 *
	 * Run a LinkBatch to pre-cache LinkCache information,
	 * like page existence and information for stub color and redirect hints.
	 * This should be done for live data and cached data.
	 *
	 * @param $db DatabaseBase connection
	 * @param ResultWrapper $res
	 */
	public function preprocessResults( $db, $res ) {
		if ( !$res->numRows() ) {
			return;
		}

		$batch = new LinkBatch();
		foreach ( $res as $row ) {
			$batch->add( $row->namespace, $row->title );
		}
		$batch->execute();

		$res->seek( 0 );
	}

	/**
	 * Reuben: modified from PageQueryPage.php.
	 *
	 * Format the result as a simple link to the page
	 *
	 * @param Skin $skin
	 * @param object $row Result row
	 * @return string
	 */
	public function formatResult( $skin, $row ) {
		global $wgContLang;

		$title = Title::makeTitleSafe( $row->namespace, $row->title );

		if ( $title instanceof Title ) {
			$text = $wgContLang->convert( $title->getPrefixedText() );
			return Linker::link( $title, htmlspecialchars( $text ) ) . " (score=$row->score, updated=$row->score_updated)";
		} else {
			return Html::element( 'span', array( 'class' => 'mw-invalidtitle' ),
				Linker::getInvalidTitleDescription( $this->getContext(), $row->namespace, $row->title ) );
		}
	}
}

