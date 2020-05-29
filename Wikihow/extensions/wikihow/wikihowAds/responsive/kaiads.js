document.addEventListener("DOMContentLoaded", function() {
	// If there is an intro ad container on the page, load it
	if(document.getElementById('intro_ad_1')) {
		getKaiAd({
			publisher: 'b9b87d46-5c86-4eb5-8465-84856ce26767',
			app: 'wikihow',
			slot: 'intro',
			h: 264,
			w: 240,
			container: document.getElementById('intro_ad_1'),
			test: window.location.hostname == "www.wikihow.com" ? 0 : 1,
			onerror: function(err) {console.error('Custom catch:', err);},
			onready: function(ad) {
				ad.call('display', {display: 'block'});
			}
		});
	}
});