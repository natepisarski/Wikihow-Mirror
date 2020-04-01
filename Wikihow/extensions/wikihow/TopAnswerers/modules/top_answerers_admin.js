(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TopAnswerers = {

		tool_url: '/Special:TopAnswerersAdmin',

		init: function() {
			//Add new user
			$('#ta_add_button').click($.proxy(function() {
				var username = $('#ta_add_input').val();
				this.addTA(username, 0);
				return false;
			},this));

			//blacklist user by button
			$('#ta_block_button').click($.proxy(function() {
				var username = $('#ta_block_input').val();
				this.addTA(username, 1);
				return false;
			},this));

			$('.ta_result').on('click', '.block_link', $.proxy(function(e) {
				var row = $(e.target).parent();
				this.setUserBlock(row,true);
				return false;
			},this));

			$('.ta_result').on('click', '.unblock_link', $.proxy(function(e) {
				var row = $(e.target).parent();
				this.setUserBlock(row,false);
				return false;
			},this));
		},

		/**
		 * addTA()
		 *
		 * @param username = username to add/block
		 * @param block = boolean to block user when added
		 */
		addTA: function(username, block) {
			var html = '';

			$.getJSON(
				this.tool_url+'?action=add_ta&user='+username+'&block='+block,
				$.proxy(function(result) {
					if (result.error) {
						html = this.getResponse(result.error, false);
						$('#ta_add_button').after(html);
						$('.ta_response').delay(2500).fadeOut();
					}
					else {
						if (block) {
							html = this.getNewBlockRow(result);
							$('#ta_blocked_results').prepend(html);
						}
						else {
							html = this.getNewTARow(result);
							$('#ta_results').prepend(html);
						}
						$('.newTA').slideDown().removeClass('newTA');
					}
				},this)
			);
		},

		/**
		 * setUserBlock()
		 *
		 * @param row = result row to block/unblock
		 * @param block = boolean of whether we're blocking or not
		 */
		setUserBlock: function(row, block) {
			var id = $(row).data('id');
			var html = '';

			if ($.isNumeric(id)) {
				var block_val = block ? '1' : '0';

				$.getJSON(
					this.tool_url+'?action=set_block_user&id='+id+'&block='+block_val,
					$.proxy(function(result) {

						if (result.error) {
							html = this.getResponse(result.error, false);
						}
						else {
							html = this.getResponse(result.response, true);
						}

						$(row).slideUp(function() {
							$(this).html(html).slideDown();
						});

					},this)
				);
			}
		},

		getNewTARow: function(data) {
			var vars = {
				ta_block_link: 					mw.message('ta_block_link').text(),
				ta_added_text: 					mw.message('ta_added_text').text(),
				ta_last_answer_text: 		mw.message('ta_last_answer_text').text(),
				ta_type_text: 					mw.message('ta_type_text').text(),
				ta_answers_live_label: 	mw.message('ta_answers_live_label').text(),
				ta_answers_calc_label: 	mw.message('ta_answers_calc_label').text(),
				ta_sim_label: 					mw.message('ta_sim_label').text(),
				ta_rating_label: 				mw.message('ta_rating_label').text(),
				ta_subcats_label: 			mw.message('ta_subcats_label').text(),
				class_new: 							'newTA'
			};

			data = $.extend(data, vars);

			return this.escapeHtml(Mustache.render(unescape($('#top_answerers_result').html()), data));
		},

		getNewBlockRow: function(data) {
			var vars = {
				ta_unblock_link: 	mw.message('ta_unblock_link').text(),
				class_new: 				'newTA'
			};

			data = $.extend(data, vars);

			return this.escapeHtml(Mustache.render(unescape($('#top_answerers_block_result').html()), data));
		},

		getResponse: function(response, is_good) {
			var vars ={
				response: response,
				response_class: is_good ? 'good' : 'bad'
			};

			return this.escapeHtml(Mustache.render(unescape($('#top_answerers_response').html()), vars));
		},

		escapeHtml: function (htmlString) {
			return $('<textarea/>').html(htmlString).text();
		}

	}

	$(document).ready(function() {
		WH.TopAnswerers.init();
	});

})(jQuery, mw);
