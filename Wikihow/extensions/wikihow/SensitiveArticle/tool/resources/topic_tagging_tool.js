(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TopicTaggingTool = {

		tool_url: '/Special:TopicTagging',
		dynamic_elements: "#ttt_title, #ttt_remaining, #ttt_topic, #ttt_question, #ttt_description, #ttt_article_html, #ttt_buttons",
		active_page_id: 0,
		active_job_id: 0,
		is_mobile: 0,

		init: function() {
			if (WH.isMobileDomain) this.is_mobile = 1;
			this.getNextArticle();
			if (this.is_mobile) this.removeFooter();
		},

		addVoteHandlers: function() {
			$('#ttt_vote_yes').unbind('click').one('click', $.proxy(function() {
				this.vote(true);
			},this));

			$('#ttt_vote_no').unbind('click').one('click', $.proxy(function() {
				this.vote(false);
			},this));

			$('#ttt_vote_skip').unbind('click').one('click', $.proxy(function() {
				this.skip();
			},this));
		},

		getNextArticle: function() {
			this.processing(true);

			$.getJSON(this.tool_url + '?action=next', $.proxy(function(data) {
				if (data) {
					if (data.end_of_queue) {
						this.endOfQueue(data.end_of_queue);
					}
					else {
						this.active_page_id = data.page_id;
						this.active_job_id = data.job_id;
						this.displayResult(data);
					}
				}
			},this));
		},

		removeFooter: function() {
			$('#footer').hide();
		},

		displayResult: function(data) {
			this.processing(false);
			this.addVoteHandlers();

			$('#ttt_title').stop().hide().html(data.page_title);
			$('#ttt_question').html(data.question);
			$('#ttt_description').html(data.description);
			$('#ttt_article_html').hide().html(data.article_html);

			if (this.is_mobile) {
				var title = $('#ttt_title').clone();
				$('#intro').prepend($(title).css('opacity',1).show());
			}
			else {
				$('#ttt_remaining p').html(data.remaining);
				$('#ttt_title').fadeIn();
				$('#bodycontents').after($('#ttt_article_html'));
			}

			$('#ttt_article_html').show();

			$('#ti_box ul').html(mw.message('ti_TopicTagging_bullets', data.topic_name).text());
		},

		vote: function(vote) {
			this.processing(true);

			var vote = vote ? 1 : 0;

			$.post(
				this.tool_url,
				{
					action: 'vote',
					vote: vote,
					page_id: this.active_page_id,
					job_id: this.active_job_id
				},
				$.proxy(function(data) {
					this.updateStats();
					this.getNextArticle();
				},this),
				'json'
			);
		},

		updateStats : function() {
			var statboxes = '#iia_stats_today_topicstagged,#iia_stats_week_topicstagged,#iia_stats_all_topicstagged,#iia_stats_group';
			$(statboxes).each(function(index, elem) {
					$(this).fadeOut(function () {
						var cur = parseInt($(this).html());
						$(this).html(cur + 1);
						$(this).fadeIn();
					});
				}
			);
		},

		skip: function() {
			this.processing(true);

			$.post(
				this.tool_url,
				{
					action: 'skip',
					page_id: this.active_page_id,
					job_id: this.active_job_id
				},
				$.proxy(function(data) {
					this.getNextArticle();
				},this),
				'json'
			);
		},

		endOfQueue: function(eoq_msg) {
			$('#ttt_spinner').hide();
			$('#ttt_top').after(eoq_msg);
			$('#ti_icon').hide();
			$('#ti_outer_box').hide();
		},

		processing: function(is_processing) {
			if (is_processing) {
				$('#ttt_spinner').show();
				$(this.dynamic_elements).fadeOut();
			}
			else {
				$('#ttt_spinner').hide();
				$(this.dynamic_elements).fadeIn();
			}
		}
	};

	$(document).ready(function() {
		WH.TopicTaggingTool.init();
	});
})(jQuery,mw);