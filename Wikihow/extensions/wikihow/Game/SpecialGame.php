<?php
/**
 * SpecialPage that lets users buy the wikihow game.
 *
 * @class
 */
class SpecialGame extends SpecialPage {

	/* Methods */

	public function __construct() {
		parent::__construct( 'Game' );
	}

	public function execute( $subPage ) {
		global $wgSquidMaxage;

		$this->setHeaders();
		$output = $this->getOutput();
		$output->setSquidMaxage( $wgSquidMaxage );
		$output->setRobotPolicy( 'noindex,nofollow' );
		$output->addModules( [ 'ext.wikihow.game' ] );
		$output->setPageTitle( 'Buy the wikiHow Card Game' );
		$data = [];
		$output->addHTML( Game::renderTemplate( 'index.mustache', $data ) );
	}
}
