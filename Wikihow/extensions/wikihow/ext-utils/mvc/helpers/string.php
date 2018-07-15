<?

function pluralize($str) {
	return ActiveRecord\Utils::pluralize($str);
}

function singularize($str) {
	return ActiveRecord\Utils::singularize($str);
}

function blankIfZero($num) {
	return $num > 0 ? $num : '';
}

function underscorify($str) {
	return ActiveRecord\Inflector::instance()->underscorify($str);
}

function autoLink($str) {
	$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
	if (preg_match($reg_exUrl, $str, $url))
		return preg_replace($reg_exUrl, "<a href='{$url[0]}'>{$url[0]}</a>", $str);
	else
		return $str;
}
