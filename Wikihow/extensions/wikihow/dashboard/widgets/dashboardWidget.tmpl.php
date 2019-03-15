<?= $header ?>
<div class="comdash-widget-body <?= $status ?>">
	<div class="comdash-weather sunny <?= $weather=='sunny'?'active':'' ?>"><?= wfMessage('cd-sunny') ?></div>
	<div class="comdash-weather stormy <?= $weather=='stormy'?'active':'' ?>"><?= wfMessage('cd-stormy') ?></div>
	<div class="comdash-weather rainy <?= $weather=='rainy'?'active':'' ?>"><?= wfMessage('cd-rainy') ?></div>
	<div class="comdash-weather cloudy <?= $weather=='cloudy'?'active':'' ?>"><?= wfMessage('cd-cloudy') ?></div>
	<div class="comdash-count">
		<?php if (isset($data['error']) || $data == null ): ?>
			<div class="cd-error"><?= wfMessage('cd-widget-error') ?></div>
			<div class="cd-count-div" style="display:none;">
		<?php else: ?>
			<div class="cd-count-div">
		<?php endif; ?>
			<span><?= $data['ct'] ?></span><?= $countDescription ?>
			<div class="comdash-today" style="display:<?= $completedToday ? 'block' : 'none' ?>;">&#x2713; done today</div>
		</div>
	</div>
	<?= $extraInternalHTML ?>
</div>
<div class="comdash-widget-footer">
	<div class='comdash-topcontributor'><div class="content"><?= $moreLink ?><span class='avatar'><?= call_user_func($getAvatarLink, $data['tp']['im']) ?></span><span>Leader</span><span class='name'><?= call_user_func($getUserLink, $data['tp']['na']) ?></span><span class='time'><?= $data['tp']['da'] ?></span></div><img src="<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>" class="waiting" /></div>
	<div class='comdash-lastcontributor'><span class='avatar'><?= call_user_func($getAvatarLink, $data['lt']['im']) ?></span><span>Last</span><span class='name'><?= call_user_func($getUserLink, $data['lt']['na']) ?></span><span class='time'><?= $data['lt']['da'] ?></span></div>
</div>
<div class="cd-info"><?= wfMessage('cd-' . $widgetMWName . '-disabled-info', $login)->parseAsBlock() ?></div>
<div class="comdash-widget-leaders">
	<div class="comdash-widget-leaders-content">
		<div class="comdash-widget-header">Leaders: <?= $title ?></div>
		<div class="comdash-widget-body">
			<table cellpadding="0" cellspacing="0">
			</table>
		</div>
		<div class="comdash-widget-footer">
			<div class='comdash-lastcontributor'><span class='avatar'><?= call_user_func($getAvatarLink, $data['lt']['im']) ?></span><span>Last</span><span class='name'><?= call_user_func($getUserLink, $data['lt']['na']) ?></span><span class='time'><?= $data['lt']['da'] ?></span></div>
			<a href="#" class="comdash-close" id="comdash-close-<?= $widgetName ?>">Done</a>
		</div>
	</div>
</div>
