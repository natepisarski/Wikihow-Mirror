<ul id="social_proof">
	<?php if($helpful !== null): ?>
	<li title="<?=wfMessage('sp_helpful_title')->text()?>">
	<div class="sp_circle sp_h_icon" >
		<div class="sp_h_icon_bg"></div>
		<div class="sp_h_icon_outer">
			<div class="sp_h_icon_inner" style="-webkit-transform:rotate(<?=$helpful['lowerDegrees']?>deg);-moz-transform:rotate(<?=$lowerDegrees?>deg);-o-transform:rotate(<?=$lowerDegrees?>deg);transform:rotate(<?=$helpful['lowerDegrees']?>deg);"></div>
		</div>
		<!-- this section exists to fix degrees from 180 - 195 because there is visual artifact -->
		<?php if($helpful['upperDegrees'] > 0 && $helpful['upperDegrees'] < 15): ?>
		<div class="sp_h_icon_outer">
			<div class="sp_h_icon_inner" style="transform:rotate(<?=$helpful['upperDegrees']?>deg);"></div>
		</div>
		<?php endif; ?>
		<?php if($helpful['upperDegrees'] > 0): ?>
		<div class="sp_h_icon_outer sp_h_icon_outer_second">
			<div class="sp_h_icon_inner sp_h_icon_inner_second" style="-webkit-transform:rotate(<?=$upperDegrees?>deg);-moz-transform:rotate(<?=$upperDegrees?>deg);-o-transform:rotate(<?=$upperDegrees?>deg);transform:rotate(<?=$helpful['upperDegrees']?>deg);"></div>
		</div>
		<?php endif; ?>
		<div class="sp_h_icon_donut_hole"></div>
	</div>
	<?=$helpful['value']?><?=wfMessage('sp_helpful')->text()?></li>
	<?php endif; ?>
	<li title="<?=wfMessage('sp_views_title')->text()?>"><span class="sp_circle sp_views_icon" ></span><?=$views?> <?=wfMessage('sp_views')->text()?></li>
	<li title="<?=wfMessage('sp_edited_title')->text()?>"><span class="sp_circle sp_edit_icon"></span><?=$authors?></li>
	<li title="<?=wfMessage('sp_updated_title')->text()?>"><span class="sp_circle sp_updated_icon"></span><span data-datestamp="<?=$modified?>" id="sp_modified"></span></li>
</ul>
<div class="social_proof clearall"></div>
