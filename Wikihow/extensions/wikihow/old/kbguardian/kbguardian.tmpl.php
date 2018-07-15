<div id="kbg-container" class="tool">
	<div id='kbg-header'>
		<div id="kbg-title" class="mt_prompt_box">
			<span id="kbg-prompt"><?=wfMessage('kbg-prompt-mobile', '<span id="kbg-article-title" class="mt_prompt_article_title"></span>')->text()?></span>
		</div>

		<div id="kbg-toolbar" class="mt_button_bar">
			<a id="kbg-yes" href="#" class="button secondary op-action" data-event_action="vote_up"><?=wfMessage('kbg-yes')?></a>
			<a id="kbg-unsure" href="#" class="button primary" data-event_action="not_sure"><?=wfMessage('kbg-unsure')?></a>
			<a id="kbg-no" href="#" class="button secondary op-action" data-event_action="vote_down"><?=wfMessage('kbg-no')?></a>
		</div>
	</div>

	<div id='kbg-content'>
		<div id="kbg-knowledge" class="mt_box"></div>
		<?=$articleWidgetHtml?>
		<div class='kbg-waiting <?=$anon ? "" : "loggedin"?>'>
			<div id="kbg-waiting-heading"><?=wfMessage('kbg-waiting-initial-heading')?></div>
			<div id="kbg-waiting-subheading"><?=wfMessage('kbg-waiting-initial-sub')?></div>
			<div id="kbg-waiting-spinner"></div>
		</div>
	</div>
	<?=$tool_info?>
</div>
