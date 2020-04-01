<div class='nfd_modal'>
	<p><?= wfMessage('nfd_conf_question', $titleUrl, $title)->text() ?> </p>
	<div style='clear:both'></div>
	<br />
	<span style='float:right'>
		<input class='button op-action nfdg_confirm_action' type='button' value='No' data-event_action='template_keep' >
		<input class='button primary op-action nfdg_confirm_action' type='button' value='Yes' data-event_action='template_remove' >
	</span>
</div>
