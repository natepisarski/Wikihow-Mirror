<div class="minor_section">
	<h3><div class='altblock'></div><?=wfMessage('cp_title_head')?></h3>
	<div id="cp_title_results">
		<div id="cpr_title_hdr" class="cpr_hdr"></div>
		<div id="cpr_title_text" class="cpr_text"></div>
	</div>
	<div id="cp_title_input_block" class="cp_block">
		<form>
		<b><?=wfMessage('howto_prefix')->showIfExists()?></b><input autocomplete='off' maxLength='256' id='cp_title_input' name='target' value='' class='search_input' type='text' placeholder='<?=wfMessage('cp_title_ph')->text()?>' />
		<input type='submit' id='cp_title_btn' value='<?= wfMessage('cp_title_submit') ?>' class='button primary createpage_button' />
		</form>
	</div>
</div>

<?php global $wgLanguageCode; if ($wgLanguageCode == 'en') { ?>
<div class="minor_section">
	<h3><div class='altblock'></div><?=wfMessage('createpage_topic_sugg_head')?></h3>
	<div class="cp_block">
		<form>
		<input type='text' id='cp_topic_input' name='q' class="search_input" placeholder='<?=wfMessage('cp_topic_ph')->text()?>' />
		<input type='submit' id='cp_topic_btn' value='<?= wfMessage('cp_topic_submit') ?>' class='button primary createpage_button' />
		</form>
	</div>
	<div id="cp_topic_results">
		<div id="cpr_topic_hdr" class="cpr_hdr"></div>
		<div id="cpr_topic_text" class="cpr_text"></div>
	</div>
</div>
<?php } ?>

<div class="minor_section">
	<h3><div class='altblock'></div><?=wfMessage('createpage_other_head')?></h3><br />
	<div class="cp_block2">
		<?= wfMessage('cp_other_details')->text() ?>
	</div>
</div>
