<?php

class ToolInfo {

	// (?)
	function getTheIcon($context, array $params = []) {
		$out = $context->getOutput();
		$out->addModules('ext.wikihow.toolinfo');

		$tool_name = $context->getTitle()->getText();

		$vars = array(
			'bullets' => wfMessage('ti_'.$tool_name.'_bullets', $params)->text(),
		);

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars($vars);
		return $tmpl->execute('toolinfo.tmpl.php');
	}
}
