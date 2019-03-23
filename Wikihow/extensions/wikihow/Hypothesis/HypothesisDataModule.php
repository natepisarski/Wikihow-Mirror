<?php
/**
 * ResourceLoader module to deliver additional data to the client.
 *
 * @class
 */
class HypothesisDataModule extends ResourceLoaderModule {

	/* Static Members */

	private static $templates = [
		'index',
		'view',
		'edit',
		'history'
	];

	private static $configs = [
		'wgOptimizelyAccessToken' => 'optimizelyAccessToken',
		'wgOptimizelyProject' => 'optimizelyProject'
	];

	/* Members */

	protected $origin = self::ORIGIN_USER_SITEWIDE;
	protected $targets = [ 'desktop', 'mobile' ];

	/* Methods */

	public function getScript( ResourceLoaderContext $context ) {
		$configs = [];
		$templates = [];

		foreach ( static::$configs as $var => $key ) {
			global ${$var};
			$configs[$key] = ${$var};
		}

		foreach ( static::$templates as $key ) {
			$templates[$key] = file_get_contents( __DIR__ . "/templates/{$key}.mustache" );
		}

		return 'WH.Hypothesis.config.set(' .
			FormatJson::encode( $configs, ResourceLoader::inDebugMode() ) .
		');' .
		'WH.Hypothesis.template.set(' .
			FormatJson::encode( $templates, ResourceLoader::inDebugMode() ) .
		');';
	}

	public function enableModuleContentVersion() {
		return true;
	}

	public function getDependencies( ResourceLoaderContext $context = null ) {
		return [ 'ext.wikihow.hypothesis.core' ];
	}
}
