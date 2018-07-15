<div id="tpc_results">
	<div id="tpc_border">
		<a href="#" id="tpc_coach_close" class="tpc_coach_close_button"></a>
		<div id="tpc_img"></div>
		<div id="tpc_heading_header">Hello<span id="tpc_real_name"></span> I am the Tips Patrol Coach</div>	
		<p id="tpc_heading_details"></p>
		<div class="tpc_section" id="tpc_answer_header"></div>
		<p id="tpc_answer_details"></p>
		<div class="tpc_section" id="tpc_message_header"></div>
		<p id="tpc_message_details"></p>
		<a class="button primary tpc_coach_close_button" id="tpc_next_button">Dismiss</a>
	</div>
</div>
<div id="tip" class="tool">
	<div id="tip_header" class="tool_header">
		<a href="#" id="tips_keys">Get Shortcuts</a>
		<h1>Should we publish this tip or delete it?</h1> 
		<textarea id="tip_tip" class="mousetrap" tabindex="1"></textarea><br />
		<input id="tip_read" type="checkbox"  type="checkbox" tabindex="2"/>This tip has been fully reviewed and is ready to be published on the article.<br />
		<a href="#" id="tip_skip" title="<?=$tip_skip_title?>" class="button secondary" tabindex="3" data-event_action="skip">Skip</a>
		<a href="#" id="tip_keep" title="<?=$tip_keep_title?>" class="button primary op-action" tabindex="5" data-event_action="publish">Publish</a>
		<a href="#" title="<?=$tip_delete_title?>" id="tip_delete" class="button secondary op-action" tabindex="4" data-event_action="delete">Delete</a>
		<div class="clearall"></div>
	</div>
	<div id='tip_waiting'><img src='<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>' alt='' /></div>
	<div id="tip_article"></div>
</div>
<div id="tips_info" style="display:none;">
    <?= wfMessage('tipspatrol_keys')->text(); ?>
</div>
