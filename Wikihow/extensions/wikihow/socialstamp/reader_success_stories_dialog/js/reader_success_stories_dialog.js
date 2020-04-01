/*global mediaWiki, jQuery, WH*/
( function( mw, $ ) {
	window.WH = WH || {};

	WH.ReaderSuccessStoriesDialog = function() {
	};

	WH.ReaderSuccessStoriesDialog.prototype = {
		getScrollingElement: function() {
			return WH.isMobileDomain ? $( '#mw-mf-viewport' ) : $( 'body' );
		},
		copyElements: function() {
			if (!$('.bss_star_container').is(":empty")) return;

			// Copy the ratings data
			$('#sp_star_box .sp_helpful_box').clone().appendTo('.bss_star_container');

			// Copy the reviews
			if($('#reviews .ur_review').length) {
				var reviews = $('#reviews').clone().removeAttr('id');
				reviews.appendTo('.bss_success_stories');
				reviews.find('.ur_nav_container').remove();
				reviews.find(".ur_review_more").show().css("display", "inline");
				reviews.find(".ur_ellipsis").hide();
				reviews.find(".ur_review").show();
				reviews.find(".ur_review_show").hide();
			} else {
				$('.bss_success_stories_title').hide();
			}

			// Hide the review button if the form has already been launched
			if (WH.UserReview.reviewFormShown) {
				$('.bss_share_container').hide();
			}
		},
		launch: function() {
			var dialog = this;

			dialog.copyElements();

			$( '#rss_diag' ).magnificPopup( {
				fixedContentPos: false,
				fixedBgPos: true,
				showCloseBtn: false,
				overflowY: 'auto',
				preloader: false,
				type: 'inline',
				closeBtnInside: true,
				callbacks: {
					beforeClose: function() {
						dialog.releaseTheScrollbar();
					}
				}
			} );
			$(document).on('click', '.bss_close_container' , function() {
				dialog.hideReaderSuccessStoriesDialog();
			} );

			var scrollingElement = dialog.getScrollingElement();
			scrollingElement.addClass( 'modal-open' );
			$('#rss_diag' ).trigger( 'click' );
		},

		hideReaderSuccessStoriesDialog: function() {
			var dialog = this;
			dialog.releaseTheScrollbar();
			$.magnificPopup.close();
		},
		releaseTheScrollbar: function() {
			var dialog = this;
			var scrollingElement = dialog.getScrollingElement();
			scrollingElement.removeClass( 'modal-open' );
		}
	};
} ( mediaWiki , jQuery ) );
