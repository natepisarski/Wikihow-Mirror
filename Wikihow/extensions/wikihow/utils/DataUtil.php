<?php

class DataUtil {

	public static function arrayToDelimitedLine(array $values, $delimeter = "\t") {
		$line = '';

		$values = array_map(function ($v) {
			return '"' . str_replace('"', '""', $v) . '"';
		}, $values);

		$line .= implode($delimeter, $values);

		return $line;
	}
}
