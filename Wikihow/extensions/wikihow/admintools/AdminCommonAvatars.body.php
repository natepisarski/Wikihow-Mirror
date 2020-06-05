<?php

class AdminCommonAvatars extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'AdminCommonAvatars' );
	}

	/**
	 * Execute special page. Only available to wikihow staff.
	 */
	function execute( $par ) {
		global $wgMemc;

		$userGroups = $this->getUser()->getGroups();
		$out = $this->getOutput();
		$request = $this->getRequest();

		if ( !in_array('staff', $userGroups ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$force = false;
		if ( $request->wasPosted() ) {
			$force = $request->getBool( 'rebuild' );
		}
		$key = wfMemcKey( 'AdminCommonAvatars::getAllResults' );
		$data = $wgMemc->get( $key );
		if ( !$data || $force ) {
			$data = [
				'results' => $this->getAllResults(),
				'rebuilt' => date( DATE_RFC2822 )
			];
			// Cache results for a day
			$wgMemc->set( $key, $data, 24 * 60 * 60 );
			$out->redirect( '/Special:AdminCommonAvatars' );
		}

		$options = array(
			'loader' => new Mustache_Loader_FilesystemLoader( __DIR__ ),
		);
		$m = new Mustache_Engine($options);
		$out->addHtml( $m->render( 'admincommonavatars.mustache', $data ) );
		$out->addModules( [ 'ext.wikihow.adminCommonAvatars' ] );
		$out->setPageTitle( 'Common Avatars' );
	}

	/**
	 * Get a list of avatars shared by at least 10 people grouped by imageHash.
	 *
	 * @return array List of results
	 */
	public static function getAllResults() {
		// Build results
		$dbr = wfGetDB( DB_REPLICA );
		//select MIN(av_image), av_imageHash, count(av_imageHash) as qty from avatar group by av_imageHash having qty > 10 order by qty desc
		$rows = $dbr->select(
			'avatar',
			[
				'av_imageHash as hash',
				'MIN(av_image) as img',
				'COUNT(av_imageHash) as qty'
			],
			[],
			__METHOD__,
			[
				'GROUP BY' => 'hash',
				'HAVING' => 'qty > 10',
				'ORDER BY' => 'qty DESC'
			]
		);
		$results = [];
		foreach ( $rows as $row ) {
			$results[] = [
				'hash' => $row->hash,
				'img' => $row->img,
				'qty' => $row->qty
			];
		}
		return $results;
	}
}
