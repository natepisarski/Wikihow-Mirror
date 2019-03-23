<div id="spch-container">
	<div id="spch-head">
        <div id="spch-prompt" class="mt_prompt_box">
			<?= wfMessage('spch-mobile-question', '<span class="mt_prompt_article_title"></span>')->text();?>
		</div>
	</div>
	<div id="spch-snippet" class="clearall mt_box">
		<?=wfMessage('spch-loading-next')->text();?>
	</div>
	<div id="spch-options" class="mt_button_bar">
		<a href="#" class="button primary spch-button-yes op-action" id="spch-yes" data-event_action="edit"><?= wfMessage('spch-yes')->text(); ?></a>
		<a href="#" class='button secondary' id="spch-skip-article" data-event_action="skip"><?= wfMessage("spch-skip-mobile")->text(); ?></a>
		<a href="#" class='button secondary op-action' id="spch-no" data-event_action="vote_up"><?= wfMessage("spch-no")->text(); ?></a>
	</div>
	<div id="spch-edit-buttons" class="mt_button_bar">
		<a href="#" class='button primary' id="spch-next" data-event_action="save_edit"><?= wfMessage("spch-next")->text(); ?></a>
		<a href="#" class='button secondary' id="spch-cancel" data-event_action="cancel_edit"><?= wfMessage("spch-cancel")->text(); ?></a>
	</div>

	<?=$articleWidgetHtml?>
	<?=$tool_info?>
	<div class='spch-waiting'><img src='<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>' alt='' /></div>
</div>
