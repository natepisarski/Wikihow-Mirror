<?= $cssTags ?>
<?= $jsTags ?>
<script>
WH.dashboard.allThresholds(<?= $thresholds ?>);
WH.dashboard.refreshData("global", <?= $GLOBAL_DATA_REFRESH_TIME_SECS ?>);
WH.dashboard.refreshData("user", <?= $USER_DATA_REFRESH_TIME_SECS ?>);
WH.dashboard.usernameMaxLength = <?= $USERNAME_MAX_LENGTH ?>;
WH.dashboard.priorityWidgets = <?= json_encode($priorityWidgets) ?>;
WH.dashboard.prefsOrdering = <?= json_encode($prefsOrdering) ?>;
WH.dashboard.widgetTitles = <?= json_encode($widgetTitles) ?>;
WH.dashboard.appShortCodes = <?= json_encode($appShortCodes) ?>;
</script>

<div class="comdash-container">
	<div class="wh_block">
		<div class="comdash-top">
			<?php if ($userImage): ?>
			<div id="comdash-header-info"> <?= $userImage ?>
				<span class="header-line1">Thanks <?= $userName ?>!</span>
				<span class="header-line2"><?= wfMessage('cd-welcome')->text() ?></span>
			</div>
			<? endif; ?>
			<h1 class="firstHeading" style="display:inline;">wikiHow Community</h1>
		</div>
		<div class="comdash-welcome">
			<p><?= wfMessage('cd-welcome-text'); ?></p>
			<div class="sandbox" id="comdash-sb-answerrequests">
				<?= wfMessage('cd-welcome-cta2')->parseAsBlock() ?>
			</div>
			<?php if ($needBoosterAlert): ?>
				<div id="NABcount"><div><?=$NABcount?></div></div>
				<div class="sandbox" id="comdash-sb-boosteralert">
					<div id="boostMsg"><?= wfMessage( 'cd-welcome-cta3', wfMessage('comm-dashboard-NABmessage-threshold') )->parseAsBlock() ?></div>
				</div>
			<?php else: ?>
				<div class="sandbox" id="comdash-sb-addimages">
					<?= wfMessage('cd-welcome-cta1', $tipsLink)->text() ?>
				</div>
			<? endif; ?>
			<div class="clearall"></div>
		</div>
	</div>

	<div class="minor_section">
		<h2>Things to Try First</h2>
		<div class="comdash-priorities">
			<?= call_user_func($displayWidgetsFunc, $priorityWidgets) ?>
			<div class="clearall"></div>
		</div><!--end comdash-priorities-->
	</div>
	<div class="minor_section">
		<h2>
			<?php if ($userImage): ?>
				<a id="comdash-header-customize" href="#">Customize</a>
			<? endif; ?>
			More Things to Do
		</h2>
		<div class="comdash-widgets">
			<? // get the html for the user-defined list of widgets ?>
			<?= call_user_func($displayWidgetsFunc, $userWidgets) ?>
			<div class="clearall"></div>
		</div>
		<script>$(document).ready(WH.dashboard.init);</script>
		<div class="comdash-controls">
			<a href="#" class="comdash-pause"><?= wfMessage('cd-pause-updates') ?></a> |
			<a href="#" class="comdash-settings"><?= wfMessage('cd-settings') ?></a>
		</div>
		<div id="cd-user-box"></div>
	</div>
</div><!--end comdash-container-->

<div class="cd-customize-dialog" title="<?= wfMessage('cd-customize-things-to-do') ?>">
	<div class="cust-head">
		<div class="cust-order"><?= wfMessage('cd-order') ?></div>
		<div class="cust-ttd"><?= wfMessage('cd-ttd') ?></div>
		<div class="cust-show"><?= wfMessage('cd-show') ?></div>
	</div>
	<div class="cd-customize-list">
		<ul class="cd-customize-sortable"><?php // list items go here ?></ul>
	</div>
	<div class="cd-bottom-buttons">
		<a class="cd-customize-cancel button secondary" href="#"><?= wfMessage('cd-cancel') ?></a>
		<input class="button primary cd-customize-save" value="<?= wfMessage('cd-save') ?>" />
	</div>
</div>

<div class="cd-network-loading">
	<?= wfMessage('cd-loading-stats') ?>
</div>
