<?php

Class InterfaceElements {
	public function addBubbleTipToElement($element, $cookiePrefix, $text) {
		global $wgOut;

		$wgOut->addModules('jquery.cookie');
		$wgOut->addModules('ext.wikihow.tips_bubble');

		InterfaceElements::addJSVars(array('bubble_target_id' => $element, 'cookieName' => $cookiePrefix.'_b'));

		$tmpl = new EasyTemplate(__DIR__);

		$tmpl->set_vars(array('text' => $text));
		$wgOut->addHTML($tmpl->execute('TipsBubble.tmpl.php'));
	}

	public function addJSVars($data) {
		global $wgOut;
		$text = "";
		foreach ($data as $key => $val) {
			$text .= "var ".$key." = ".json_encode($val).";";
		}
		$wgOut->addHTML(Html::inlineScript("\n$text\n") . "\n");
	}
}
