<?
	require_once('commandLine.inc');
	$dbr = wfGetDB(DB_SLAVE);
	$sql = "Select page_id, page_title, page_len, max(rev_len) as rev_len from page left join revision on page_id = rev_page where page_namespace=0 and page_is_redirect=0 group by rev_page";
#echo $sql; exit;
	$res = $dbr->query($sql);
	echo "<table width='80%' align='center'>";
	$count = 0;
	while ($row = $dbr->fetchObject($res)) {
		if ($row->rev_len == 0) {
			#echo "Warning! {$row->page_id} = 0 \n"; exit;
			continue; /// it's an old article
		}
		if ($row->page_len / $row->rev_len <= 0.7) {
			$t = Title::makeTitle(NS_MAIN, $row->page_title);
			if ($t) {
				$rev_id = $dbr->selectField("revision",
					array("rev_id"),
					array("rev_page" => $row->page_id, "rev_len"=>$row->rev_len)
					);
				$count++;
				echo "<tr><td>{$count}.</td><td><a href='{$t->getFullURL()}'>{$t->getText()}</a></td>"
				. "<td><a href='{$t->getFullURL()}'>" .number_format($row->page_len, 0, "", ",") . "</a></td>"
				. "<td><a href='{$t->getFullURL()}?oldid={$rev_id}'>" .number_format($row->rev_len, 0, "", ",") . "</td>"
				. "<td><a href='{$t->getFullURL()}?action=history'>History</td>
				</tr>";
			}
		}
	}
	echo "</table>";
