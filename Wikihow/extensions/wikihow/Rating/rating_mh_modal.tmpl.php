<script>window.WH.ratings._titleText = <?= json_encode($titleText)?>;</script>

<?
if ( $rating == 1 ) {
	//YES!!!
	$detailsHeadlineMsg = 'ratearticle_rated_details_headline';
	$detailsPlaceholderMsg = 'ratearticle_notrated_textarea'; // Reuse the notrated one
	$detailsPromptMsg = 'ratearticle_rated_details_prompt';
}
else {
	//NOOOOOOOOOOOOO
	$detailsHeadlineMsg = 'ratearticle_notrated_details_headline';
	$detailsPlaceholderMsg = 'ratearticle_notrated_textarea';
	$detailsPromptMsg = 'ratearticle_notrated_details_prompt';
}

if ($tech_article) $detailsPromptMsg = 'rating_feedback_prompt_tech';
?>

<div class='article_rating_container clearfix'>
	<div class='ar_inner' id='ar_inner_details'>
		<div class='ar_header'>
			<div class='ar_headline ar_headline_desktop'>
				<?=wfMessage($detailsHeadlineMsg)->text()?>
			</div>
			<div class='ar_prompt'>
				<?=wfMessage($detailsPromptMsg)->text()?>
			</div>
		</div>
		<form class='ar_form' id='ar_form_details' action='javascript:void(0);'>
			<textarea placeholder='<?=wfMessage($detailsPlaceholderMsg)->text()?>' id='ar_details' class='ar_textarea ar_border_thin' name='submit' maxlength='254'></textarea>
			<input placeholder='<?=wfMessage('ratearticle_input_email')->text()?>' id='ar_email' class='ar_input ar_border_thin' />
			<input type='button' class='button secondary ar_button wh_modal_close' value='<?=wfMessage('modal_close')->text()?>' />
			<input type='button' class='ar_submit button primary ar_button ar_submit_inactive ar_button_short' id='ar_submit_details' value='<?=wfMessage('submit')->text()?>' onClick='WH.ratings.ratingDetailsSubmit(window.WH.ratings._titleText, <?=$rating?>, $("#ar_details").val(), $("#ar_email").val(), $(".ar_radios input[name=ar_radio]:checked").val());' />
			<div class='ar_spinner ar_submit_inactive'>
				<img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='<?=wfMessage('ar_submitting')->text()?>' />
			</div>
		</form>
	</div>
	<div class='clearall'></div>
</div>
