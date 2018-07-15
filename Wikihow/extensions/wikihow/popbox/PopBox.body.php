<?php

class PopBox {

	function getGuidedEditorButton() {
		global $wgUser;
		return "<a class='" . ($wgUser->isAnon() ? " disabled" : "") . "' id='weave_button' accesskey='" .wfMessage('popbox_accesskey') ."' href='#' " . ($wgUser->isAnon() ? " disabled='disabled' " : "") . ">" . wfMessage('popbox_add_related') . "</a>";
	}

	function getPopBoxJSAdvanced() {
		$js = "<script>window.isGuided = false;</script>";
		return $js;
	}

	function getPopBoxJSGuided() {
		$js = "<script>window.isGuided = true;</script>";
		return $js;
	}

	function getPopBoxDiv() {
	  return "
	<div id='popbox'>
		<div id='popbox_inner'></div>
	</div>
		";
	}

}

