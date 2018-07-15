<div id="spch-container" class="tool">
	<div id="spch-head" class="spch-head tool_header">
	<p id="spch_help" class="tool_help"><a href="/Use-the-wikiHow-Spell-Checker" target="_blank">Learn how</a></p>
        <div id="spch-prompt">
		<h1><?= wfMessage('spch-question'); ?></h1>
		</div>
		<div id="spch-snippet" class="spch-snippet clearall">
			<?=wfMessage('spch-loading-next')->text();?>
        </div>
		<div id="spch-options">
			<a href="#" class='button secondary' id="spch-skip-article" data-event_action="skip"><?= wfMessage("spch-skip-article"); ?></a>
			<a href="#" class='button secondary op-action' id="spch-qe" data-event_action="edit"><?= wfMessage("spch-qe"); ?></a>
			<a href="#" class='button secondary op-action' id="spch-no" data-event_action="vote_up"><?= wfMessage("spch-no"); ?></a>
			<a href="#" class='button secondary' id="spch-skip" data-event_action="not_sure"><?= wfMessage("spch-skip"); ?></a>
			<a href="#" class="button primary spch-button-yes op-action" id="spch-yes" data-event_action="edit"><?= wfMessage('spch-yes'); ?></a>

		</div>
		<div id="spch-edit-buttons">
			<a href="#" class='button primary' id="spch-next" data-event_action="save_edit"><?= wfMessage("spch-next"); ?></a>
			<a href="#" class='button secondary' id="spch-cancel" data-event_action="cancel_edit"><?= wfMessage("spch-cancel"); ?></a>
		</div>
	</div>
	<div id='spch-preview'></div>
	<div id='spch-id'></div>
	<div class='spch-waiting'><img src='<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>' alt='' /></div>
</div>
