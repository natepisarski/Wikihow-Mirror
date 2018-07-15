<div id="kbg-container" class="tool">
	<div id='kbg-header'>
		<div id="kbg-header-title">
			<div id="kbg-tool-title" class="special_title"><?=wfMessage('kbguardian_title')?></div>
			<div id="kbg-article-title"></div>
		</div>
		<div id="kbg-prompt"><?=wfMessage('kbg-prompt')?></div>
		<div id="kbg-sub-prompt"><?=wfMessage('kbg-sub-prompt')?></div>
		<div id="kbg-knowledge"><?=wfMessage('kbg-knowledge-loading')?></div>
		<div id="kbg-toolbar">
			<a id="kbg-yes" href="#" class="button secondary op-action" data-event_action="vote_up">
				<?=wfMessage('kbg-yes')?>
			</a>
			<a id="kbg-unsure" href="#" class="button primary" data-event_action="not_sure">
				<?=wfMessage('kbg-unsure')?>
			</a>
			<a id="kbg-no" href="#" class="button secondary op-action" data-event_action="vote_down">
				<?=wfMessage('kbg-no')?>
			</a>
		</div>
	</div>

	<div id='kbg-content'>
		<div id='kbg-preview'></div>
		<div class='kbg-waiting <?=$anon ? "" : "loggedin"?>'>
			<div id="kbg-waiting-heading"><?=wfMessage('kbg-waiting-initial-heading')?></div>
			<div id="kbg-waiting-subheading"><?=wfMessage('kbg-waiting-initial-sub')?></div>
			<div id="kbg-waiting-spinner"></div>
		</div>
	</div>

</div>
