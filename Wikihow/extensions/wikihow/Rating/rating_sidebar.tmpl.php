<div id="ratearticle_sidebar" class="sidebox<?=$class?>" style="display: none;">
	<h3><?=$headerText?></h3>
	<div id="ra_side_choices">
		<div id="ratearticle_side_yes" class="ra_side_vote" data-vote="1">
			<div class="ra_side_face rasf_yes"></div>
			<?=wfMessage('ratearticle_side_yes')->text()?>
		</div>
		<div id="ratearticle_side_no" class="ra_side_vote" data-vote="0">
			<div class="ra_side_face rasf_no"></div>
			<?=wfMessage('ratearticle_side_no')->text()?>
		</div>
	</div>

<? if (!$is_intl): ?>
	<div id="ra_side_response_yes" class="ra_side_response" style="display: none;">
		<?=wfMessage('ras_res_yes_top')->text()?>
		<div><input type="button" id='ras_response' class='button primary' value='<?=wfMessage('ras_res_yes_btm')?>' /></div>
	</div>
    <? if ($showNoForm):
        $thanksMessage = htmlspecialchars(wfMessage('ratearticle_reason_submitted_mobile')->text());
        $submit = "WH.ratings.ratingDetailsSubmit(window.WH.ratings._titleText, 0, $(\"#sf_no_details\").val(), null, null, null, null, null);$(\"#ratearticle_sidebar\").html($(\"#thanks_message\").html());";
    ?>
        <div id="ra_side_response_no_form" class="ra_side_response" style="display: none;">
            <div id='ra_side_response_no_message'><?=$noMessage?></div>
            <div id='thanks_message' style="display: none;"><div class='thanks_message_text'><?=$thanksMessage?></div></div>
            <form action='javascript:void(0);'></form>
            <textarea placeholder='details here' id='sf_no_details' class='ar_border_thin' name='submit' maxlength='254'></textarea>
            <input type='button' class='button primary ar_button ar_submit_inactive' id='sf_no_submit_details' value='<?=wfMessage('submit')->text()?>' onClick='<?=$submit?>' />
        </div>
    <? else: ?>
        <div id="ra_side_response_no" class="ra_side_response" style="display: none;">
            <div id='ra_side_response_no_message'><?=$noMessage?></div>
        </div>
    <? endif; ?>
<? endif; ?>

</div>
