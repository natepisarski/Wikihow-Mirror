<?= $cssTags ?>
<?= $jsTags ?>

<div class="comdash-container">
	<div id="comdash-header"><?=wfMessage('cd-m-header')->text()?></div>
	<div class="minor_section">
		<h2><?= wfMessage('cd-m-firstsection-header')->text() ?></h2>
		<div class="comdash-priorities">
			<?= call_user_func($displayWidgetsFunc, $priorityWidgets) ?>
			<div class="clearall"></div>
		</div><!--end comdash-priorities-->
	</div>
	<div class="minor_section">
		<h2><?= wfMessage('cd-m-secondsection-header')->text() ?></h2>
		<div class="comdash-widgets">
			<? // get the html for the user-defined list of widgets ?>
			<?= call_user_func($displayWidgetsFunc, $userWidgets) ?>
			<div class="clearall"></div>
		</div>
		<script>$(document).ready(WH.dashboard.init);</script>
		<div id="cd-user-box"></div>
	</div>
</div><!--end comdash-container-->
