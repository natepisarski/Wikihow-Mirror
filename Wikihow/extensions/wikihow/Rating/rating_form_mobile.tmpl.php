<div id="article_rating_mobile" class="section_text">
	<h2 id="article_rating_header"><span class="mw-headline"><?=$msgText?></span></h2>
	<div id="article_rating">
		<button id="gatAccuracyYes" pageid="<?=$pageId?>" role="button" tabindex="0" class="button secondary vote_up aritem" <? if ($amp) { ?>on="tap:amp-rate-form-yes.submit"<? } ?>><?=$yesText?></button>
		<button id="gatAccuracyNo" pageid="<?=$pageId?>" role="button" tabindex="1" class="button secondary vote_down aritem" <? if ($amp) { ?>on="tap:amp-rate-form-no.submit"<? } ?>><?=$noText?></button>
	</div>
	<? if ($amp) { ?>
	<form id="amp-rate-form-yes" method="post" action-xhr="/Special:RateItem" target="_top" on="submit-success:article_rating.hide,article_rating_header.hide">
		<input type="hidden" class="amp_input" name="action" value="rate_page" />
		<input type="hidden" class="amp_input" name="page_id" value="<?=$pageId?>" />
		<input type="hidden" class="amp_input" name="type" value="article_mh_style" />
		<input type="hidden" class="amp_input" name="source" value="mobile" />
		<input type="hidden" class="amp_input" name="amp" value="1" />
		<input type="hidden" class="amp_input" name="rating" value="1" />
		<div submit-success><?=$amp_form_yes_response?></div>
		<div submit-error><?=$amp_form_yes_response?></div>
	</form>
	<form id="amp-rate-form-no" method="post" action-xhr="/Special:RateItem" target="_top" on="submit-success:article_rating.hide,article_rating_header.hide">
		<input type="hidden" class="amp_input" name="action" value="rate_page" />
		<input type="hidden" class="amp_input" name="page_id" value="<?=$pageId?>" />
		<input type="hidden" class="amp_input" name="type" value="article_mh_style" />
		<input type="hidden" class="amp_input" name="source" value="mobile" />
		<input type="hidden" class="amp_input" name="amp" value="1" />
		<input type="hidden" class="amp_input" name="rating" value="0" />
		<div submit-success><?=$amp_form_no_response?></div>
		<div submit-error><?=$amp_form_no_response?></div>
	</form>
	<? } ?>
</div>
