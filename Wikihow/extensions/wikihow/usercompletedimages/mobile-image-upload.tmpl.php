<div id="uci_section" class="section">
	<h2 class='mw-headline' id='uci_header'><?=$headername?></h2>
	<div id="uci_images_section" class="section_text">
		<div id="uci_images">
			<? foreach( $thumbs as $thumb): ?>
			<a class='uci_thumbnail swipebox ucis_swipebox' pageid="<?=$thumb['pageId'] ?>" href="<?=$thumb['lbSrc'] ?>">
				<div class="uci_thumb_wrapper">
					<img src='<?=$thumb['src']?>' alt='' class="defer" />
				</div>
				<div class="uci_thumbnail_description"><?=$thumb['timeago']?></div>
			</a>
			<? endforeach; ?>
			<div id="uci_fileinput_square_wrapper" class="uci_thumbnail <? if ($totalCount == 0): ?>uci_empty<? endif; ?>">
				<div id="uci_fileinput_square" class="uci_fileinput_square_bg">
					<div id="uci_fileinput_spin" ></div>
					<div class="uci_fileinput_loading"><?=$loadingMessage ?></div>
					<div class="uci_fileinput_center">
					<?
					if ($totalCount == 0) {
						echo $uciupload_instructions;
					}
					else {
						echo $addPhotoMessage;
					}
					?>
					 <div id="uci_upload_response_error"><?=wfMessage('uci_upload_error')->text()?></div>
					</div>
					<input id="uci_fileupload" class="op-action" type="file" name="wpUploadImage" aria-label="<?= wfMessage('aria_upload_image')->showIfExists() ?>">
					<div id="files" class="files"></div>
					<div class="uci_thumbnail_description"></div>
				</div>
			</div>
		</div>
		<div id="uci_upload_response"></div>
		<div id="uci_userreview_cta" data-image=""><?=wfMessage('uci_userreview_cta')->text()?></div>
		<? if ($end == false): ?>
			<div class='uci_more'><a href='#'>View more</a></div>
		<? endif; ?>
	</div>
</div>
<script>
window.WH = window.WH || {};
WH.ucilightbox = <?=$lightboxthumbs?>;
mw.loader.load( 'mobile.wikihow.uci' );
</script>
