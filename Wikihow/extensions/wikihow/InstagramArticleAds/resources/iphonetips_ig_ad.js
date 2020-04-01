(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.InstagramArticleAds = {
		version_count: 2,

		init: function() {
			this.loadiPhoneAd();
		},

		loadiPhoneAd: function() {
			var version = this.getVersion();

			$.getJSON(
				'api.php?action=instagram_article_ads&type=iphone&version='+version+'&format=json',
				$.proxy(function(data) {
					if (typeof data.instagram_article_ads.html !== 'undefined') {
						this.addAd(data.instagram_article_ads.html, version);
					}
				}, this)
			);
		},

		getVersion: function() {
			//forced?
			var v = this.getForcedVersion();
			if (v) return v;

			//randomized
			return Math.floor(Math.random() * this.version_count) + 1;
		},

		getForcedVersion: function() {
			var url = new URL(window.location);
			var v = parseInt(url.searchParams.get("iphonetips_ig_ad_version"));
			return v > 0 && v <= this.version_count ? v : false;
		},

		addAd: function(html, version) {
			$('.steps:last').after(html);

			//track those clicks
			$(document).on('click', '#instagram_article_block', function() {
				WH.maEvent('iphonetips_ig_ad_click', { 'version': version });
			});
		}
	}

	WH.InstagramArticleAds.init();
})(jQuery);