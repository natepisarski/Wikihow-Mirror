<div id="uci_sidebox" class="sidebox" style="display:none;">
	<div class="sidebox_top">
		<h3><?=$headername?></h3> · <span class="ucis_count"><?=$totalCount?></span>
	</div>
	<div id="uci_images">
		<? foreach( $thumbs as $thumb): ?>
			<a class='uci_thumbnail swipebox ucis_swipebox' pageid=<?=$thumb['pageId'] ?> href=<?=$thumb['lbSrc'] ?>><img src='<?=$thumb['src']?>' alt='' /></a>
		<? endforeach; ?>
		<div id="uci_fileinput_square" class="uci_fileinput_square_bg">
			<div id="uci_fileinput_spin" ></div>
			<div class="uci_fileinput_loading"><?=$loadingMessage ?></div>
			<div class="uci_fileinput_center"><?=$addPhotoMessage ?></div>
			<input id="uci_fileupload" class="op-action" type="file" name="wpUploadImage" aria-label="<?= wfMessage('aria_upload_image')->showIfExists() ?>">
			<div id="files" class="files"></div>
		</div>
		<? if ($totalCount == 0): ?>
			<div id="uci_fileinput_instructions" class="">
				<?=$uciupload_instructions ?>
			</div>
		<? endif; ?>
	</div>
	<div id="uci_upload_response"></div>
	<? if ($end == false): ?>
		<div class='uci_more'><a href='#'>View more</a></div>
	<? endif; ?>
</div>
<script>
window.WH = window.WH || {};
WH.ucilightbox = <?=$lightboxthumbs?>;
</script>

