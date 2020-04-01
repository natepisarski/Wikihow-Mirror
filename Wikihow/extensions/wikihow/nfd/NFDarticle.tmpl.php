
<div id='nfd_options'></div>

<div id='nfd_head' class='tool_header'>
	<p id="nfd_help" class="tool_help"><a href="/Use-the-NFD-Guardian-App-on-wikiHow" target="_blank">Learn how</a></p>
	<a href='#' class='button secondary' id='nfd_skip' data-event_action='skip'><?= wfMessage('nfd_skip_article') ?></a>
	<a href='#' class='button secondary' id='nfd_delete' data-event_action='vote_down'><?= wfMessage("nfd_button_delete") ?></a>
	<a href='#' class='button secondary' id='nfd_keep' data-event_action='vote_up'><?= wfMessage("nfd_button_keep") ?></a>
	<a href='#' class='button primary' id='nfd_save' data-event_action='edit'><?= wfMessage("nfd_button_save") ?></a>

	<?= $articleInfo ?>
</div>
<input type='hidden' id='qcrule_choices' value='' />
<div id="article_tabs">
	<ul id="tabs">
		<li><a href="#" id="tab_article" title="Article" class="nfdg_tab on">Article</a></li>
		<li><a href="#" title="Edit" id="tab_edit"><?= wfMessage('edit') ?></a></li>
		<li><span id="gatDiscussionTab"><a href="#" id="tab_discuss" title="<?= wfMessage('discuss') ?>" class="nfdg_tab"><?= wfMessage('discuss') ?></a></span></li>
		<li><a href="#" id="tab_history" title="<?= wfMessage('history') ?>" class="nfdg_tab"><?= wfMessage('history') ?></a></li>
		<li><a href="#" id="tab_helpful" title="Helpfulness" class="nfdg_tab">Helpfulness</a></li>
	</ul><!--end tabs-->
</div><!--end article_tabs-->
<div id="articleBody" class="nfd_tabs_content">
	<?= $articleHtml ?>
</div>
<div id="articleEdit" class="nfd_tabs_content"></div>
<div id="articleDiscussion" class="nfd_tabs_content"></div>
<div id="articleHistory" class="nfd_tabs_content"></div>
<div id="articleHelpfulness" class="nfd_tabs_content">
<div id="page_helpfulness_box"></div>
</div>
<input type='hidden' name='nfd_id' value='<?= $nfdId ?>'/>
