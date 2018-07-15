<?php

/**
 * @package MediaWiki
 * @author Krzysztof KrzyÅ¼aniak <eloy@wikia.com>
 *
 * @version: $Id: EasyTemplate.php 9427 2008-02-15 11:31:05Z eloy $
 *
 * EasyTemplate class for easy mixing HTML/JavaScript/CSS/PHP code
 */


/**
 * ideas taken from Template class by
 * Copyright (c) 2003 Brian E. Lozier (brian@massassi.net)
 *
 * set_vars() method contributed by Ricardo Garcia (Thanks!)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */


class EasyTemplate {

	private static $path = '';

	public $mPath, $mVars;

	/**
	 * public constructor
	 */
	public function __construct( $path = '' ) {
		if ( !empty($path) ) {
			$this->mPath = rtrim( $path, "/" );
		}
		else {
			$this->mPath = self::$path;
		}
		$this->mVars = array();
	}

	/**
	 * Set a bunch of variables at once using an associative array.
	 *
	 * @param array $vars array of vars to set
	 * @param bool $clear whether to completely overwrite the existing vars
	 *
	 * @author Krzysztof KrzyÅ¼aniak <eloy@wikia.com>
	 *
	 * @return void
	 */
	public function set_vars( $vars, $clear = false ) {
		if( $clear ) {
			$this->mVars = $vars;
		}
		else {
			$this->mVars = is_array( $vars )
				?  array_merge( $this->mVars, $vars )
				:  array_merge( $this->mVars, array() ) ;
		}
	}

	/**
	 * Open, parse, and return the template file.
	 *
	 * @param string string the template file name
	 *
	 * @author Krzysztof KrzyÅ¼aniak <eloy@wikia.com>
	 * @author Brian E. Lozier <brian@massassi.net>
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function execute($file) {

		wfProfileIn(__METHOD__);
		if( !strstr($file, ".tmpl.php") ) {
			$file .= ".tmpl.php";
		}

		if (!empty($this->mPath)) {
			$path = $this->mPath . "/" . $file;
		} else {
			if ($file{0} != '/') {
				throw new Exception('Must use EasyTemplate::set_path');
			} else {
				$path = $file;
			}
		}

		extract($this->mVars);
		ob_start();
		include($path);
		$contents = ob_get_clean();

		wfProfileOut(__METHOD__);
		return $contents;
	}

	/**
     * template_exists
	 *
	 * Check if template's file exists
	 *
	 * @author Piotr Molski <moli@wikia.com>
	 *
	 * @access public
	 *
	 * @param string $file: path to file with template
	 *
	 * @return boolean
	 */
	public function template_exists( $file ) {
		if( !strstr($file, ".tmpl.php") ) {
			$file .= ".tmpl.php";
		}
		return file_exists($this->mPath ."/". $file);
	}

	public static function set_path( $path ) {
		self::$path = $path;
	}

	/**
	 * utility to create and execute a WH template
	 */
	public static function html( $name, $vars = array() ) {
        $tmpl = new EasyTemplate();
		if ( !empty($vars) ) {
			$tmpl->set_vars( $vars );
		}
        return $tmpl->execute( $name );
	}

}

