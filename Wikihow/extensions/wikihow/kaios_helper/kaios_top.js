(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KaiosTop = {
		queryString: 'kaios=1',
		init: function() {
			document.addEventListener('click', function (e) {
				var elem = e.target;
				while (elem.nodeName !== 'A' && elem.parentElement) {
					elem = elem.parentElement;
				}
				if (elem.nodeName === 'A') {
					var href = elem.getAttribute('href');
					if(href && !(/^#/).test(href)) {
						var splitUrl = href.split('#');
						href = splitUrl[0];
						var hash = splitUrl[1] ? "#" + splitUrl[1] : "";
						href += (/\?/.test(href) ? '&' : '?') + WH.KaiosTop.queryString;
						href += hash;
						elem.setAttribute('href', href);
					}
				}

			}, true);
		}
	};

	WH.KaiosTop.init();
}());