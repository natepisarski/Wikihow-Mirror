<hr class='article_rating_divider' /><br />
<div class='article_rating_container clearfix'>
  <form id='rating_feeback' name='rating_reason' method='GET'>

<? if ($rating > 0) { ?>
<div class='article_rating_message'><?=wfMessage('ratearticle_rated')->text()?></div>
<? } else { ?>
  <div class='article_rating_message'><?=wfMessage('ratearticle_notrated')->text()?></div>
  <div id='article_rating_more'>
<?
  $responses = wfMessage('ratearticle_notrated_responses')->text();
  $responses = explode("\n", $responses);
  foreach ($responses as $response) {
    $msg = wfMessage($response)->text();
?>
    <label for='<?=$response?>' class='article_rating_detail'>
      <input type='radio' name='ar_radio' id='<?=$response?>' value='<?=$response?>'></input><?=$msg?>
    </label>
  <? } ?>
  </div>
<? } ?>

    <textarea placeholder='<?=wfMessage("Ratearticle_notrated_textarea")->text()?>' id='article_rating_feedback' class='input_med article_rating_textarea' name='submit' maxlength='254'></textarea>
    <div class='article_rating_inner'>
      <input placeholder='<?=wfMessage("Ratearticle_input_name")->text()?>' id='article_rating_name' name='name' class='input_border article_rating_input'>
      <input placeholder='<?=wfMessage("Ratearticle_input_email")->text()?>' id='article_rating_email' name='email' class='input_border article_rating_input'>
<? if ($rating > 0) { ?>
	  <input type='button' class='rating_submit button primary article_rating_button' value='<?=wfMessage("Submit")?>' onClick='WH.ratings.ratingReason($("#article_rating_feedback").val(), "<?=$titleText?>", "article", 1, $("#article_rating_name").val(), $("#article_rating_email").val());'>
<? } else { ?>
<input type='button' class='rating_submit button primary article_rating_button' value='<?=wfMessage("Submit")?>' onClick='WH.ratings.ratingReason($("#article_rating_feedback").val(), "<?=$titleText?>", "article", 0, $("#article_rating_name").val(), $("#article_rating_email").val(), $(".article_rating_detail input[name=ar_radio]:checked").val(), "<?=$ratingId?>");'>
<? } ?>
    </div>
    <div class='clearall'></div>
  </form>
</div>

