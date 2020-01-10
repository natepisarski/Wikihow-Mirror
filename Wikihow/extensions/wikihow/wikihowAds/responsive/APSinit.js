if (WH.shared.isDesktopSize) {
	//Load the APS JavaScript Library
	!function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:
		function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function()
		{},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A
	.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}
	("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");
	//Initialize the Library
	var privacyValue = null;

	var vrCACookie = document.cookie.indexOf('vr=US-CA');
	if (vrCACookie >= 0) {
		privacyValue = "1YN";
	}
	var ccpaCookie = document.cookie.indexOf('ccpa_out=');
	if (ccpaCookie >= 0) {
		privacyValue = "1YY";
	}
	if (privacyValue) {
		apstag.init({
			pubID: '3271',
			adServer: 'googletag',
			aps_privacy: privacyValue
		});
	} else {
		apstag.init({
			pubID: '3271',
			adServer: 'googletag'
		});
	}
}
