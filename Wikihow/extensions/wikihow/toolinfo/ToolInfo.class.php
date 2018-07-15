<?php

class ToolInfo {
	
	// (?)
	function getTheIcon($context) {
		$out = $context->getOutput();
		$out->addModules('ext.wikihow.toolinfo');
		
		$tool_name = $context->getTitle()->getText();
		
		$vars = array(
			'bullets' => wfMessage('ti_'.$tool_name.'_bullets')->text(),
		);
		
		$tmpl = new EasyTemplate(dirname(__FILE__));
		$tmpl->set_vars($vars);
		return $tmpl->execute('toolinfo.tmpl.php');
	}
}
