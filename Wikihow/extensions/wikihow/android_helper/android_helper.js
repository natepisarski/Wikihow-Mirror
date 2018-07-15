(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AndroidHelper = {

		article_img_url: $('meta[name="wh_an:image"]').attr('content'),

		getArticleImageUrl: function () {
			return WH.AndroidHelper.article_img_url;
		}
	};

	if (typeof(window.android) !== 'undefined' && typeof(window.android.onGetNamespaceId) !== 'undefined') {
		window.android.onGetNamespaceId(mw.config.get('wgNamespaceNumber'));
	}

}());



