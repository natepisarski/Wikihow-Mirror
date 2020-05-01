<div id='wh_modal' class='modal-printview'>
	<form>
		<a href='#' id='wh_modal_close'>x</a>
		<div id='wh_modal_top' class='wh_modal_section'>
			<?= $printview_header ?>
		</div>
		<div class='wh_modal_section'>
			<div class='wh_modal_text'>
				<?= $printview_question ?>
			</div>
			<div id='wh_modal_buttons'>
				<input type='button' class='button secondary' id='wh_modal_btn_text_only' value='<?= $printview_textonly ?>' />
				<input type='button' class='button primary' id='wh_modal_btn_incl_imgs' value='<?= $printview_images ?>' />
			</div>
		</div>
	</form>
</div>

