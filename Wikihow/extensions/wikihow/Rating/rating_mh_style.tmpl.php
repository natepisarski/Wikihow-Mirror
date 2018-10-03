<script>
	window.WH.ratings._titleText = <?= json_encode($titleText)?>;
</script>

<?
if (!$isMobile) {
	$headlineClasses = 'ar_headline ar_headline_desktop';
	$innerClassesArr = array('ar_inner');
	$extraInputClass = ' ar_border_thin';
	$extraButtonClass = ' ar_button_short';
?>
<div class='article_rating_container clearfix'>
<?
} else {
	$headlineClasses = 'ar_headline';
	$innerClassesArr = array('ar_inner', 'ar_inner_mobile');
	$extraInputClass = ' ar_border_thick';
	$extraButtonClass = ' ar_button_long';
}
if ($rating == 0) {
	$reasonClasses = implode(' ', $innerClassesArr);
	$detailsClassesArr = array_merge($innerClassesArr, array('ar_hidden'));
	$detailsClasses = implode(' ', $detailsClassesArr);
	$detailsHeadlineMsg = 'ratearticle_notrated_details_headline';
	$detailsPromptMsg = 'ratearticle_notrated_details_prompt';
	$detailsPlaceholderMsg = 'ratearticle_notrated_textarea';
?>
	<div class='<?=$reasonClasses?>' id='ar_inner_radios'>
		<div class='ar_header'>
			<div class='<?=$headlineClasses?>'>
				<?=wfMessage('ratearticle_notrated_headline')->text()?>
			</div>
			<div class='ar_prompt'>
				<?=wfMessage('ratearticle_notrated_prompt')->text()?>
			</div>
		</div>
		<form class='ar_form' id='ar_form_radios' action='javascript:void(0);'>
			<div class='ar_radios'>
<?
	$responses = wfMessage('ratearticle_notrated_responses')->text();
	$responses = explode("\n", $responses);
	foreach ($responses as $response) {
		$msg = wfMessage($response)->text();
?>
				<label for='<?=$response?>' class='ar_radio_label'>
					<input type='radio' class='ar_radio' name='ar_radio' id='<?=$response?>' value='<?=$response?>'></input><?=$msg?>
				</label>
<?
	}
?>
			</div>
			<input type='button' class='ar_submit button primary ar_button ar_submit_inactive<?=$extraButtonClass?>' id='ar_submit_reason' value='<?=wfMessage('submit')->text()?>' onClick='WH.ratings.ratingReasonSubmit(window.WH.ratings._titleText, 0, $(".ar_radios input[name=ar_radio]:checked").val(), "<?=$ratingId?>");' />
			<div class='ar_spinner ar_submit_inactive'>
				<img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='<?=wfMessage('ar_submitting')->text()?>' />
			</div>
		</form>
	</div>
<?
} else {
	$detailsClasses = implode(' ', $innerClassesArr);
	$detailsHeadlineMsg = 'ratearticle_rated_details_headline';
	$detailsPromptMsg = 'ratearticle_rated_details_prompt';
	$detailsPlaceholderMsg = 'ratearticle_rated_textarea';
	$firstnamePlaceholderMsg = 'ratearticle_firstname';
	$lastnamePlaceholderMsg = 'ratearticle_lastname';
}
?>
	<div class='<?=$detailsClasses?>' id='ar_inner_details'>
		<div class='ar_header'>
			<div class='<?=$headlineClasses?>'>
				<?=wfMessage($detailsHeadlineMsg)->text()?>
			</div>
			<div class='ar_prompt'>
				<?=wfMessage($detailsPromptMsg)->text()?>
			</div>
		</div>
		<form class='ar_form' id='ar_form_details' action='javascript:void(0);'>
			<textarea placeholder='<?=wfMessage($detailsPlaceholderMsg)->text()?>' id='ar_details' class='ar_textarea<?=$extraInputClass?>' name='submit' maxlength='254'></textarea>
<? if ($rating > 0) { ?>
			<div id="ar_user_info" class="ar_public_info">
				<input type="text" name="ar_lastname" id="ar_lastname" class="ar_border_thin" placeholder="<?=wfMessage($lastnamePlaceholderMsg)->text()?>" />
				<input type="text" name="ar_firstname" id="ar_firstname" class="ar_border_thin" placeholder="<?=wfMessage($firstnamePlaceholderMsg)->text()?>" />
			</div>
			<p id="ar_public_prompt"><?=wfMessage("ratearticle_publicprompt")->text()?></p>
			<input type="radio" class="ar_public" name="ar_public" id="ar_public_radio_yes" value="yes" /> <label for="ar_public_radio_yes" class="ar_public_label"><?=wfMessage('ratearticle_publicyes')->text()?></label> <br />
			<input type="radio" class="ar_public" name="ar_public" id="ar_public_radio_no" value="no" /> <label for="ar_public_radio_no" class="ar_public_label"><?=wfMessage('ratearticle_publicno')->text()?></label><br /><br />
			<div id="ar_public_info" class="ar_public_info">
				<p id="ar_public_error" style="display:none;"><?= wfMessage("ratearticle_error")->text()?></p>
				<p><?= wfMessage("ratearticle_public_agree")->text()?></p>
			</div>
			<input type='button' class='ar_submit button primary ar_button ar_submit_inactive<?=$extraButtonClass?>' id='ar_submit_details' value='<?=wfMessage('submit')->text()?>' onClick='WH.ratings.ratingDetailsSubmit(window.WH.ratings._titleText, <?=$rating?>, $("#ar_details").val(), $("#ar_email").val(), null, $("input[name=ar_public]:checked").val(), $("#ar_firstname").val(), $("#ar_lastname").val());' />
<? } else { ?>
			<input placeholder='<?=wfMessage('ratearticle_input_email')->text()?>' id='ar_email' class='ar_input<?=$extraInputClass?>' />
			<input type='button' class='ar_submit button primary ar_button ar_submit_inactive<?=$extraButtonClass?>' id='ar_submit_details' value='<?=wfMessage('submit')->text()?>' onClick='WH.ratings.ratingDetailsSubmit(window.WH.ratings._titleText, <?=$rating?>, $("#ar_details").val(), $("#ar_email").val(), $(".ar_radios input[name=ar_radio]:checked").val());' />
<? } ?>
			<div class='ar_spinner ar_submit_inactive'>
				<img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='<?=wfMessage('ar_submitting')->text()?>' />
			</div>
		</form>
	</div>
	<div class='clearall'></div>
<?
if (!$isMobile) {
?>
</div>
<?
}
?>

