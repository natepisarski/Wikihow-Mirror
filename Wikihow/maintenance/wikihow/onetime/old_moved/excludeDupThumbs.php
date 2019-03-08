<?
require_once( 'commandLine.inc' );
$dbw = wfGetDB( DB_MASTER );
$dbr = wfGetDB( DB_SLAVE );

$sql = "SELECT  thumb_giver_id, thumb_recipient_text, thumb_timestamp, thumb_rev_id, thumb_page_id, page_id, page_title  FROM thumbs, page WHERE thumb_page_id=page_id ORDER BY thumb_page_id, thumb_recipient_text, thumb_timestamp";
$res = $dbr->query($sql);

$row = $dbr->fetchObject($res);
$thumbGiverId = $row->thumb_giver_id;
$thumbRecipientText = $row->thumb_recipient_text;
$thumbTimestamp = $row->thumb_timestamp;
$thumbRevId = $row->thumb_rev_id;
$thumbPageId = $row->thumb_page_id;
$thumbPageTitle = $row->page_title;
$timestamp = $row->thumb_timestamp;

echo "curent data: $thumbPageTitle, pageId: $thumbPageId, thumbGiverId: $thumbGiverId, thumb_recipient_text: $thumbRecipientText, rev: $thumbRevId, $timestamp\n";
while ($row = $dbr->fetchObject($res)) {
	$before = wfTimestamp(TS_UNIX, $thumbTimestamp) - 3;
	$after = wfTimestamp(TS_UNIX, $thumbTimestamp) + 3;
	$time = wfTimestamp(TS_UNIX, $row->thumb_timestamp);
	if ($thumbPageId == $row->thumb_page_id && $thumbRecipientText == $row->thumb_recipient_text && $thumbGiverId == $row->thumb_giver_id && $time > $before && $time < $after && $thumbRevId != $row->thumb_rev_id) {
		$sql = "UPDATE thumbs SET thumb_exclude=1 WHERE thumb_giver_id = " . $row->thumb_giver_id . " AND thumb_rev_id = " . $row->thumb_rev_id . " and thumb_recipient_text = " . $dbr->addQuotes($row->thumb_recipient_text) . " and thumb_timestamp='" . $row->thumb_timestamp . "' and thumb_page_id = " . $row->thumb_page_id;
		echo $sql . "\n";
		$dbw->query($sql);
	}
	else {
		$thumbGiverId = $row->thumb_giver_id;
		$thumbRecipientText = $row->thumb_recipient_text;
		$thumbTimestamp = $row->thumb_timestamp;
		$thumbRevId = $row->thumb_rev_id;
		$thumbPageId = $row->thumb_page_id;
		$thumbPageTitle = $row->page_title;
		$timestamp = $row->thumb_timestamp;
		echo "curent data: $thumbPageTitle, pageId: $thumbPageId, thumbGiverId: $thumbGiverId, thumb_recipient_text: $thumbRecipientText, rev: $thumbRevId, $timestamp\n";
	}
}

