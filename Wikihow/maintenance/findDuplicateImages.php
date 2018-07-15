<?
require_once( "commandLine.inc" );

function get_key ($name, $size) {
	 return substr($name, 0, strrpos($name, "-")) . "-$size";
}

	$wgUser = User::newFromName("Tderouin");

	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->select('image', 
			array( 'img_name', 'img_size'),
			//array('img_size > 1048576')
			array()
			);

	$checked = array();
	$replace = array();
	while ( $row = $dbr->fetchObject($res) ) {
		$name = $row->img_name;
	
		if (strpos($name, "-") !== false) {
			if (isset($checked[$name]) || $checked[$name] == 1) 
				continue;	
			$name = substr($name, 0, strrpos($name, "-"));
			$res2 = $dbr->select("image", 
				array("img_name"), 
				array("img_name like '" . 
					$dbr->strencode($name) . "%'", "img_size=" 
					. $row->img_size, "img_name != " . $dbr->addQuotes($row->img_name) ));
			while ( $row2 = $dbr->fetchObject($res2) ) {
				#echo "{$row->img_name}\t{$row2->img_name}\t{$row->img_size}\n";
				$checked[$row->img_name] = 1;	
				$checked[$row2->img_name] = 1;
				$replace[$row2->img_name] = $row->img_name;
			}
			$dbr->freeResult($res2);
		}		
	}	
	$dbr->freeResult($res);

	$updated = 0;
	foreach ($replace as $source=>$target) {
		$res = $dbr->select('imagelinks', array('il_from'), array('il_to' => $source) );
		$found = false;
		$img = Title::makeTitle(NS_IMAGE, $source);	
		$dest = Title::makeTitle(NS_IMAGE, $target);
		while ($row = $dbr->fetchObject($res) ) {
			$t = Title::newFromId($row->il_from);
			if ($t->getNamespace() == NS_IMAGE)
				continue;
			$r = Revision::newFromTitle($t);
			if (strpos($r->getText(), $img->getText()) !== false) {
				echo "replacing $source with $target on {$t->getFullText()}\n";
				$found = true;	
				$text = str_replace($img->getFullText(), $dest->getFullText(), $r->getText());
				$wgTitle = $t;
				$a = new Article($t);
				if ($a->updateArticle($text, "using $target instead of $source", true, true)) {
					echo "{$t->getFullText()} updated\n";
				} else {
					echo "{$t->getFullText()} not updated\n";
				}
			}
		}
		if (!$found && false) {	
			echo "Found nothing for $source\n";
		} else {
			$r = Revision::newFromTitle($img);
			$text = $r->getText();
			if (strpos($text, "{{ifd") === false) {	
				$text = "{{ifd|duplicate of [[:Image:{$dest->getText()}]]}}\n{$r->getText()}";
				$wgTitle = $img;
				$a = new Article($img);
				if ($a->updateArticle($text, "ifd, duplicate of {$dest->getFullText()}", false, false)) {
					echo "image article $source updated.\n";
				} else {
					echo "image article $source not updated.\n";
				}
				$updated++;
			} else {
				#echo "$source already if'd\n";
			}
		}
		if ($updated == 100) {
			#break;
		}
	}
	echo "$updated articles updated\n";
?>
