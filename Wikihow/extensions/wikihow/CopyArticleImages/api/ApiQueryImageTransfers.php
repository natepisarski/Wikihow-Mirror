<?php
/**
 * API for querying pending image transfers.
 *
 * @class
 */
class ApiQueryImageTransfers extends ApiQueryBase {

	/* Methods */

	public function __construct( ApiQuery $queryModule, $moduleName ) {
		parent::__construct( $queryModule, $moduleName, 'imagetransfers' );
	}

	public function execute() {
		global $wgActiveLanguages;

		$user = $this->getUser();

		if ( !in_array( 'staff', $user->getGroups() ) ) {
			$this->dieUsage( 'Permission denied.', 'permissiondenied' );
			return;
		}

		$params = $this->extractRequestParams();
		$data = [];

		$langs = $wgActiveLanguages;
		if ( is_array( $params['langs'] ) && count( $params['langs'] ) ) {
			$langs = $params['langs'];
		}
		foreach ( $langs as $lang ) {
			if ( in_array( $lang, $wgActiveLanguages ) ) {
				$data = array_merge( $data, ImageTransfer::getUpdatesForLang( $lang ) );
			}
		}

		usort( $data, function ( $a, $b ) {
			$av = $a->timeStarted;
			$bv = $b->timeStarted;
			return $av === $bv ? $av - $bv : ( $av < $bv ? 1 : -1 );
		} );

		$result = $this->getResult();
		$result->setIndexedTagName( $data, 'imagetransfers' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	public function getAllowedParams() {
		global $wgActiveLanguages;
		return [
			'langs' => [
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array_merge( [ '' ], $wgActiveLanguages ),
			]
		];
	}

	public function getPossibleErrors() {
		return [
			[ 'permissiondenied' ]
		];
	}

	protected function getExamplesMessages() {
		$params = $this->getAllowedParams();
		$allProps = implode( '|', $params['prop'][ApiBase::PARAM_TYPE] );
		return [
			'action=query&list=imagetransfers'
				=> 'apihelp-query+imagetransfers-example-1',
			"action=query&list=imagetransfers&imagetransferslangs=es|fr|ja"
				=> 'apihelp-query+imagetransfers-example-2',
		];
	}
}
