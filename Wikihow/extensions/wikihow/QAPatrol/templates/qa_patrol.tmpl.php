<div id="qap_top">
	<div id="qap_count">
		<h3></h3>
		<?=$remaining_text?>
		<div id="qap_edited">
			<h2></h2>
			<?=wfMessage('qap_edited')->text()?>
		</div>
	</div>
	<h5><?=wfMessage('cd-qap-title')->text()?></h5>
	<a href="" id="qap_article" target="_blank"></a>
</div>
<div id="qap_main">
	<?=$tool_info?>
	<div id="qap_txt"><?=wfMessage('qap_txt')->text()?></div>
	<div id="qap_q"><?=wfMessage('qap_q')->text()?></div>
	<div id="qap_question" class="wh_block"></div>
	<?if ($isPowerVoter) {?><a href="#" id="qap_delete_q"><?=wfMessage('qap_delete_q')->text()?></a><?}?>
	<div id="qap_q_cl" class="qap_char_limit"><?=wfMessage('qap_q_cl')->text()?></div>
	<div id="qap_a"><?=wfMessage('qap_a')->text()?></div>
	<div id="qap_answer" class="wh_block"></div>
	<div id="qap_err_msg"></div>
	<div id="qap_qid"></div>
	<div id="qap_user_data"></div>
	<div id="qap_a_cl" class="qap_char_limit"><?=wfMessage('qap_a_cl')->text()?></div>

	<div id="qap_buttons">
		<div id="qap_buttons_main">
			<input type="button" id="qap_btn_no" class="button secondary op-action" value="<?=wfMessage('qap_btn_no')->text()?>" />
			<input type="button" id="qap_btn_skip" class="button secondary" value="<?=wfMessage('qap_btn_skip')->text()?>" />
			<input type="button" id="qap_btn_edit" class="button secondary" value="<?=wfMessage('qap_btn_edit')->text()?>" />
			<input type="button" id="qap_btn_yes" class="button primary op-action" value="<?=wfMessage('qap_btn_yes')->text()?>" />
		</div>
		<div id="qap_buttons_edit">
			<input type="button" id="qap_btn_cancel" class="button secondary" value="<?=wfMessage('qap_btn_cancel')->text()?>" />
			<input type="button" id="qap_btn_save" class="button primary op-action" value="<?=wfMessage('qap_btn_save')->text()?>" />
		</div>
	</div>
	<div id="qap_votes_left"></div>
	<div id="qap_dup"><?=wfMessage('qap_duplicate_msg')->text()?></div>
</div>
<div id="qap_spinner"></div>
<?if ($expert_mode) {?><input type="hidden" id="expert_mode" value="1" /><?}?>
<?if ($top_answerer_mode) {?><input type="hidden" id="top_answerer_mode" value="1" /><?}?>
