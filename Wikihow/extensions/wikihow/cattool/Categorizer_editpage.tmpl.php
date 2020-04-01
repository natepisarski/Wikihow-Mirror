<div id="cat_head_outer">
	<div id="cat_spinner">
		<img src="/extensions/wikihow/rotate.gif" alt="" />
	</div>
	<div id="cat_head">
		<div id="cat_list_header">
			<b><?= wfMessage('cat_currently_in')->text() ?></b>
			<span id="cat_list">
				<? 
				$nodisplay = "";
				if (!empty($cats)) { 
					echo $cats;
					$nodisplay = "style = 'display: none'";
				} 
				?>
				<span id='cat_none' <?=$nodisplay?>><?= wfMessage('cat_search_below')->text() ?></span>
			</span>
		</div>
	</div>
</div>
<div id="cat_notify"><?= wfMessage('cat_max_two')->text() ?></div>
<div id="cat_tree"><?=$tree?></div>
<div id="cat_ui">
	<div id="cat_search_outer">
		<div id="cat_search_box">
			<input id="cat_search" class="search_input" />
			<input type="button"  id="cat_search_button" class="cat_search_button button primary" value="<?= wfMessage('search')->text() ?>"></input>
		</div>
	</div>
	<br />
	<div class="wh_block">
		<div id='cat_breadcrumbs_outer'>
			<ul id='cat_breadcrumbs' class='ui-corner-all'></ul>
			<a class="cat_breadcrumb_add button secondary" href="#"><?= wfMessage('add')->text()?></a>
			<div class="cat_subcats_outer">
				<div><b class="whb"><?=$cat_subcats_label?></b></div>
				<div class="cat_subcats cat_multicolumn">
					<ul id="cat_subcats_list"></ul>
				</div>
			</div>
		</div>
		<div id="cat_options">
			<a href="#" class="button primary op-action" id="cat_save_editpage"><?= wfMessage('cat_update_categories')->text() ?></a>
			<a href="#" class="button secondary" id="cat_cancel"><?= wfMessage('cancel')->text()?></a>
		</div>
		<br />
	</div>
</div>
<?=$js;?>
