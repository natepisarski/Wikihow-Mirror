<div id="editpage_quick_tips" class="sidebox">
	<div id="epqt_hdr"><?=wfMessage('epqt_hdr')->text();?></div>
	<? if ($is_adv): ?>
	<div class="epqt_quicktip" id="epqt_part">
		<?=wfMessage('epqt_part')->text();?>
		<div class="epqt_img"></div>
	</div>
	<? endif; ?>
	<div class="epqt_quicktip" id="epqt_step">
		<?=wfMessage('epqt_step')->text();?>
		<div class="epqt_img"></div>
	</div>
	<div class="epqt_quicktip" id="epqt_bullets">
		<?=wfMessage('epqt_bullets')->text();?>
		<div class="epqt_img"></div>
	</div>
	<div class="epqt_quicktip" id="epqt_tips">
		<?=wfMessage('epqt_tips')->text();?>
		<div class="epqt_img"></div>
	</div>
	<? if (!$is_adv): ?>
	<div class="epqt_quicktip" id="epqt_part_link">
		<?=wfMessage('epqt_part')->text();?>
		<div id="epqt_part_link_text"><?=wfMessage('epqt_part_link',$part_link)->text();?></div>
	</div>
	<? endif; ?>
</div>
