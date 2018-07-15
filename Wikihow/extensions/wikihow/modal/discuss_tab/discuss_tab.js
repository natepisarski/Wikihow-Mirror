(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.DiscussTab = {
		aid: 0,

		popModal: function() {
			if (this.aid > 0) {
				var already_rated = +WH.ratings.gRated;
				var modal_url = '/Special:BuildWikihowModal?modal=discusstab' +
												'&aid=' + this.aid +
												'&already_rated=' + already_rated;

				$.get(modal_url, $.proxy(function(data) {
					$.modal(data, {
						zIndex: 100000007,
						maxWidth: 400,
						minWidth: 400,
						minHeight: 400,
						overlayCss: { "background-color": "#000" }
					});
					this.addHandlers();
					if (already_rated) this.showCloseButton();
				},this));
			}
		},

		addHandlers: function() {
			$('.aritem_dt').on('click', function() {
				var source = 'discuss_tab';
				var rating = $(this).data('rating');
				WH.ratings.rateItem(rating, WH.DiscussTab.aid, 'article_mh_style', source);
				return false;
			});

			//x
			$('#wh_modal').on('click', '.wh_modal_close', function() {
				$.modal.close();
				return false;
			});

			//submit button detection (for after clicking "no")
			$('#article_rating_modal').delegate(
				$('.ar_textarea'),
				'input propertychange paste',
				$.proxy(this.detectButtonActive, this)
			);
		},

		detectButtonActive: function() {
			$.trim($('.ar_textarea').val()) ? WH.ratings.setButtonActive() : WH.ratings.setButtonInactive();
		},

		showCloseButton: function() {
			$('#modal_close_button').css('display','block');
		},

		addFinalResponse: function(response) {
			this.showCloseButton();
			$('#article_rating_modal').html(response);
		}
	}
}($,mw));