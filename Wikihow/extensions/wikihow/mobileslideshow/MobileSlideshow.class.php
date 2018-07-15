<?php

/**
 * Created by PhpStorm.
 * User: bebeth
 * Date: 1/27/16
 * Time: 2:18 PM
 */
class MobileSlideshow
{
	static function getHtml() {
		$tmpl = new EasyTemplate(dirname(__FILE__));
		return $tmpl->execute('mobileslideshow.tmpl.php');
	}
}
