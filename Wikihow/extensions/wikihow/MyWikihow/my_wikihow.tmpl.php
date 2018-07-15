<div id="my_wikihow">
	<div id="mwh_hdr"><?=$hdr?></div>
	
	<div id="mwh_cats">
		<? foreach ($cats as $cat) { ?>
			<div class="mwh_catbox">
				<div class="mwh_catcall"><?=$cat?></div>
			</div>
		<? } ?>
	</div>
	<input type="button" value="<?=$btn?>" class="button primary mwh_done clearall" />
	
	<div id="mwh_articles">
		<div id="mwh_question">
			<div id="mwh_question_text"><?=wfMessage('mywikihow_beta_text')->text()?></div>
			<input type="button" value="<?=wfMessage('mywikihow_no')->text()?>" class="button secondary mwh_no" />
			<input type="button" value="<?=wfMessage('mywikihow_yes')->text()?>" class="button primary mwh_yes" />
	</div>
</div>
<br class="clearall" />