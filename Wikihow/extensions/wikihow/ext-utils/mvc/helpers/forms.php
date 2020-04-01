<?php

function isChecked($id, $children) {
	return hasModel($id, $children) ? "checked='true'" : '';
}

function isSelected($id, $children) {
	return hasModel($id, $children) ? "selected='true'" : '';
}

function selectedIf($val, $val2) {
	$val =  is_string($val) && !is_null(params($val)) ? params($val) : $val;
	return $val == $val2 ? 'selected="true"' : '';
}

function checkedIf($val, $val2) {
	$val =  is_string($val) ? params($val) : $val;
	if (is_array($val)) {
		return in_array($val2, $val) ? 'checked="true"' : '';
	}
	return $val == $val2 ? 'checked="true"' : '';
}

function valueOr($val1, $val2) {
	return $val1 ? $val1 : $val2;
}

function option($label, $value, $compareVal=null) {
	return "<option value='$value'". selectedIf($value, $compareVal) .">$label</option>\n";
}

function optionsFromArray($arr, $label, $value, $compareVal=null) {
	$output = "\n";
	foreach ($arr as $item) {
		$itemValue = is_object($item) ? $item->$value : $item[$value];
		$itemLabel = is_object($item) ? $item->$label : $item[$label];
		$output .= option($itemLabel, $itemValue, $compareVal);
	}
	return "$output\n";
}

function urlFor($model, $baseUrl=null) {
	$baseUrl = $baseUrl ? $baseUrl : $_GET['controller'];
	$baseUrl .= $model->id ? '/update' : '/create';
	$params = $model->id ? ['id' => $model->id] : null;
	return url($baseUrl, $params);
}

function hasModel($id, $arr) {
	if (is_null($arr)) {
		return false;
	}

	foreach ($arr as $child) {
		if ($id == $child->id) {
			return true;
		}
	}

	return false;
}
