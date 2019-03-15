<?php
/**
 * SpecialPage to manage A/B experiements
 *
 * @class
 */
class SpecialHypothesis extends SpecialPage {

	/* Methods */

	public function __construct() {
		parent::__construct( 'Hypothesis' );
	}

	public function execute( $subPage ) {
		$this->setHeaders();
		$output = $this->getOutput();

		if ( !in_array( 'staff', $this->getUser()->getGroups() ) ) {
			$output->setRobotPolicy( 'noindex,nofollow' );
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$output->addModules( [ 'ext.wikihow.hypothesis' ] );
		$output->addHtml( '<div id="hyp" class="hyp"></div>' );
	}
}

/*
CREATE TABLE `hyp_experiment` (
	`hypx_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`hypx_opti_experiment` VARBINARY(255) NOT NULL DEFAULT '',
	`hypx_opti_project` VARBINARY(255) NOT NULL DEFAULT '',
	`hypx_name` VARBINARY(255) NOT NULL DEFAULT '',
	`hypx_holdback` INT(3) NOT NULL DEFAULT 100,
	`hypx_status` VARBINARY(255) NOT NULL DEFAULT '',
	`hypx_target` VARBINARY(255) NOT NULL DEFAULT 'all',
	`hypx_creator` INT(10) NOT NULL DEFAULT 0,
	`hypx_created` BINARY(14) NOT NULL DEFAULT '',
	`hypx_updated` BINARY(14) NOT NULL DEFAULT ''
);
*/

/*
CREATE TABLE `hyp_test` (
	`hypt_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
	`hypt_experiment` INT(10) NOT NULL DEFAULT 0,
	`hypt_page` INT(10) NOT NULL DEFAULT 0,
	`hypt_rev_a` INT(10) NOT NULL DEFAULT 0,
	`hypt_rev_b` INT(10) NOT NULL DEFAULT 0,
	KEY (`hypt_page`),
	KEY (`hypt_experiment`)
);
*/
