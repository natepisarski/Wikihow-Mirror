<? if ($pb_display_show): ?>
<div class="section_text userpage_section">
	<? if ($pb_showlive): ?>
		<p><strong><?= $pb_display_name ?></strong> <?= wfMessage('pb-livesin', "<strong>" . $pb_live . "</strong>")->text() ?>.</p>
	<? endif; ?>
	<p><?= ucfirst(wfMessage("pb-beenonwikihow", "<strong>" . $pb_regdate . "</strong>")->text()) ?></p>
</div>
<? endif ?>	
