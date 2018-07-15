( function () {
	'use strict';
	window.WH = window.WH || {};
	window.WH.sizer = {

		breakPortrait: 500,
		breakLandscape: 421,
		countRatio: false,

		detectSize: function (img) {
			if (img.getAttribute('src')) return;

			var large = img.getAttribute('data-srclarge') || img.getAttribute('data-src'),
				small = img.getAttribute('data-src') || large,
				attr = window.defer ? 'data-src' : 'src',
				src = this.isPhone() ? small : large;

			img.setAttribute(attr, src);
		},

		// look for a gif src as a default image and use it if possible
		detectGif: function (img) {
			var gif = img.getAttribute('data-srcgiffirst');
			var isiPad = navigator.userAgent.match(/iPad/i) !== null;

			if (gif && !isiPad) {
				img.setAttribute('data-src', gif);
				img.setAttribute('src', gif);
				img.className += ' whvgif';
			} else if (gif) {
				img.parentElement.className += ' nogif';
			}
		},

		getWidth: function () {
			var width = document.getElementsByTagName('body')[0].clientWidth;
			return this.countRatio ? width * this.getPixelRatio() : width;
		},

		getHeight: function () {
			var height = document.documentElement.clientHeight;
			return this.countRatio ? height * this.getPixelRatio() : height;
		},

		isPhone: function () {
			return this.isLandscape() ? (this.getHeight() < this.breakLandscape) : (this.getWidth() < this.breakPortrait);
		},

		isTablet: function () {
			return !this.isPhone();
		},

		getPixelRatio: function () {
			return (window.devicePixelRatio) ? devicePixelRatio : 1;
		},

		isRetina: function () {
			return this.getPixelRatio() > 1;
		},

		isLandscape: function () {
			return this.getWidth() > this.getHeight();
		}
	};
}() );
