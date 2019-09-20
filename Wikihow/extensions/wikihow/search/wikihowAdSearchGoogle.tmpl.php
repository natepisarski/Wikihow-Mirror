<script async="async" src="https://www.google.com/adsense/search/ads.js"></script>

<script type="text/javascript" charset="utf-8">
	//https://developers.google.com/custom-search-ads/s/docs/implementation-guide
	(function(g,o){g[o]=g[o]||function(){(g[o]['q']=g[o]['q']||[]).push(
		arguments)},g[o]['t']=1*new Date})(window,'_googCsa');
</script>
<script type="text/javascript" charset="utf-8">
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

	var adBlockTop = {
		container: 'search_adblock_top',
		number: 3,
		fontSizeTitle: 16,
		noTitleUnderline: true,
		colorTitleLink: WH.isMobile ? '#93b874' : '#363',
		colorDomainLink: '#363',
		colorText: '#545454',
		colorBorder : '#d2d8cd',
	};

	var adBlockBottom = {
		container: 'search_adblock_bottom',
		number: 4,
		fontSizeTitle: 16,
		noTitleUnderline: true,
		colorTitleLink: WH.isMobile ? '#93b874' : '#363',
		colorDomainLink: '#363',
		colorText: '#545454',
		colorBorder : '#d2d8cd',
	};

	if (WH.gdpr && WH.gdpr.isEULocation()) {
		pageOptions['personalizedAds'] = false;
	}
	if (WH.isMobile) {
		var adBlockMiddle = {
			container: 'search_adblock_middle',
			number: 1,
			fontSizeTitle: 16,
			noTitleUnderline: true,
			colorTitleLink: WH.isMobile ? '#93b874' : '#363',
			colorDomainLink: '#363',
			colorText: '#545454',
			colorBorder : '#d2d8cd',
		};
		_googCsa('ads', pageOptions, adBlockTop, adBlockMiddle, adBlockBottom);
	} else {
		_googCsa('ads', pageOptions, adBlockTop, adBlockBottom);
	}

</script>
