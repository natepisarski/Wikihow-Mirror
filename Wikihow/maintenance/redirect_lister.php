<?

#
# List redirect articles with a bunch of extra info, in CSV format
#

require_once('commandLine.inc');

function main($out_csv) {
	$dbr = wfGetDB(DB_SLAVE);

	# List all articles in main namespace who redirect elsewhere
	$articles = array();
	$batch_size = 500;
	$page = 0;
	while (true) {
		$offset = $page++ * $batch_size;
		$res = $dbr->query('SELECT page_title FROM page WHERE page_namespace=' . NS_MAIN . ' AND page_is_redirect=1 LIMIT ' . $offset . ',' . $batch_size);
		$count = 0;
		foreach ($res as $row) {
			$count++;
			$articles[] = array(
				'title' => $row->page_title,
			);
		}
		if ($count < $batch_size) break;
	}

	print "number of redirects found: ".sizeof($articles)."\n";

	# Get the extra redirect info, write the line to $out_csv
	$bad_records = 0;
	$fp = fopen($out_csv, 'w');
	$count = 0;
	foreach ($articles as $article) {
		$count++;
		$redir_title = $article['title'];
		$title = Title::newFromDBkey($redir_title);
		if (!$title) {
			$bad_records++;
			continue;
		}
		$rev = Revision::newFromTitle($title);
		if (!$rev) {
			$bad_records++;
			continue;
		}

		# Parse title of article to which to redirect
		$body = $rev->getText();
		if (preg_match('@^\s*#REDIRECT[: *]*\[\[(.*)\]\]@i', $body, $matches)) {
			$redir_dest = $matches[1];
			$title_dest = Title::newFromText($redir_dest);
			if (!$title_dest) {
				$bad_records++;
				continue;
			}

			# Search for inbound links
			$fields = array('page_title');
			$conds = array( 
				'page_id=pl_from',
				'pl_title' => $redir_title,
				'pl_namespace' => NS_MAIN,
				'page_namespace' => NS_MAIN,
			);
			$options = array('STRAIGHT_JOIN', 'LIMIT' => 6);
			$res = $dbr->select( array('pagelinks', 'page'), $fields, $conds, __METHOD__, $options );
			$links = array();
			while ($row = $dbr->fetchRow($res)) {
				$link = Title::newFromDBkey($row['page_title']);
				if (!$link) {
					print "err: bad link title: {$row['page_title']}\n";
					continue;
				}
				$links[] = $link->getText();
			}

			# collate results, print in CSV format
			$inbound_links = $links ? join(', ', $links) : '';
			$line = array('', $title->getText(), $title_dest->getText(), $inbound_links);
			fputcsv($fp, $line);

		} else {
			print "err: bad match: $redir_title, text:$body\n";
			$bad_records++;
		}

		if ($count % 1000 == 0) {
			print "processed $count ...\n";
		}
	}
	fclose($fp);
	print "num bad records: $bad_records\n";
}

if (@$GLOBALS['argc'] != 2) {
	print "usage: php redirect_lister.php output-file.csv\n";
	exit;
}

$out_csv = $GLOBALS['argv'][0];

main($out_csv);

