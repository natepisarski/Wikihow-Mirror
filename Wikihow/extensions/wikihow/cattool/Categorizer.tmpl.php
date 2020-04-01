<div class="tool">
	<?=$cat_head?>
	<div id="cat_tree"><?=$tree?></div>
	<div id="cat_ui" class="minor_section">
		<div id="cat_search_outer">
			<label for="cat_search"><b class="whb"><?=$cat_search_label?></b></label>
			<div id="cat_search_box">
				<input id="cat_search" class='tool_input'></input>
				<input type="button"  id="cat_search_button" class="cat_search_button button primary" value="<?= wfMessage('search')->text()?>"></input>
			</div>
		</div>
		<div id='cat_breadcrumbs_outer'>
			<ul id='cat_breadcrumbs' class='ui-corner-all'></ul>
			<a class="cat_breadcrumb_add button secondary" href="#"><?= wfMessage('add')->text()?></a>
			<div class ="cat_divider"></div>
			<div class="cat_subcats_outer">
				<div><b class="whb"><?=$cat_subcats_label?></b></div>
				<div class="cat_subcats cat_multicolumn">
					<ul id="cat_subcats_list"></ul>
				</div>
			</div>
		</div>
		<div id="cat_options">
			<a id="cat_skip" href="#" class="button secondary"><?= wfMessage('skip')->text()?></a>
			<a href="#" class="button primary disabled op-action" id="cat_save"><?= wfMessage('save')->text() ?></a>
		</div>
		<div class="clearall"></div>
	</div>
	<div id="cat_article">
		<?= $article ?>
	</div>
</div>
