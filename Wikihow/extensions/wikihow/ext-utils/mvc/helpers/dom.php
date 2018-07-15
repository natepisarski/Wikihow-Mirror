<?

function dataAttr($items) {
	$str = "";
	foreach ($items as $key => $value) {
		$str .= "data-$key='$value' ";
	}
	return $str;
}

function el($params='div') {
	$params = is_string($params) ? ['type' => $params] : $params;
	$atts = "";
	foreach($params as $key => $val) {
		$atts .= $key == 'type' ? '' : "$key='$val' ";
	}
	return "<{$params['type']} $atts>\n";
}

function close($type='div') {
	return "</$type>\n";
}

function arrToJs($arr) {
	$items = [];
	foreach($arr as $key => $value) {
		array_push($items, "$key: '$value'");
	}
	return "{" . implode(", ", $items) . "}";
}

function cssClass($str) {
	return str_replace(' ', '-', strtolower($str));
}