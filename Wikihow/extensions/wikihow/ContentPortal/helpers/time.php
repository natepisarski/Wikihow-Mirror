<?
function humanTime($stamp) {
	return (new DateTime($stamp))->format('m/d/y h:ia ');
}

function timeAgo($stamp, $nullString=''){
	if (is_null($stamp)) {
		return $nullString;
	}

	$periods = ["sec", "min", "hour", "day", "week", "month", "year", "decade"];
	$lengths = ["60","60","24","7","4.35","12","10"];

	$now        = (new DateTime())->format('U');
	$difference = $now - $stamp->format('U');

	for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}

	$difference = round($difference);
	$unit = $periods[$j];

	if ($difference != 1 && !in_array($unit, ['sec', 'min'])) {
		$unit .= "s";
	}

	return $difference <= 0 ? "just now" : "$difference $unit ago";
}
