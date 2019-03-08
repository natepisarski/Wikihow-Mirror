<?
	require_once('commandLine.inc');

	if (sizeof($argv) < 3) {
		echo "usage: php init_lite.php sourceDb destDb destImageDir\n";
		exit;
	}
	$sourceDB 	= $argv[0];
	$destDB 	= $argv[1];
	$destdir		= $argv[2];
	$wgEnotifWatchlist = false;
	$wgMemCachedServers = array();

	$destdir = preg_replace("@/$@", "", $destdir); 
function copyImages($images) {
	echo "Copying images to $destdir\n";
	global $destdir;
	$cmds = "";
	foreach ($images as $m) {
		$t = Title::makeTitle(NS_IMAGE, $m); 
		$img = wfLocalFile( $t, false);
		$path = $img->getPath();
		$d = preg_replace("@.*images/@", "", $img->getPath());
		$x = explode("/", $d);
		array_pop($x);
		$d = implode("/", $x);
		$thumb = preg_replace("@.*images/@", "", str_replace("/images/", "/images/thumb/", $img->getPath()));
	 	$thumb_dest = "thumb/$d";
		$cmds .= "mkdir -p \"$destdir/$d\";
cp \"$path\"  \"$destdir/$d\"; 
mkdir -p \"$destdir/{$thumb_dest}\";
cp -r \"./images/{$thumb}/\" \"$destdir/{$thumb_dest}\" 
";
		#shell_exec ($cmd);
	}	
	$myFile = "copyimages.sh";
	$fh = fopen($myFile, 'w') or die("can't open file");
	fwrite($fh, $cmds);
	fclose($fh);
}

	$wgDBservers[0]['dbname'] = $sourceDB;
	$wgLoadBalancer = new StubObject( 'wgLoadBalancer', 'LoadBalancer', array( $wgDBservers, false, $wgMasterWaitTimeout, true ) );

	$dbr = wfGetDB(DB_SLAVE);
	$mw = array();
	$images = array();

	// get all of the top 1000 pages
	echo "Grabbing top 1000 pages\n";
    $res = $dbr->select('page', array('page_namespace', 'page_title'), 
			array(
				//'page_title' => 'Write-a-Novel',
				'page_namespace'=>NS_MAIN),
			"init_lite", array("ORDER BY" => "page_counter desc", "LIMIT"=>1000));
    while ($row = $dbr->fetchObject($res)) {
        $t = Title::makeTitle($row->page_namespace, $row->page_title);
        $r = Revision::newFromTitle($t);
		preg_match_all("@\[\[Image:[^\]]*\]\]@im", $r->getText(), $matches);
		if (sizeof($matches[0]) > 0) {
			foreach ($matches[0] as $m) {
				$m = str_ireplace("[[Image:", "", $m);
				$m = str_replace("]]", "", $m);
				$m = preg_replace("@\|.*@im", "", $m);
				$images[] = $m;
			}
		}
        $mw[] = array($t, $r->getText());
    }
	/*
	// debug images
	foreach ($images as $m) {
		$t = Title::makeTitle(NS_IMAGE, $m); 
		$img = wfLocalFile( $t, false);
		echo $t->getFullText() . "\t" . $img->getPath() . "\n";
	}
	exit;
	*/
	copyImages($images);

	
	echo "Grabbing mediawiki messages\n";
	// get all of the  mediawiki messages
	$res = $dbr->select('page', array('page_namespace', 'page_title'), array('page_namespace in ('.NS_MEDIAWIKI . ', ' . NS_TEMPLATE . ', ' . NS_CATEGORY . ')' ));
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		$r = Revision::newFromTitle($t);
		$mw[] = array($t, $r->getText());
	}
	
	echo "Grabbing image pages and image table rows\n";
	// get all of the image pages
	$img_rows = array();
	$files = array();
	foreach ($images as $m) {
		$t = Title::makeTitle(NS_IMAGE, $m); 
		$a = new Article($t);
		$r = Revision::newFromTitle($t);
		if (!$r) {
			$t = Title::makeTitle(NS_IMAGE, ucfirst($m));
			$r = Revision::newFromTitle($t);
		}
		if (!$r) {
			echo "Couldn't get revision ";
			print_r($t);
			continue;
		}
		$mw[] = array($t, $r->getText());
		$row = (array) $dbr->selectRow('image', array("*"), array('img_name'=>$t->getDBKey()));
		$img_rows[] = $row;

	}

	// change to the destination server	
	$wgDBservers[0]['dbname'] = $destDB;
	$wgLoadBalancer = new StubObject( 'wgLoadBalancer', 'LoadBalancer', array( $wgDBservers, false, $wgMasterWaitTimeout, true ) );
	$dbw = wfGetDB(DB_MASTER);
	echo "Inserting revisions into lite database\n";
	echo "size: " . sizeof($mw) . "\n";
	foreach($mw as $ar) {
		$t = $ar[0];
		$wgTitle = $t; // for article->doEdit
		if ($t->getArticleID() > 0) {
			echo "Already exists: {$t->getFullText() } \n";
			continue;
		}
		$a = new Article($ar[0]);
		#echo "updating {$ar[0]->getFullText()}\n";
		$success = false;
		for ($i =0; $i < 3; $i++) {
			if (!$a->doEdit($ar[1], "initializing")) {
				echo "\n\nFAIL--------\n\n"; 
				//print_r($a); print_r($t); 
				sleep(5);
			} else {
				echo "Edit to: {$t->getFullText() } SUCCESS\n";
				$success = true;
				break;
			}
		}
		if (!$success) {
			echo "didn't update, why?";
			exit;
		}
	}

	echo "Initializing the image table\n";
	$dbw->query("delete from image");
	$oldIgnore = $dbw->ignoreErrors( true );
	foreach($img_rows as $row) {
		if (sizeof($row) >  5) {
			print_r($row);
			$dbw->insert('image', $row);
		}
	}
	$dbw->ignoreErrors( $oldIgnore );

