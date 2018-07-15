<div>
	<label for="interests"><?=wfMessage('gh_topic_desc')->text()?></label>
	<div><input id="csui_interests" class="search_input" placeholder="<?=wfMessage('gh_topic_ph')->text()?>" autocomplete="off"></input></div>
	<?if ($suggested_cats != '') { ?><div class="csui_topic_subdesc"><?=wfMessage('gh_topic_subdesc')->text() . $suggested_cats?></div><? } ?>
	<div class="csui_categories_outer">
		<div id="csui_interests_label" class="<?=$cats_hidden?>"><?=wfMessage('csui_interests_label')->text()?></div> 
		<div class="csui_categories" id="categories">
			<?=$cats?>
			<div id="csui_none" class="<?=$nocats_hidden?>"><?=wfMessage('gs_topic_suggest')->text()?></div>
		</div>
	</div>
	<div class="csui_final_button"><a class="button primary" id="csui_close_popup"><?=wfMessage('gs_topic_done')->text()?></a></div>
</div>
