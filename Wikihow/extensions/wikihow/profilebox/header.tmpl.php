<div class="pb_userdata">
	<span><?= $pb_display_name ?></span>
<? if ($pb_display_show): ?>
	<div id="profileBoxID">
	<? if ($pb_showlive): ?>
		<?= wfMessage('pb-livesin', "<span>" . $pb_live . "</span>")->text() ?>,<br />
	<? endif; ?>
	<?= wfMessage("pb-beenonwikihow", "<span>" . $pb_regdate . "</span>")->text() ?>
	<? if ($pb_showwork): ?>
	<div><?= wfMessage("pb-website")->text() ?> <a href="<?= htmlspecialchars($pb_work) ?>" rel="nofollow"><?= $pb_work ?></a></div>
	<? endif; ?>
	</div>
<? endif ?>
<? if (! $GLOBALS["wgUser"]->isAnon() && $GLOBALS['wgUser']->isEmailConfirmed() 
	&& $GLOBALS['wgUser']->isAllowed('sendemail')): ?>
	<div class="pb_contact"><a href="<?= $pb_email_url ?>">Email <?= $pb_user_name ?></a></div>
<? endif ?>
</div>
<? if (($pb_display_show) && ($pb_aboutme)): ?>
<div class="pb_aboutme" id="pb_aboutme"><?= $pb_aboutme ?></div>
<? endif; ?>
<?= $pb_social ?>
