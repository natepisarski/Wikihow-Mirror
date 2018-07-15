<?

function partial($path, $locals=[]) {
	return MVC\Controller::getInstance()->render($path, false, true, $locals);
}

function inlineScripts() {
	$scripts = MVC\Controller::getInstance()->inlineScripts;
	return "<script>\n" . implode("\n", $scripts) . "\n</script>";
}

function addScript($script) {
	array_push(MVC\Controller::getInstance()->inlineScripts, $script);
}