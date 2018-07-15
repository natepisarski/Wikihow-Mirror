<?php

class ListDemotedArticles extends QueryPage {

	private $targets;
	private $tablePrefix;

	function __construct( $name = 'ListDemotedArticles', $restriction = 'listdemotedarticles' ) {
		parent::__construct($name, $restriction);
	}

	function getName() {
		return 'ListDemotedArticles';
	}

	protected function getGroupName() {
		return 'pagetools';
	}

	function isSyndicated() {
		return false;
	}

	function getOrderFields() {
		return array( 'nap_page' );
	}

	function sortDescending() {
		return false;
	}

	function getQueryInfo() {
		return array(
				'tables' => array( 'newarticlepatrol' ),
				'fields' => array( 'nap_page' ), 
				'conds' => array( 'nap_demote' => 1 )
			);
	}

	function formatResult($skin, $result) {
		$title = Title::newFromID($result->nap_page);
		$link = Linker::linkKnown($title, null, array(), array( 'redirect' => 'no') );

		return $link;

	}
}