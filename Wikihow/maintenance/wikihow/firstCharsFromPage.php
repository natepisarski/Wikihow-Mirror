<?php

require_once __DIR__ . '/../Maintenance.php';

class FirstCharsFromPage extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "get first chars from title";
    }

	public function execute() {
		while (false !== ($line = fgets(STDIN))) {
			$id = trim( $line );
			$title = Title::newFromID( $id );
			if ( !$title ) {
				decho("no title", $id);
				die();
			}
			$gr = GoodRevision::newFromTitle( $title );
			if ( !$gr ) {
				decho("no gr", $title);
				die();
			}

			$latestGood = $gr->latestGood();
			if ( !$latestGood ) {
				decho("no latest good from gr", $title);
				die();
			}

			$r = Revision::newFromId( $latestGood );
			if ( !$r ) {
				decho("no rev from rev id ", $latestGood);
				die();
			}

			//$txt = ContentHandler::getContentText( $r->getContent() );
			$content = $r->getContent();
			$txt = $content->getNativeData();

			//$text = preg_replace('/\[\[[\s\S]+?\]\]/', '', $text);
			$txt = preg_replace('/===[\s\S]+?===/', '', $txt);
			$txt = preg_replace('/<ref>[\s\S]+?<\/ref>/', '', $txt);
			// remove http urls
			//$txt = preg_replace('|https?://www\.[a-z\.0-9]+|i', '', $txt);
			// Remove images
			//$txt = preg_replace( "@\[\[image:[^\]]+\]\]@i","", $txt);
			// Remove templates
			$txt = preg_replace( "@{{[^}]+}}@","",$txt);
			// Remove bold triple-single quotes
			///$txt = preg_replace( "@'''@","", $txt);
			// Remove wikilinks
			//$txt = preg_replace( "@\[\[[^|\]]+\|([^\]]+)\]\]@", "$1", $txt);
			$txt = preg_replace( "@\[[^\]]+\]+@", "", $txt);
			//$txt = preg_replace( "@<[^>]+>@", "", $txt);
			//$txt = htmlspecialchars_decode($txt);
			$txt = str_ireplace( "==Ingredients==", "Ingredients", $txt );
			$txt = str_ireplace( "== Ingredients ==", "Ingredients", $txt );
			$txt = str_ireplace( "==Steps==", "", $txt );
			$txt = str_ireplace( "== References ==", "", $txt );
			$txt = str_ireplace( "== Steps ==", "", $txt );
			$txt = str_ireplace( "== Steps==", "", $txt );
			$txt = str_ireplace( "==Steps ==", "", $txt );
			$txt = str_ireplace( "==Steps ==", "", $txt );
			$txt = str_replace( "<br>", "", $txt );
			$txt = str_replace( "\n#*", "\n", $txt );
			$txt = str_replace( "\n#", "\n", $txt );
			$txt = str_replace( "\n", "", $txt );
			// remove the # sign. for some reason this, when it appeared at the end of a url for example this one:
			// http://support.hp.com/us-en/drivers/selfservice/hp-deskjet-3050-all-in-one-printer-series-j610/4066450/model/4066451#Z7_3054ICK0K8UDA0AQC11TA930C7
			// was causing the output file to be ascii instead of utf8  and therefore messed up importing this in to another file
			$txt = str_replace( "#", "", $txt );

			$txt = wfMessage( 'howto', $title ) . '. ' . $txt;

			$length = 999;
			if ( mb_substr( $txt, 1000, 1 ) !== ' ' ) {
				$txt = mb_substr( $txt, 0, 999 );
				$length = mb_strrpos( $txt, " " );
			}
			$output = mb_substr( $txt, 0, $length );
			
			echo $output;
			echo "\n";
		}
	}
}


$maintClass = "FirstCharsFromPage";
require_once RUN_MAINTENANCE_IF_MAIN;

