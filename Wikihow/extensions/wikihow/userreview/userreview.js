(function($, mw) {
	"use strict";

	/**
	 * Check if an avatar's URL is broken, and swap it for the default as needed.
	 *
	 * @param {HTMLImageElement} image Image to auto-replace src for
	 */
	function autoReplaceBrokenAvatar( image ) {
		var test = new Image();
		test.onerror = function () {
			test.onerror = null;
			image.src = '/skins/WikiHow/images/80x80_user.png';
		};
		test.src = image.src;
	}

	window.WH = window.WH || {};
	window.WH.UserReview = {


		init: function () {
			// Auto-repair broken avatar images
			$( '.ur_avatar' ).each( function () {
				autoReplaceBrokenAvatar( this );
			} );

			//show a few more reviews
			$(".ur_more").on("click", function (e) {
				e.preventDefault();
				$(this).hide();
				var container = $(this).parent().parent();
				//expand all reviews
				$(container).find(".ur_review").show();
				//expand all review texts
				$(container).find(".ur_review_show").hide();
				$(container).find(".ur_review_more").show().css("display", "inline");
				$(container).find(".ur_ellipsis").hide();
				//are there any left to show?
				$(container).find(".ur_hide").show().css("display", "block");
			});
			//show the rest of the text of this review
			$(".ur_review_show").on("click", function (e) {
				e.preventDefault();
				$(this).hide();
				var container = $(this).parent().parent();
				$(container).find(".ur_review_more", $(this).parent()).show();
				$(container).find(".ur_ellipsis", $(this).parent()).hide();
			});
			//hide all but the first review
			$(".ur_hide").on("click", function (e) {
				e.preventDefault();
				var container = $(this).parent().parent();
				$(container).find(".ur_review:gt(0)").hide();
				$(this).hide();
				$(container).find(".ur_more").show().css("display", "block");
			});
			$(".ur_share").on("click", function(e) {
				e.preventDefault();
				mw.loader.using('ext.wikihow.UserReviewForm', function () {
					var urf = new window.WH.UserReviewForm();
					urf.loadUserReviewForm();
				});
				$(this).hide();
			});
		}
	}

	WH.UserReview.init();
}($, mw));
