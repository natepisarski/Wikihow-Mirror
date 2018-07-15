<? if (($dv_dl_file_pdf != '') && ($dv_dl_file_xls == '')) { ?>
<p class="dv_dl_pdf_2"><a href="<?=$dv_dl_file_pdf?>" id="gatSamplePdf3" target="_blank" rel="nofollow"><?=$dv_download?> <?=$dv_dl_text_pdf?></a></p>
<? } ?>
<? if ($dv_dl_file_doc != '') { ?>
<p class="dv_dl_doc_2"><a href="<?=$dv_dl_file_doc?>" id="gatSampleWord3" target="_blank" rel="nofollow"><?=$dv_download?> <?=$dv_dl_text_doc?></a></p>
<? } ?>
<? if ($dv_dl_file_xls != '') { ?>
<p class="dv_dl_xls_2"><a href="<?=$dv_dl_file_xls?>" id="gatSampleXls3" target="_blank" rel="nofollow"><?=$dv_download?> <?=$dv_dl_text_xls?></a></p>
<? } ?>
<? if ($dv_dl_file_txt != '') { ?>
<p class="dv_dl_txt_2"><a href="<?=$dv_dl_file_txt?>" id="gatSampleTxt3" target="_blank" rel="nofollow"><?=$dv_download?> <?=$dv_dl_text_txt?></a></p>
<? } ?>
<? if ($dv_dl_file_ext != '') { ?>
<p class="dv_dl_ext_2"><a href="<?=$dv_dl_ext_prefix.$dv_dl_file_ext?>" target="_blank" id="gatSampleExtLink3" rel="nofollow"><?=$dv_open_in?> <?=$dv_dl_text_ext?></a></p>
<? } ?>
