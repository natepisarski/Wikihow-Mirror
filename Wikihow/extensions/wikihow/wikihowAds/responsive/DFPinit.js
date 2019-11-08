if (WH.shared.isDesktopSize) {
	var gads = document.createElement('script');
	gads.async = true;
	gads.type = 'text/javascript';
	var useSSL = 'https:' == document.location.protocol;
	gads.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
	var node = document.getElementsByTagName('script')[0];
	node.parentNode.insertBefore(gads, node);
	var format = 'sma';
	var viewportWidth = (window.innerWidth || document.documentElement.clientWidth);
	if (WH.isMobile == 0) {
		format = 'dsk';
	} else if (viewportWidth >= WH.largeScreenMinWidth) {
		format = 'lrg';
	} else if (viewportWidth >= WH.mediumScreenMinWidth) {
		format = 'med';
	}

	// Load GPT asynchronously
	function setDFPTargeting(slot, data) {
		var slotData = data[slot.getAdUnitPath()];
		for (var key in slotData) {
		  slot.setTargeting(key, slotData[key]);
		}

		// always set this on every ad
		slot.setTargeting('bucket', bucketId);
		slot.setTargeting('language', WH.pageLang);
		slot.setTargeting('format', format);
	}
	var googletag = googletag || {};
	googletag.cmd = googletag.cmd || [];
	gptRequested = true;
	googletag.cmd.push(function() {
		defineGPTSlots();
		googletag.pubads().addEventListener('slotRenderEnded', function(event) {
			if (WH.ads) {
				WH.ads.slotRendered(event.slot, event.size, event);
			}
		});
		googletag.pubads().addEventListener('impressionViewable', function(event) {
			if (WH.ads) {
				WH.ads.impressionViewable(event.slot);
			}
		});
	});
}
