(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KaiosHelper = {
		queryString: 'kaios=1',
		init: function() {
			document.addEventListener('click', function (e) {
				var elem = e.target;
				if (elem.nodeName !== 'A') {
					elem = elem.parentElement;
				}
				if (elem.nodeName === 'A') {
					var href = elem.getAttribute('href');
					if(href && !(/^#/).test(href)) {
						href += (/\?/.test(href) ? '&' : '?') + WH.KaiosHelper.queryString;
						elem.setAttribute('href', href);
					}
				}

			}, false);
		}
	};

	WH.KaiosHelper.init();
}());



