<?php
namespace ContentPortal;
require '../../ext-utils/mvc/CLI.php';
require '../vendor/autoload.php';

use MVC\CLI;
use __;

use WAPEditfishConfig;
use EditfishArtist;
use WAPArticleTagDB;
use WAPDB;
use Misc;

class Fish extends CLI {

	public $defaultMethod = 'goFishing';


	function goFishing() {
		$fish = new Fishing('Dperrymorrow');
		self::trace($fish->userAllowed);

		$tags = $fish->getTags();
		self::trace($fish->getTags());

		$availible = $fish->availibleArticles($tags[0]['tag']);
		self::trace($availible);
		$fish->assignArticle($availible[0]);
		self::trace($fish->assignedArticles());
	}

}

$maintClass = 'ContentPortal\\Fish';
require RUN_MAINTENANCE_IF_MAIN;
