(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.HighSchoolHacksArticleIcon = {
		endpoint: '/Special:HighSchoolHacks',
		topic: '',

		init: function() {
			this.setTopic();
			this.addIcon();
		},

		setTopic: function() {
			var high_school_hack = this.getQueryVariable('hsh');
			high_school_hack = $("<div/>").html(high_school_hack).text(); //sanitize
			this.topic = high_school_hack;
		},

		addIcon: function() {

			//TODO: faster to just load all the HTML when the page loads?

			$.getJSON(
				this.endpoint+'?action=article_icon&topic='+this.topic,
				$.proxy(function(html) {
					$('body').append(html);
				},this)
			);
		},

		getQueryVariable: function(variable) {
			var query = window.location.search.substring(1);
			var vars = query.split("&");
			for (var i=0; i<vars.length; i++) {
				var pair = vars[i].split("=");
				if (pair[0] == variable) { return pair[1]; }
			}
			return(false);
		}
	}

	WH.HighSchoolHacksArticleIcon.init();

})(jQuery);
