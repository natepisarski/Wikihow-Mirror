window.dfprequested = false;
function initDFP() {
	if (window.dfprequested == true) {
		return;
	}
	if (WH.shared.isDesktopSize || dfpSmallTest) {
		var gads = document.createElement('script');
		gads.async = true;
		gads.type = 'text/javascript';
		gads.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
		var node = document.getElementsByTagName('script')[0];
		node.parentNode.insertBefore(gads, node);
		window.dfprequested = true;

		// Load GPT asynchronously
		window.googletag = window.googletag || {};
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

			var hasCookie = document.cookie.indexOf('ccpa_out=');
			if (hasCookie >= 0) {
				googletag.pubads().setPrivacySettings({'restrictDataProcessing': true});
			}
		});
	}
}

var format = 'sma';
var viewportWidth = (window.innerWidth || document.documentElement.clientWidth);
if (WH.isMobile == 0) {
	format = 'dsk';
} else if (viewportWidth >= WH.largeScreenMinWidth) {
	format = 'lrg';
} else if (viewportWidth >= WH.mediumScreenMinWidth) {
	format = 'med';
}
function setDFPTargeting(slot, data) {
	var slotData = data[slot.getAdUnitPath()];
	for (var key in slotData) {
	  slot.setTargeting(key, slotData[key]);
	}

	// always set this on every ad
	slot.setTargeting('bucket', bucketId);
	slot.setTargeting('language', WH.pageLang);
	slot.setTargeting('format', format);
	slot.setTargeting('site', window.location.hostname);
	slot.setTargeting('coppa', isCoppa);
	if (dfpCategory != '') {
		slot.setTargeting('category', dfpCategory);
	}
}
if (window.loadGPT == 1) {
	initDFP();
}
