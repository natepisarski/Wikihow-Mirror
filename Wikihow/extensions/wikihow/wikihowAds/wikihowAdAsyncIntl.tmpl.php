<div class="clearall"></div>
<div class='wh_ad_inner adunit<?=$adId?>'>
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<script type="text/javascript">
		<!--

		document.write('<i' + 'ns class="adsbygoogle" style="display:inline-block;width:<?= $params['width'] ?>px;height:<?= $params['height'] ?>px" data-ad-client="ca-pub-9543332082073187" data-ad-slot="<?= $params['slot'] ?>" data-font-size="large"> </i' + 'ns>');

		(adsbygoogle = window.adsbygoogle || []).push({
			params: {google_max_num_ads: <?= $params['max_ads'] ?>,
				google_override_format: true,
				google_ad_region: "test",
				google_ad_channel: "<?= $channels ?>" + gchans + xchannels}
		});
		if (<?= ($adId === "2a"?"true":"false") ?>) {
			document.write('<i' + 'ns class="adsbygoogle" style="display:inline-block;width:<?= $params['width'] ?>px;height:<?= $params['height'] ?>px;margin-left:35px;" data-ad-client="ca-pub-9543332082073187" data-ad-slot="<?= $params['slot'] ?>" data-font-size="large"> </i' + 'ns>');

			(adsbygoogle = window.adsbygoogle || []).push({
				params: {google_max_num_ads: <?= $params['max_ads'] ?>,
					google_override_format: true,
					google_ad_region: "test",
					google_ad_channel: "<?= $channels ?>" + gchans + xchannels}
			});
		}
		//-->
	</script>
</div>
