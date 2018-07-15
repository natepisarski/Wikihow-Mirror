<div id="<?=$id?>" class="wh_search <?=$class?>" role="search">
	<div class="cse_x" role="button"></div>
	<? // below is the modified output of: GoogSearch::getSearchBox("cse-search-box") ?>
	<? //<form action="/Special:GoogSearch" id="cse-search-box"> ?>
	<form action="/wikiHowTo" id="<?= (@$form_id ? $form_id : 'cse-search-box') ?>">
		<div>
			<input type="text" class="cse_q search_box" name="search" value="" placeholder="<?=$placeholder?>" x-webkit-speech aria-label="<?= wfMessage('aria_search')->showIfExists() ?>" />
			<input type="submit" value="" class="cse_sa" alt="" />
		</div>
	</form>
</div>
<? /* Google CSE - Not used as of June 2017 - Alberto ?>
<div id="<?=$id?>" class="wh_search <?=$class?>" role="search">
	<div class="cse_x" role="button"></div>
	<? // below is the modified output of: GoogSearch::getSearchBox("cse-search-box") ?>
	<? //<form action="/Special:GoogSearch" id="cse-search-box"> ?>
	<form action="//cse.google.<?=wfMessage('cse_domain_suffix')?>/cse" id="cse-search-box">
		<div>
			<input type="hidden" name="cx" value="<?=wfMessage('cse_cx')?>" />
			<!--<input type="hidden" name="cof" value="FORID:10" />-->
			<input type="hidden" name="ie" value="UTF-8" />
			<input type="text" class="cse_q search_box" name="q" value="" placeholder="<?=$placeholder?>" x-webkit-speech />
			<input type="submit" value="" class="cse_sa" alt="" />
		</div>
	</form>
	<form action="/wikiHowTo" id="cse-search-box-noscript" style="display:none;">
		<div>
			<input type="text" class="cse_q search_box" name="search" value="" placeholder="<?=$placeholder?>" x-webkit-speech />
			<input type="submit" value="" class="cse_sa" alt="" />
		</div>
	</form>
</div>
<? */ ?>
<!--end search-->
