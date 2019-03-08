<?
        require_once('commandLine.inc');

		# this basically does a case insensitive comparison of suggested
		# titles to existing pages and sets any suggested titles to used
		# if they match a page that exists

        $dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('page', array('page_title'), 
			array('page_namespace'=>0, 'page_is_redirect' => 0));
		
        while ($row = $dbr->fetchObject($res)) {
			$titles[strtolower($row->page_title)] = 1;
		}

        $res = $dbr->select('suggested_titles',
                        array('st_title', 'st_id', 'st_used'), array('st_used'=>0));
		$ids = array();
        while ($row = $dbr->fetchObject($res)) {
			if (isset($titles[strtolower($row->st_title)])){
				$ids[] = $row->st_id;
				echo "{$row->st_title} has already been taken\n";
			}
        }

		$sql = "UPDATE suggested_titles set st_used=1 where st_id in ("
			. str_replace(" ", ", ", trim(implode($ids, " ")))
			. " ); ";
		if (sizeof($ids) > 0) {
			$dbw = wfGetDB(DB_MASTER);
			echo $sql;
			$dbw->query($sql);
		} else {
			echo "Nothing to update\n";
		}
	
