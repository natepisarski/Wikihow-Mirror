function insertAdsense(style, slot, target, client) {
	var i = window.document.createElement('ins');
	i.setAttribute('data-ad-client', client );
	i.setAttribute('data-ad-slot', slot);
	i.setAttribute('class', 'adsbygoogle');
	i.style.cssText = style;
	window.document.getElementById(target).firstElementChild.appendChild(i);
}
(function() {
	var abg = document.createElement('script');
	abg.async = true;
	abg.type = 'text/javascript';
	var useSSL = 'https:' == document.location.protocol;
	abg.src = (useSSL ? 'https:' : 'http:') +
	'//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
var node = document.getElementsByTagName('script')[0];
node.parentNode.insertBefore(abg, node);
})();
