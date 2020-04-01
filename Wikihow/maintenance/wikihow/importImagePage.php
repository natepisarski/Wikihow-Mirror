<?php
// this script can find an image on an image page and import it into the current mediawiki
// it essentially will impor the image page since the end result is creating a new image page
// it really just finds the image on an existing image page then downloads that image file then runs
// the script importImages.php to import that image.

// it's kind of a one off thing, but it works
// depending on permissions of your computer you probably have to run this as root or _www or apache
// in order for the importImages.php script to work

require_once( __DIR__ . '/../Maintenance.php' );

class ImportImagePage extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'pagetitle', 'page title', true, true, 't');
	}

	public function execute() {
		// get the page name and image name (for use later)
		$page = str_replace(' ', '-', $this->getOption('pagetitle'));
		$imageName = str_replace('Image:', '', $page);

		// sanity check
		if (strpos($page, "Image:") !== 0) {
			echo("this title does not begin with Image: are you sure it is an image page?\n");
			exit();
		}

		// for now, just get the image from the main wikihow site
		$url = "http://www.wikihow.com";

		$fetchUrl = "$url/$page";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fetchUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);


		// probably could improve this pattern matching but it works
		$matches = array();
		$pattern = '/href="\/image.*/';
		preg_match($pattern, $result, $matches);
		$p2 = '/"([^"]+)"/' ;
		$m2 = array();
		preg_match($p2, $matches[0], $m2);

		// we know that the image almost certainly exists on our pad server so get it there
		$imageUrl = "http://pad1.whstatic.com".$m2[1];
		shell_exec("rm images/*");
		echo "downloading image $imageUrl\n";
		shell_exec("curl -o images/$imageName $imageUrl");
		echo "importing image $imageName\n";
		// this is the script that probably needs root access or run as apache or _www
	    $result = shell_exec("php ../importImages.php images/");
		echo $result."\n";
		// remove images from this diretory when we are donw so the image importer doesn't import multiple images next time
		shell_exec("rm images/$imageName");
	}
}

$maintClass = "ImportImagePage";
require_once( RUN_MAINTENANCE_IF_MAIN );
