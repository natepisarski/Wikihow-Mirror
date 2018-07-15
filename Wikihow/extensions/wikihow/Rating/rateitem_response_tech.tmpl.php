<script>window.WH.ratings._titleText = <?= json_encode($titleText)?>;</script>
<?
// simple part, yes rating
if ( $rating == 1 ) {
	if ( $isMobile) {
		echo wfMessage('ratearticle_reason_submitted_mobile_yes')->text();
	} else {
		echo wfMessage('ratearticle_reason_submitted_yes')->text();
	}
	return;
}

$detailsPlaceholderMsg = 'rating_feedback_textarea_tech';
$buttonClick = "WH.ratings.ratingDetailsSubmit(window.WH.ratings._titleText, $rating, $(\"#ar_details\").val(), null, null, null, null, null);";
$extraInputClass = ' ar_border_thin';
$headlineClasses = 'ar_headline ar_headline_desktop';
$detailsClasses = 'ar_inner';
$wrapClasses = 'article_rating_container clearfix';

// now for no rating
if ( $isMobile) {
	$detailsPlaceholderMsg = 'rating_feedback_textarea_mobile_tech';
	$extraInputClass = ' ar_border_thick';
	$headlineClasses = 'ar_headline';
	$wrapClasses = '';
}
$detailsPlaceholderMsg = wfMessage( $detailsPlaceholderMsg )->text();
?>

<div class='<?=$wrapClasses?>'>
	<div class='<?=$detailsClasses?>' id='ar_inner_details'>
		<div class='ar_header'>
			<div class='<?=$headlineClasses?>'><?=wfMessage( 'ras_res_no_hdr' )->text()?></div>
			<div class='ar_prompt'><?=wfMessage( 'rating_feedback_prompt_tech' )->text()?></div>
		</div>
		<form class='ar_form' id='ar_form_details' action='javascript:void(0);'>
		<textarea placeholder='<?=$detailsPlaceholderMsg?>' id='ar_details' class='ar_textarea<?=$extraInputClass?>' name='submit' maxlength='254'></textarea>
		<input type='button' class='ar_submit button primary ar_button ar_submit_inactive' id='ar_submit_details' value='<?=wfMessage('submit')->text()?>' onClick='<?=$buttonClick?>' />
		</form>
	</div>
</div>
