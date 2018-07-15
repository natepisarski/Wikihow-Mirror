<?
	require_once('commandLine.inc');

	$dbr = wfGetDB(DB_SLAVE);
	$bad = array(); 
	$wgUser = User::newFromName('Tderouin');
	$res = $dbr->select('page', array('page_title', 'page_namespace'), array('page_namespace' => NS_IMAGE));
	$count = 0;
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		if (preg_match("/[^".Title::legalChars()."]|\?/", $t->getText())) {
			#echo "find /var/www/images_en -name '{$t->getText()}' \n";
			$img = wfFindFile( $t, false);	
			$oldpath = $img->getPath(); 

			$newtitle = Title::makeTitle(NS_IMAGE, trim(preg_replace("@\?@", "", $t->getText())));
			if (!$newtitle) {
				echo "oops! {$row->page_title}\n";
				exit;
			}
			$a = new Article($t); 
			$a->doDelete("Bad image name");	
			#echo "{$oldpath}\t{$newpath}\n";
			echo "{$t->getText()}\n";
			#echo "cp " . wfEscapeShellArg($img->getPath()) . " /tmp/bad \n";
		}
		$count++;
	}
