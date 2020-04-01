<div id="rcl-container" class="tool">
	<div id='rcl-header'>
		<div id="rcl-header-title"  class="mt_prompt_box">
			<span id="rcl-edit-type"></span>
		</div>
		<div id="rcl-toolbar" class="mt_button_bar">
				<a id="rcl-yes" href="#" class="button secondary op-action" data-event_action="mark_patrolled"><?=wfMessage('rcl-yes')->text()?></a>
				<a id="rcl-unsure" href="#" class="button primary" data-event_action="skip"><?=wfMessage('rcl-unsure')->text()?></a>
				<a id="rcl-no" href="#" class="button secondary op-action"  data-event_action="rollback"><?=wfMessage('rcl-no')->text()?></a>
		</div>
	</div>

	<div id='rcl-content'>
		<div id='rcl-preview'></div>
		<div class='rcl-waiting <?=$anon ? "" : "loggedin"?>'>
			<div id="rcl-waiting-heading"><?=wfMessage('rcl-waiting-initial-heading')->text()?></div>
			<div id="rcl-waiting-subheading"><?=wfMessage('rcl-waiting-initial-sub')->text()?></div>
			<div id="rcl-waiting-spinner"></div>
		</div>
	</div>
</div>
