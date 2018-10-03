(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.HighSchoolHacks = {
		endpoint: '/Special:HighSchoolHacks',

		init: function() {
			this.setHandlers();
		},

		setHandlers: function() {
			$('.hsh_topic').click(function() {
				WH.HighSchoolHacks.topicClick(this);
			});

			if (!WH.isMobileDomain) {
				$('.hsh_topic').hover(function() {
					$(this).addClass('over');
				},
				function() {
					$(this).removeClass('over');
				});
			}
		},

		topicClick: function(obj) {
			var topic_unselected = $(obj).hasClass('selected');

			this.restoreDefaultView();

			if (!topic_unselected) {
				var topic_id = $(obj).attr('id');
				this.getArticlesByTopic(topic_id);
			}
		},

		getArticlesByTopic: function(topic) {

			//TODO: faster to just load all the HTML when the page loads?

			$.getJSON(
				this.endpoint+'?action=get_articles&topic='+topic,
				$.proxy(function(articles) {
					if (articles.length) this.showArticleList(topic, articles);
				},this)
			);
		},

		showArticleList: function(topic, articles) {
			var data = {
				topic: topic,
				articles: articles
			};

			var html = this.escapeHtml(Mustache.render(unescape($('#high_school_hacks_article_list').html()), data));

			var padding_offset = 20;
			var offset = $('#'+topic).outerHeight() + $('#'+topic).position().top - padding_offset;

			$('#'+topic).after(html).addClass('selected');
			$('.high_school_hacks_article_list').css('top', offset+'px');
			$('.high_school_hacks_article_list').slideDown();

			this.fadeOtherIcons();
		},

		fadeOtherIcons: function() {
			$('.hsh_topic').each(function() {
				if (!$(this).hasClass('selected')) {
					$(this).animate({'opacity': .3});
				}
			});
		},

		restoreDefaultView: function() {
			$('.hsh_topic').removeClass('selected');
			$('.high_school_hacks_article_list').remove();

			$('.hsh_topic').each(function() {
				$(this).css('opacity', 1);
			});
		},

		escapeHtml: function (htmlString) {
			return $('<textarea/>').html(htmlString).text();
		}
	}

	WH.HighSchoolHacks.init();

})(jQuery);
