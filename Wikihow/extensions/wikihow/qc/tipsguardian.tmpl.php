<div id="tg_container">
	<div id="tg_header" class="mt_prompt_box">
		<?=wfMessage('tg_prompt', '<span id="tg_article_title" class="mt_prompt_article_title"></span>')->text()?>
	</div>
	<div id="tg_tip" class="mt_box"></div>
	<div id="tg_toolbar" class="mt_button_bar">
		<div id="buttonBlocker"></div>
		<a id="tg_yes" href="#" class="button secondary op-action" data-event_action="vote_up"><?=wfMessage('tg_yes')?></a>
		<a id="tg_unsure" href="#" class="button primary" data-event_action="skip"><?=wfMessage('tg_unsure')?></a>
		<a id="tg_no" href="#" class="button secondary op-action" data-event_action="vote_down"><?=wfMessage('tg_no')?></a>
	</div>
	
	<div id="tg_limit_reached" style="display:none;">
		<p><?= wfMessage('catch-msg-anon-limit')->text() ?></p>
		<a href="/Special:UserLogin?type=signup&amp;returnto=Special:TipsGuardian" class="button primary">
			<?= wfMessage('catch-sign-up')->text() ?>
		</a>
	</div>
	<?=$articleWidgetHtml?>
	<?=$tool_info?>
	<div id='tg_waiting'><img src='<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>' alt='' /></div>
</div>

