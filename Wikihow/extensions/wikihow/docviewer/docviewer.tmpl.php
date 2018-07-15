<?= $dv_ads2 ?>
<div id="sample_options">
	<h3><?=$header_get?></h3>
	<ul id="dv_dls">
		<? if (($dv_dl_file_pdf != '') && ($dv_dl_file_xls == '')) { ?>
		<li class="dv_dl_pdf">
			<a href="<?=$dv_dl_file_pdf?>" class="dv_dl_block" id="gatSamplePdf1" target="_blank" rel="nofollow"></a>
			<div class="sample_hover">
				<div class="sidebar_carrot"></div>
				<a href="<?=$dv_dl_file_pdf?>" id="gatSamplePdf2" target="_blank" rel="nofollow"><?=$dv_download?><br />
				<span><?=$dv_dl_text_pdf?></span></a>
			</div>
		</li>
		<? } ?>
		<? if ($dv_dl_file_doc != '') { ?>
		<li class="dv_dl_doc">
			<a href="<?=$dv_dl_file_doc?>" class="dv_dl_block" id="gatSampleWord1" target="_blank" rel="nofollow"></a>
			<div class="sample_hover">
				<div class="sidebar_carrot"></div>
				<a href="<?=$dv_dl_file_doc?>" id="gatSampleWord2" target="_blank" rel="nofollow"><?=$dv_download?><br />
				<span><?=$dv_dl_text_doc?></span></a>
			</div>
		</li>
		<? } ?>
		<? if ($dv_dl_file_xls != '') { ?>
		<li class="dv_dl_xls">
			<a href="<?=$dv_dl_file_xls?>" class="dv_dl_block" id="gatSampleXls1" target="_blank" rel="nofollow"></a>
			<div class="sample_hover">
				<div class="sidebar_carrot"></div>
				<a href="<?=$dv_dl_file_xls?>" id="gatSampleXls2" target="_blank" rel="nofollow"><?=$dv_download?><br />
				<span><?=$dv_dl_text_xls?></span></a>
			</div>
		</li>
		<? } ?>
		<? if ($dv_dl_file_txt != '') { ?>
		<li class="dv_dl_txt">
			<a href="<?=$dv_dl_file_txt?>" class="dv_dl_block" id="gatSampleTxt1" target="_blank" rel="nofollow"></a>
			<div class="sample_hover">
				<div class="sidebar_carrot"></div>
				<a href="<?=$dv_dl_file_txt?>" id="gatSampleTxt2" target="_blank" rel="nofollow"><?=$dv_download?><br />
				<span><?=$dv_dl_text_txt?></span></a>
			</div>
		</li>
		<? } ?>
		<? if ($dv_dl_file_ext != '') { ?>
		<li class="dv_dl_ext">
			<a href="<?=$dv_dl_ext_prefix.$dv_dl_file_ext?>" target="_blank" class="dv_dl_block" id="gatSampleExtLink1" rel="nofollow"></a>
			<div class="sample_hover">
				<div class="sidebar_carrot"></div>
				<a href="<?=$dv_dl_ext_prefix.$dv_dl_file_ext?>" target="_blank" id="gatSampleExtLink2" rel="nofollow"><?=$dv_open_in?><br />
				<span><?=$dv_dl_text_ext?></span></a>
			</div>
		</li>
		<? } ?>
	</ul>
</div>
<?=$dv_sample_html?>
<?=RateItem::showForm('sample')?>
<script type="text/javascript">
<!--
var wgSampleName = "<?= $doc_name ?>";
//-->
</script>
<br class="clearall" />
