<div id='wh_modal' class='modal-ccpa'>
	<form>
		<a href='#' id='wh_modal_close' class='ccpa_close'>x</a>
		<div id='wh_modal_top' class='wh_modal_section'><?=$ccpa_popup_title?></div>
		<div class='wh_modal_content'>
			<div class='wh_modal_text'><?=$ccpa_notice_text?></div>
			<div class='wh_modal_line'></div>
			<div class='ccpa_opt_out_wrap'>
			<div class='wh_modal_text_second'><?=$ccpa_text_second?></div>
			</div>
			<div id='wh_modal_buttons'>
				<input type='button' class='button primary wh_modal_btn_opt_out' value='<?=$ccpa_opt_out_button?>' />
				<input type='button' class='button secondary ccpa_close' value='<?=$ccpa_no_opt_out_button?>' />
			</div>
		</div>
	</form>
</div>

