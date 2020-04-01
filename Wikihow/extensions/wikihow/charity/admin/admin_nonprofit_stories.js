(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminStories = {
		url: '/Special:AdminNonProfitStories',
		story_order: [],

		init: function() {
			this.addHandlers();
			this.story_order = this.getStoryOrder();
		},

		addHandlers: function() {
			$('#current_stories').sortable({
				items: '.ch_review',
				beforeStop: $.proxy(function() {
					this.orderChanged();
				},this)
			});

			$('#submit').click($.proxy(function() {
				this.grabArticleStories();
			},this));

			$('#reset').click($.proxy(function() {
				this.displayCurrentStories();
			},this));

			$('.np_section')
			.on('mouseenter', '.ch_review', function() {
				WH.AdminStories.highlightStory($(this));
			})
			.on('mouseleave', '.ch_review', function() {
				WH.AdminStories.highlightStory($(this));
			})
			.on('click', '.remove_button', function() {
				WH.AdminStories.removeStory($(this).parent());
				return false;
			})
			.on('click', '.add_button', function() {
				WH.AdminStories.addStory($(this).parent());
				return false;
			})
			.on('click', '#save_story_order_change', $.proxy(function() {
				this.updateStoryOrder();
				return false;
			},this))
			.on('click', '#close_story_order_done', function() {
				$('#story_order_done').slideUp();
			});
		},

		getStoryOrder: function() {
			var order = [];
			var review_id;
			$('.ch_review').each(function() {
				review_id = $(this).data('review_id');
				if (typeof(review_id) != 'undefined') order.push(review_id);
			});
			return order;
		},

		grabArticleStories: function() {
			var page = $('#page_name').val().trim();
			if (page.length == 0) return false;

			$.post(
				this.url,
				{
					action: 'grab_stories',
					page: encodeURI(page)
				},
				function(data) {
					WH.AdminStories.displayArticleStories(data);
				},
				'json'
			);
		},

		highlightStory: function(story) {
			if ($(story).hasClass('selected')) {
				$(story).removeClass('selected');
			}
			else {
				$('.ch_review').removeClass('selected');
				$(story).addClass('selected');
			}
		},

		orderChanged: function() {
			if (!$('#story_order_change').hasClass('changed')) {
				$('#story_order_change').addClass('changed').slideDown();
			}
		},

		displayArticleStories: function(stories) {
			var html = '';
			if (stories.error) {
				html = stories.error;
			}
			else {
				html = stories.html;
			}

			$('#article_stories').html(html);

			$('#current_stories').slideUp(function() {
				$('#article_stories').slideDown();
			});
		},

		displayCurrentStories: function() {
			window.location.reload();
		},

		removeStory: function(story) {
			var confirm = window.confirm('This will immediately REMOVE this user story from the Non-Profit page.\n\nARE YOU SURE?');
			if (!confirm) return false;

			$.post(
				this.url,
				{
					action: 'remove_story',
					review_id: $(story).data('review_id')
				},
				function(data) {
					$(story).slideUp();
				},
				'json'
			);
		},

		addStory: function(story) {
			var confirm = window.confirm('This will immediately ADD this user story from the Non-Profit page.\n\nCool?');
			if (!confirm) return false;

			$.post(
				this.url,
				{
					action: 'add_story',
					review_id: $(story).data('review_id')
				},
				$.proxy(function(data) {
					this.displayCurrentStories();
				},this),
				'json'
			);
		},

		updateStoryOrder: function() {
			$.post(
				this.url,
				{
					action: 'update_story_order',
					new_order: this.getStoryOrder().toString()
				},
				$.proxy(function(data) {
					this.orderUpdatedMessage();
				},this),
				'json'
			);
		},

		orderUpdatedMessage: function() {
			$('#story_order_change').slideUp(function() {
				$('#story_order_done').slideDown();
				$('#story_order_change').removeClass('changed');
			});

		}
	}

	$(document).ready(function() {
		WH.AdminStories.init();
	});

})($);