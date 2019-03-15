<?=$widgetLink?>
<div class="comdash-widget-icon icon-<?=$widgetName?>"></div>
<?= $header ?>
<div class="comdash-widget-body">
	<div class="comdash-count">
		<?php if (isset($data['error']) || $data == null ): ?>
			<div class="cd-error"><?= wfMessage('cd-widget-error') ?></div>
			<div class="cd-count-div" style="display:none;">
		<?php else: ?>
			<div class="cd-count-div">
		<?php endif; ?>
		<?php if ($showCount): ?>
			<span><?= $data['ct'] ?></span><?= $countDescription ?>
		<?php endif; ?>
		</div>
	</div>
	<?= $extraInternalHTML ?>
</div>
</a>
