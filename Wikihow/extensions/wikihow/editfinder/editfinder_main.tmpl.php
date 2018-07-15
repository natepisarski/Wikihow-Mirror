<div class="tool">
<div id="editfinder_head" class="tool_header">
	<a href="#" id="edit_keys">Get Shortcuts</a>
	<div id="editfinder_options">
		<a href="#" id="editfinder_skip" class="button secondary"><?=$nope?></a>
		<a href="#" class="button primary" id="editfinder_yes"><?=$yep?></a>
	</div>
	<h1><?=$question?></h1>
	<p id="editfinder_help" class="tool_help"><a href="/<?=$helparticle?>" target="_blank">Learn how</a></p>
	<div id="editfinder_cat_header"><span id="user_cats"></span> (<a href="" class="editfinder_choose_cats">change</a>)</div>
	<input type="hidden" id="ef_num_cats" value="<?=$ef_num_cats?>" />
</div>
<div id='editfinder_spinner'><img src='/extensions/wikihow/rotate.gif' alt='' /></div>
<div id='editfinder_preview_updated'></div>
<div id='editfinder_preview'></div>
<div id='article_contents'></div>
<div id="editfinder_cat_footer">
	Not finding an article you like?  <a href="" class="editfinder_choose_cats">Choose <?=$lc_categories?></a>
</div>
</div>
<div id="edit_info" style="display:none;">
	<?= wfMessage('editfinder_keys')->text(); ?>
</div>
<input type="hidden" id="editfinder_edittype" value="<?=$edittype?>" />