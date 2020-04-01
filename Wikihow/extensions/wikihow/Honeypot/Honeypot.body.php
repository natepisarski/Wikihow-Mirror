<?php

class Honeypot {

	static $mustache = false;

	/* Methods */

	public static function renderTemplate( $path, $data = [] ) {
		if ( !self::$mustache ) {
			self::$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
			] );
		}
		return self::$mustache->render( $path, $data );
	}

	public static function getDesktopWidgetHtml( $context ) {
		global $wgHoneypotCampaigns, $wgHoneypotActiveCampaign;

		$campaign = $wgHoneypotCampaigns[$wgHoneypotActiveCampaign];
		$target = isset( $campaign['target'] ) ?
			$campaign['target'] : "/Special:Campaign/{$wgHoneypotActiveCampaign}";

		return self::renderTemplate(
			'desktop-widget.mustache',
			[ 'campaign' => $wgHoneypotActiveCampaign, 'target' => $target ]
		);
	}
}
