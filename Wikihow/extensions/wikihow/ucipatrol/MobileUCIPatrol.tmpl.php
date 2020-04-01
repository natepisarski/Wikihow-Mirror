<div id="uci" class="tool" style="display:none">
	<div id="uci_header" class="tool_header fixed_tool_header">
		<div id="uci_title_header" class="mt_prompt_box">
			<?= wfMessage('uci-question-mobile', '<span class="mt_prompt_article_title"></span>')->text(); ?>
		</div>
		<div id="uci_patrol_buttons" class="mt_button_bar">
			<a href="#" id="uci_resetskip" title="resetskip" class="button secondary" tabindex="2"><?= wfMessage('uci-resetskip')->text() ?></a>
			<a href="#" id="uci_errortest" title="errortest" class="button secondary" tabindex="2"><?= wfMessage('uci-errortest')->text() ?></a>
			<a href="#" id="uci_good" title="good" class="button secondary op-action" tabindex="5" data-event_action="vote_up"><?= wfMessage('uci-button-yes')->text() ?></a>
			<a href="#" id="uci_skip" title="skip" class="button primary uci_button" tabindex="4" data-event_action="not_sure"><?= wfMessage('uci-button-notsure-mobile')->text() ?></a>
			<a href="#" id="uci_bad" title="bad" class="button secondary op-action" tabindex="3" data-event_action="vote_down"><?= wfMessage('uci-button-no')->text() ?></a>
		</div>
	</div>
	<?=$articleWidgetHtml?>
	<?=$tool_info?>
	<div id="uci_img_wrap" class="mt_box_black">
		<div id="uci_img_spinner" class="spinner_black"></div>
		<div id="uci_img"></div>
	</div>
</div>
<div id="uci_info" style="display:none;">
    <?= wfMessage('ucipatrol_keys')->text(); ?>
</div>
