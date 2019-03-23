<?php
/**
 * SpecialPage that lets users buy the wikihow game.
 *
 * @class
 */
class SpecialHoneypotCampaign extends SpecialPage {

	static $mustache = false;

	/* Methods */

	public function __construct() {
		parent::__construct( 'Campaign' );
	}

	public static function renderCampaign( $campaign ) {
		if ( !self::$mustache ) {
			self::$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader( __DIR__ . '/campaigns' )
			] );
		}
		$data = [ 'campaign' => $campaign ];
		return self::$mustache->render( "{$campaign}/index.mustache", $data );
	}

	public function execute( $subPage ) {
		global $wgSquidMaxage, $wgHoneypotCampaigns, $wgHoneypotActiveCampaign,
			$wgHoneypotDefaultCampaign;

		$this->setHeaders();
		$output = $this->getOutput();
		$output->setSquidMaxage( $wgSquidMaxage );
		$output->setRobotPolicy( 'noindex,nofollow' );

		// Use the default campaign if the requested campaign isn't recognized
		if (
			// Campaign must be specified
			!array_key_exists( $subPage, $wgHoneypotCampaigns ) ||
			// Campaign must use Special:Campaign as landing page
			!array_key_exists( 'title', $wgHoneypotCampaigns[$subPage] )
		) {
			$subPage = $wgHoneypotDefaultCampaign;
		}

		$output->setPageTitle( $wgHoneypotCampaigns[$subPage]['title'] );
		$output->addHTML( self::renderCampaign( $subPage ) );
	}
}
