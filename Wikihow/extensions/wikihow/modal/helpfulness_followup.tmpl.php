<div id="wh_modal">
<form>
	<a href="#" id="wh_modal_close">x</a>
	<div id="wh_modal_top" class="wh_modal_section"><?=wfMessage('helpfulness_followup_hdr')->text()?></div>
	<div id="hfu_default" class="wh_modal_section">
		<div class="wh_modal_text"><?=wfMessage('helpfulness_followup_txt')->text()?></div>
		<div id="wh_modal_buttons">
			<input type="button" class="button secondary" id="wh_modal_btn_skip" value="<?=wfMessage('helpfulness_followup_btn_no')->text()?>" />
			<input type="button" class="button primary" id="wh_modal_btn_prompt" value="<?=wfMessage('helpfulness_followup_btn_yes')->text()?>" />
		</div>
	</div>
	
	<div id="hfu_methods" class="wh_modal_section">
		<span id="hfu_title"></span>
		<ul id="hfu_method_list">
			<li><input type="checkbox" id="hfu_none" /> <label for="hfu_none"><?=wfMessage('helpfulness_followup_opt_none')->text()?></label></li>
			<li><input type="checkbox" id="hfu_forgot" /> <label for="hfu_forgot"><?=wfMessage('helpfulness_followup_opt_forgot')->text()?></label></li>
		</ul>
		<div id="wh_modal_buttons">
			<input type="button" class="button secondary hfu_button" id="wh_modal_btn_skip" value="<?=wfMessage('helpfulness_followup_btn_cancel')->text()?>" />
			<input type="button" class="button primary hfu_button" id="wh_modal_btn_prompt" value="<?=wfMessage('helpfulness_followup_btn_submit')->text()?>" />
		</div>
	</div>
	
	<div id="hfu_ty" class="wh_modal_section">
		<div class="wh_modal_text"><?=wfMessage('helpfulness_followup_txt3')->text()?></div>
		<div id="wh_modal_buttons">
			<input type="button" class="button primary hfu_button" id="wh_modal_btn_prompt" value="<?=wfMessage('helpfulness_followup_btn_done')->text()?>" />
		</div>
	</div>
	
	<div id="hfu_nothanks_div">
		<input type="checkbox" id="hfu_nothanks" /> <label for="hfu_nothanks"><?=wfMessage('helpfulness_followup_nothanks')->text()?></label>
	</div>
</form>
<input type="hidden" id="hfu_article" value="<?=$title?>" />
</div>