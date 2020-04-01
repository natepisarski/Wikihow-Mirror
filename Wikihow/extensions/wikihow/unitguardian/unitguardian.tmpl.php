<div id="ug-container" class="tool">
	<div id='ug-header'>
		<div id="ug-header-title" class="mt_prompt_box">
			<span id="ug-prompt"><?=wfMessage('ug-prompt', '<span id="ug-article-title" class="mt_prompt_article_title"></span>')->text()?></span>
		</div>
		<div id="ug-convert" class="mt_box"></div>

		<div id="ug-toolbar" class="mt_button_bar">

			<a id="ug-yes" href="#" class="button secondary op-action" data-event_action="vote_up"><?=wfMessage('ug-yes')?></a>
			<a id="ug-maybe" href="#" class="button primary" data-event_action="not_sure"><?=wfMessage('ug-unsure')?></a>
			<a id="ug-no" href="#" class="button secondary op-action" data-event_action="vote_down"><?=wfMessage('ug-no')?></a>
		</div>
	</div>

	<div id='ug-content'>
		<?=$articleWidgetHtml?>
		<div class='ug-waiting <?=$anon ? "" : "loggedin"?>'>
			<div id="ug-waiting-heading"><?=wfMessage('ug-waiting-initial-heading')?></div>
			<div id="ug-waiting-subheading"><?=wfMessage('ug-waiting-initial-sub')?></div>
			<div id="ug-waiting-spinner"></div>
		</div>
	</div>
	<?=$tool_info?>
</div>
