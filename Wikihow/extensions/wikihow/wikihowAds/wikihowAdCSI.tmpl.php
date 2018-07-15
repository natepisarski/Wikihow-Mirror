<div class='wh_ad_inner adunit<?=$adId?>'>
<script type="text/javascript">
<!--
<!-- MediaWiki:Adunit<?=$adId?> -->
	
google_ad_client = "ca-pub-9543332082073187";
/* iFrame Unit - <?=$adId?> */
google_ad_slot = <?= $params['slot'] ?>;
google_ad_width = <?= $params['width'] ?>;
google_ad_height = <?= $params['height'] ?>;
google_ad_output = 'html';
google_override_format = true;
google_ad_channel = "<?= $channels ?>" + gchans + xchannels;
google_max_num_ads = <?= $params['max_ads'] ?>;

if(<?= ($adId === "intro"?"true":"false") ?> && fromsearch) {
	document.write('<sc' + 'ript t' + 'ype="text/javascript" s' + 'rc="http://pagead2.googlesyndication.com/pagead/show_ads.js"></' + 'script>');
}
else if( <?= ($adId === "intro"?"true":"false") ?> && !fromsearch) {
	
}
else {
	document.write('<scri' + 'pt ty' + 'pe="text/javascript" sr' + 'c="http://pagead2.googlesyndication.com/pagead/show_ads.js"></' + 'script>');
}
//-->
</script>
</div>
