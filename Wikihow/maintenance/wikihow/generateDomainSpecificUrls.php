<?php
//
// Generate a list of all URLs for the sitemap generator and for
// scripts that crawl the site (like to generate cache.wikihow.com)
//

require_once __DIR__ . '/../Maintenance.php';

class GenerateDomainSpecificUrls extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "get list of urls in a domain for use in sitemap generation: eg howyougetfit.com";
		$this->addOption( 'domain', 'domain to use', true, true, 'd' );
	}

	private static function iso8601_date($time) {
		$date = substr($time, 0, 4)  . "-"
			  . substr($time, 4, 2)  . "-"
			  . substr($time, 6, 2)  . "T"
			  . substr($time, 8, 2)  . ":"
			  . substr($time, 10, 2) . ":"
			  . substr($time, 12, 2) . "Z" ;
		return $date;
	}

	public function execute() {
		if ( !class_exists( 'AlternateDomain' ) ) {
			exit("cannot get test pages. domain test class does not exist\n");
		}

		$domainToPrint = $this->getOption( 'domain' );
		$pageIds = AlternateDomain::getAllPages();
		foreach( $pageIds as $pageId => $domain ) {
			if ( $domain != $domainToPrint ) {
				continue;
			}
			$title = Title::newFromID( $pageId );
			if ( $title && !$title->isRedirect() ) {
				$line = "https://www.$domain{$title->getLocalURL()}";
				$line .= ' lastmod=' . self::iso8601_date( $title->getTouched() );
				print "$line\n";
			}
		}
	}
}

$maintClass = "GenerateDomainSpecificUrls";
require_once RUN_MAINTENANCE_IF_MAIN;
