window.PWT = {};
var googletag = googletag || {};
var openWrapProfileVersionId = '';
var openWrapProfileId = 2154;
googletag.cmd = googletag.cmd || [];
(function() {
	var purl = window.location.href;
	var url = '//ads.pubmatic.com/AdServer/js/pwt/159181/2154';
	var isCCPAOut = document.cookie.indexOf('ccpa_out=');
	if (isCCPAOut >= 0) {
		url = '//ads.pubmatic.com/AdServer/js/pwt/159181/2159';
	}
	if (typeof(WH.gdpr) != 'undefined' && WH.gdpr.isEULocation()) {
		openWrapProfileId = 2159;
		url = '//ads.pubmatic.com/AdServer/js/pwt/159181/2159';
	}
	if (bucketId == 22) {
		openWrapProfileId = 2442;
		url = '//ads.pubmatic.com/AdServer/js/pwt/159181/2442';
	}
	if (purl.indexOf('pwtv=') > 0) {
		var regexp = /pwtv=(.*?)(&|$)/g;
		var matches = regexp.exec(purl);
		if (matches.length >= 2 && matches[1].length > 0) {
			openWrapProfileVersionId = '/' + matches[1];
		}
	}
	var wtads = document.createElement('script');
	wtads.async = true;
	wtads.type = 'text/javascript';
	wtads.src = url + openWrapProfileVersionId + '/pwt.js';
	var node = document.getElementsByTagName('script')[0];
	node.parentNode.insertBefore(wtads, node);
})();
