<?php

if ( !defined('MEDIAWIKI') ) die();

/**
* A utility class of static functions that produce html snippets
*/
class HtmlSnips {

	/*
	 * Returns script or link tags for including javascript and css
	 *
	 * WARNING: You should use NOT use this method typically. You should prefer
	 * Resource Loader, which loads resources more efficiently. We use this method
	 * for low traffic special pages only, when using Resource Loader is a little
	 * too cumbersome.
	 *
	 * @param string $type The type of tags to produce.  Valid values are 'css' or 'js'
	 * @param array $files An array of js or css file names
	 * @param string $path The path to the files
	 * @param bool $debug An optional debug flag. If true, then files aren't minified
	 *
	 * @return string
	 */
	public static function makeUrlTags($type, $files, $path, $debug = false) {
		$files = array_unique($files);

		$path = preg_replace('/^\/(.*)/', '$1', $path);
		$path = preg_replace('/(.*)\/$/', '$1', $path);

		if ($type == 'css') {
			$fmt = '<link rel="stylesheet" type="text/css" href="%s" />'."\n";
		} else {
			$fmt = '<script src="%s"></script>'."\n";
		}
		if (!$debug) {
			$url = wfGetPad('/extensions/min/f/' . join(',', $files) . '&b=' . $path . '&' . WH_SITEREV);
			$ret = sprintf($fmt, $url);
		} else {
			$ret = '';
			foreach ($files as $file) {
				$ret .= sprintf($fmt, '/' . $path . '/' . $file);
			}
		}

		return $ret;
	}

	/*
	 * Returns a script or link tag for including a single javascript or css
	 * file. A simpler version of makeUrlTags.
	 *
	 * See warning above, which applies to this method as well.
	 *
	 * @param string $file The file. For example, /extensions/wikihow/leaderboard/Leaderboard.css
	 *
	 * @return string
	 */
	public static function makeUrlTag($file, $debug = false) {
		if ( preg_match('@^/?(.+)/([^/]+)\.(css|js)$@', $file, $m) ) {
			$type = $m[3];
			$filename = $m[2];
			$path = $m[1];
			return self::makeUrlTags($type, ["$filename.$type"], $path, $debug);
		} else {
			throw new MWException(__METHOD__ . ': incorrect url format: ' . $file);
		}
	}
}
