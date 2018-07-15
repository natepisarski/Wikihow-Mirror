<?

function ensureArray($arr) {
	return is_array($arr) ? $arr : [$arr];
}

function filter($arr, $method, $params) {
	$filtered = [];
	foreach ($arr as $item) {
		if ($item->$method($params)) {
			array_push($filtered, $item);
		}
	}
	return $filtered;
}

// function toCollection($arr) {
// 	return MVC\Collection::create($arr);
// }

function attributes($arr) {
	$collection = [];
	if (is_array($arr)) {
		foreach ($arr as $obj) {
			array_push($collection, $obj->attributes());
		}
	} else {
		array_push($collection, $arr->attributes());
	}
	return $collection;
}
