<?=$dialogStyle?>
<div id="created_modal">
	<i id="created_modal_close" class="fa fa-times created_modal_close"></i>
	<div id="created_modal_top" class="created_modal_section">
		<?= wfMessage('ac-modal-head')->text(); ?>
		<div id="created_modal_bar">
			<i class="fa fa-check-circle"></i>
			<div class="created_modal_line"></div>
			<i class="fa fa-check-circle"></i>
			<div class="created_modal_line wait_for_it"></div>
			<i class="fa fa-circle wait_for_it"></i>
		</div>
		<div id="created_modal_bar_step1" class="created_modal_bar_step"><?= wfMessage('ac-modal-step1')->text(); ?></div>
		<div id="created_modal_bar_step2" class="created_modal_bar_step"><?= wfMessage('ac-modal-step2')->text(); ?></div>
		<div id="created_modal_bar_step3" class="created_modal_bar_step wait_for_it"><?= wfMessage('ac-modal-step3')->text(); ?></div>
	</div>
	<div id="created_modal_email_error"><?= wfMessage('ac-modal-email-error')->text(); ?></div>
	<div class="created_modal_section">
		<div class="created_modal_email_msg"><?= wfMessage('ac-modal-email-msg')->text(); ?></div>
		<? if ($anon) { ?>
		<div class="created_modal_email_anon_msg"><?= wfMessage('ac-modal-anon-msg')->text(); ?></div>
		<? } else { ?>
		<i class="fa fa-toggle-<?=$on_off?>" id="created_modal_toggle"></i>
		<div id="created_modal_input_hdr">
			<div id="info_tooltip" class="hint_box"><?= wfMessage('ac-modal-info-tip')->text(); ?></div>
			<?= wfMessage('ac-modal-email-hdr')->text(); ?>
			<i class="fa fa-info-circle" id="created_modal_info"></i>
		</div>
			<? if ($email == '') { ?>
			<div class="created_modal_input_box" id="cm_input_box_1">
				<i class="fa fa-envelope"></i>
				<input type="text" id="cm_input" class="cm_input" placeholder="<?= wfMessage('ac-modal-email-ph')->text(); ?>" />
			</div>
			<? } else { ?>
			<input type="hidden" id="cm_input" value="<?= $email ?>" />
			<? } ?>
		<? } ?>
	</div>
	<? if (!$anon) { ?>
	<div id="created_modal_share_it">
		<div id="created_modal_share_it_hdr"><?= wfMessage('ac-modal-shareit-hdr')->text(); ?></div>
		<div id="created_modal_button_bar">
			<div id="created_modal_share_fb" class="created_modal_share_button">
				<div class="created_modal_share_button_icon"><i class="fa fa-facebook-square"></i></div>
			</div>
			<div id="created_modal_share_tw" class="created_modal_share_button">
				<div class="created_modal_share_button_icon"><i class="fa fa-twitter"></i></div>
			</div>
			<div id="created_modal_share_em" class="created_modal_share_button">
				<div class="created_modal_share_button_icon"><i class="fa fa-envelope"></i></div>
			</div>
		</div>
		<div id="created_modal_share_by_email">
			<div class="created_modal_input_box">
				<i class="fa fa-envelope"></i>
				<input type="text" id="cm_share_input" class="cm_input" placeholder="<?= wfMessage('ac-modal-email2-ph')->text(); ?>" />
			</div>
			<div id="cm_share_email_msg"><?= wfMessage('ac-modal-email2-msg') ?></div>
		</div>
	</div>
	<div class="created_modal_section" id="created_modal_bottom">
		<div id="created_modal_checkbox_div">
			<input type="checkbox" id="created_modal_checkbox" /> 
			<label for="created_modal_checkbox"><?= wfMessage('ac-modal-checkbox')->text(); ?></label>
		</div>
		<input type="button" id="created_modal_view_article_btn" class="button primary created_modal_close" value="<?= wfMessage('ac-modal-view-article-btn')->text(); ?>" /><br />
	</div>
	<? } else { //anon ?>
	<div class="created_modal_section" id="created_modal_bottom_anon">
		<input type="button" id="created_modal_view_article_btn" class="button secondary created_modal_close" value="<?= wfMessage('ac-modal-view-article-btn')->text(); ?>" />
		<input type="button" id="created_modal_signup" class="button primary created_modal_close" value="<?= wfMessage('ac-modal-sign-up')->text(); ?>" />
	</div>
	<? } ?>
</div>
