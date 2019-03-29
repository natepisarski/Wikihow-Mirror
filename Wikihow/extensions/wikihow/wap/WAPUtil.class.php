<?php
class WAPUtil {
	/**
	 * @param $data
	 * @param $table
	 * @param bool $updateOnDup
	 * @return string
	 * @deprecated Use DatabaseMysql::upsert instead of this method
	 */
	public static function makeBulkInsertStatement(&$data, $table, $updateOnDup = true) {
		$sql = "";
		if (!empty($data)) {
			$keys = "(" . implode(", ", array_keys($data[0])) . ")";
			$values = array();
			foreach ($data as $datum) {
				$values[] = "('" . join("','", array_values($datum)) . "')";
			}
			$values = implode(",", $values);

			$sql = "INSERT IGNORE INTO $table $keys VALUES $values";

			if ($updateOnDup) {
				$set = array();
				foreach ($data[0] as $col => $val) {
					$set[] = "$col = VALUES($col)";
				}
				$set = join(",", $set);
				$sql .= " ON DUPLICATE KEY UPDATE $set";
			}
		}

		return $sql;
	}

	public static function createTagArrayFromRequestArray(&$requestArray) {
		if (is_null($requestArray)) {
			$requestArray = array();
		}
		array_walk($requestArray, function(&$tag) {
			$parts = explode(",", $tag);
			$tag = array('tag_id' => $parts[0], 'raw_tag' => $parts[1]);
		});
		return $requestArray;
	}

	public static function generateTSVOutput(&$rows) {
		$output = "";
		if (!empty($rows)) {
			$output = implode("\t", array_keys((array) $rows[0])) . "\n";
			foreach ($rows as $row) {
				$row = (array) $row;
				$row['ct_page_title'] = WAPLinker::makeWikiHowUrl($row['ct_page_title']);
				$output .= implode("\t", $row) . "\n";
			}
		}
		return $output;
	}

	public static function getUserNameFromUserUrl(&$url) {
		$uname = preg_replace('@https?://www\.wikihow\.com/User:@', '', $url);
		$uname = str_replace('-', ' ', $uname);
		return urldecode($uname);
	}

	// parse a CSV file into a two-dimensional array
	// this seems as simple as splitting a string by lines and commas, but this only works if tricks are performed
	// to ensure that you do NOT split on lines and commas that are inside of double quotes.
	//
	// parse_csv and supplementary functions taken from:
	// http://php.net/manual/en/function.str-getcsv.php#111665
	// Serves as a fixed/improved version of PHP's str_getcsv.
	public static function parse_csv($str) {
		$str = preg_replace_callback('/([^"]*)("((""|[^"])*)"|$)/s', array(self, 'parse_csv_quotes'), $str);

		$str = preg_replace('/\n$/', '', $str);

		return array_map(array(self, 'parse_csv_line'), explode("\n", $str));
	}

	// replace all the csv-special characters inside double quotes with markers using an escape sequence
	public static function parse_csv_quotes($matches) {
		$str = str_replace("\r", "\rR", $matches[3]);
		$str = str_replace("\n", "\rN", $str);
		$str = str_replace('""', "\rQ", $str);
		$str = str_replace(',', "\rC", $str);

		return preg_replace('/\r\n?/', "\n", $matches[1]) . $str;
	}

	// split on comma and parse each field with a callback
	public static function parse_csv_line($line) {
		return array_map(array(self, 'parse_csv_field'), explode(',', $line));
	}

	// restore any csv-special characters that are part of the data
	public static function parse_csv_field($field) {
		$field = str_replace("\rC", ',', $field);
		$field = str_replace("\rQ", '"', $field);
		$field = str_replace("\rN", "\n", $field);
		$field = str_replace("\rR", "\r", $field);
		return $field;
	}
}
