<?php

class Utils {
	public static function arrToAssoArr($arr) {
		$assocArr;
		if ($arr && is_array ( $arr )) {
			foreach ( $arr as $key => $val ) {
				$assocArr [$val] = 1;
			}
		}
		return $assocArr;
	}
	
	public static function getFileExt($path) {
		if($path) {
			return strtolower(pathinfo($path, PATHINFO_EXTENSION));
		}
		return NULL;
	}
	
	public static function getFileNameNoExt($path) {
		if($path) {
			return pathinfo($path, PATHINFO_FILENAME);
		}
		return null;
	}
}

