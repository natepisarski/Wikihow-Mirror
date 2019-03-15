<script type='text/javascript'>

$.getScript('https://www.google.com/adsense/search/ads.js', function()
{
	// https://developers.google.com/custom-search-ads/s/docs/code-generator
	(function(g,o){g[o]=g[o]||function(){(g[o]['q']=g[o]['q']||[]).push(
		arguments)},g[o]['t']=1*new Date})(window,'_googCsa');

	var pageOptions = {
		pubId: 'pub-9543332082073187',
		query: <?= $query ?>,
		hl: <?= $lang ?>,
		adPage: <?= $page ?>,
		channel: <?= $channel ?>,
		//adtest: <?= $test ?>,
		location: true,
		sellerRatings: true,
		siteLinks: false
	};

	var commonBlockCnf = {
		fontSizeTitle: 16,
		noTitleUnderline: true,
		colorTitleLink: WH.isMobile ? '#93b874' : '#363',
		colorDomainLink: '#363',
		colorText: '#545454',
		colorBorder : '#d2d8cd',
	};

	var adBlockTop = {
		container: 'search_adblock_top',
		number: 3
	};

	var adBlockBottom = {
		container: 'search_adblock_bottom',
		number: 4
	};

	$.extend(adBlockTop, commonBlockCnf);
	$.extend(adBlockBottom, commonBlockCnf);

	if (WH.gdpr && WH.gdpr.isEULocation()) {
		pageOptions['personalizedAds'] = false;
	}
	if (WH.isMobile) {
		var adBlockMiddle = {
			container: 'search_adblock_middle',
			number: 1
		};
		$.extend(adBlockMiddle, commonBlockCnf);
		_googCsa('ads', pageOptions, adBlockTop, adBlockMiddle, adBlockBottom);
	} else {
		_googCsa('ads', pageOptions, adBlockTop, adBlockBottom);
	}

});

</script>
