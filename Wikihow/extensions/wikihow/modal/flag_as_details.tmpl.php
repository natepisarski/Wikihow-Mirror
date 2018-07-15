<div id="wh_modal">
<form>
	<a href="#" id="wh_modal_close">x</a>
	<div id="wh_modal_top" class="wh_modal_section"><?=wfMessage('fad_hdr')->text()?></div>
	<div class="wh_modal_section">
		<div class="wh_modal_text"><?=wfMessage('fad_txt')->text()?></div>
		<textarea class="wh_modal_textarea" id="fad_details" placeholder="<?=wfMessage('fad_ta_ph')->text()?>"></textarea>
		<div id="wh_modal_buttons">
			<input type="button" class="button primary" id="wh_modal_btn_prompt" value="<?=wfMessage('fad_btn_submit')->text()?>" />
			<input type="button" class="button secondary" id="wh_modal_btn_skip" value="<?=wfMessage('fad_btn_skip')->text()?>" />
		</div>
	</div>
</form>
</div>