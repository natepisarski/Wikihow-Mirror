<div id="cat_head_outer" class="tool_header">
	<h1><?= wfMessage('cat_can_you_help')->text() ?></h1>
	<p id="cat_help" class='tool_help'><?= wfMessage('cat_need_help')->text()?>&nbsp;<a href="<?=$cat_help_url?>" target="_blank"><?= wfMessage('cat_learn_to_categorize')->text()?></a>.</p>
	<div id="cat_spinner">
		<img src="/extensions/wikihow/rotate.gif" alt="" />
	</div>
	<div id="cat_head">
		<div id="cat_aid"><?=$pageId?></div>
		<h1 id="cat_title"><a href="<?=$titleUrl?>" target="_blank"><?=$title?></a></h1>
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
				<span id='cat_none' <?=$nodisplay?>><?= wfMessage('cat_search_below')->text()?></span>
			</span>
		</div>
	</div>
</div>
<div id="cat_notify"><?= wfMessage('cat_max_two')->text() ?></div>
