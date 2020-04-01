<div id="wh_modal">
	<a href="#" id="wh_modal_close">x</a>
	<div id="wh_modal_top" class="wh_modal_section">
		<?=wfMessage('expertise_hdr')->text()?>
		<div id="wh_modal_subhdr"><?=wfMessage('expertise_subhdr')->text()?></div>
	</div>
	<div class="wh_modal_section">
		<input type="text" id="expertise_cat" class="wh_modal_input" placeholder="<?=wfMessage('expertise_input_ph')->text()?>" />
		<div class="expertise_suggest">
			<?=wfMessage('expertise_also')->text()?>
			<span id="expertise_sug_cats"></span>
		</div>
		<div id="wh_modal_eg">
			<div id="wh_modal_eg_hdr"><?=wfMessage('expertise_eg_hdr')->text()?></div>
			<div id="wh_modal_eg_list"><?=wfMessage('expertise_eg')->text()?></div>
			<div id="wh_modal_interests"></div>
		</div>
	</div>
	<div class="wh_modal_section" id="expertise_anon">
		<input type="text" id="expertise_anon_email" class="wh_modal_input" placeholder="<?=wfMessage('expertise_email_ph')->text()?>" />
		<div class="expertise_tinytext"><?=wfMessage('expertise_email_sub')->text()?></div>
	</div>
	<div class="wh_modal_section">
		<div id="wh_modal_buttons">
			<input type="button" class="button secondary" id="wh_modal_btn_skip" value="<?=wfMessage('expertise_btn_skip')->text()?>" />
			<input type="button" class="button primary" id="wh_modal_btn_prompt" value="<?=wfMessage('expertise_btn_done')->text()?>" />
		</div>
	</div>
</div>
