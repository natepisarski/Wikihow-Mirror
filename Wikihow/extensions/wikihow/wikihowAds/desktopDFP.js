// Load GPT asynchronously
function setDFPTargeting(slot, data) {
	var slotData = data[slot.getAdUnitPath()];
    for (var key in slotData) {
      slot.setTargeting(key, slotData[key]);
    }
}
var googletag = googletag || {};
googletag.cmd = googletag.cmd || [];
gptRequested = true;
googletag.cmd.push(function() {
	defineGPTSlots();
	googletag.pubads().addEventListener('slotRenderEnded', function(event) {
		if (WH.desktopAds) {
			WH.desktopAds.slotRendered(event.slot, event.size, event);
		}
	});
	googletag.pubads().addEventListener('impressionViewable', function(event) {
		if (WH.desktopAds) {
			WH.desktopAds.impressionViewable(event.slot);
		}
	});
});
