<div>
	<div><?=wfMessage('ccm_inst')->text()?></div>
	<div><input type="text" id="ccm_category" autocomplete="off" class="ccm_input" value="" placeholder="<?=wfMessage('ccm_cat_ph')->text()?>" /><div>
	<div>
		<?=wfMessage('ccm_mwm_hdr')->text()?><br />
		<input type="text" id="ccm_mwm_link" class="ccm_input" value="" />
	</div>
	<div>
		<?=wfMessage('ccm_subject_hdr')->text()?><br />
		<input type="text" id="ccm_subject" class="ccm_input" value="" />
	</div>
	<div>
		<?=wfMessage('ccm_max_num')->text()?><br />
		<input type="text" id="ccm_max_num" class="ccm_input" value="<?=$max_num?>" />
		<div id="ccm_total_max"></div>
	</div>
	<div>
		<?=wfMessage('ccm_source')->text()?><br />
		<input type="text" id="ccm_source" class="ccm_input" value="" />
	</div>
	<div>
		<?=wfMessage('ccm_num_contacted')->text()?><br />
		<select id="ccm_num_contacted" class="ccm_input">
			<option value="any" selected="selected"><?=wfMessage('ccm_any')->text()?></option>
			<option value="range"><?=wfMessage('ccm_range')->text()?></option>
		</select>
		<input id="ccm_ctd_range_num" type="text" readonly="readonly" />
		<div id="ccm_ctd_slider"></div>
	</div>
	<form>
	<div>
		<?=wfMessage('ccm_test_hdr')->text()?><br />
		<input type="text" id="ccm_test_addresses" class="ccm_input" value="" />
		<input type="submit" id="ccm_test_btn" class="button secondary" value="<?=wfMessage('ccm_test_btn')->text()?>" />
	</div>
	</form>
	<input type="button" id="ccm_send_btn" class="button primary" value="<?=wfMessage('ccm_send_btn')->text()?>" />
	<div id="ccm_results"></div>
</div>
