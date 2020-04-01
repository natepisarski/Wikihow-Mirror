(function ($, mw) {
	'use strict';

	window.WH.ratings.parentElem = $('#article_rating');
	window.WH.ratings.buttonLocked = false;

	window.WH.ratings.bindInputFields = function (parentElem) {
		parentElem.delegate(
			$('#ar_form_radios>.ar_radios'),
			'change',
			$.proxy(this.detectButtonActive, this)
		);

		parentElem.delegate(
			$('#ar_details'),
			'input propertychange paste',
			$.proxy(this.detectButtonActive, this)
		);
	};

	window.WH.ratings.detectButtonActive = function () {
		var reasonStep = $('#ar_inner_details').hasClass('ar_hidden');
		if ((reasonStep && $('#ar_form_radios>.ar_radios .ar_radio:checked').val()) || (!reasonStep && $.trim($('#ar_details').val()))) {
			this.setButtonActive();
		} else {
			this.setButtonInactive();
		}
	};

	window.WH.ratings.setButtonActive = function () {
		if (this.buttonLocked) {
			return;
		}

		$('.ar_button,.ar_spinner')
			.removeClass('ar_submit_inactive ar_submit_submitting')
			.addClass('ar_submit_active');
	};

	window.WH.ratings.setButtonInactive = function () {
		if (this.buttonLocked) {
			return;
		}

		$('.ar_button,.ar_spinner')
			.removeClass('ar_submit_active ar_submit_submitting')
			.addClass('ar_submit_inactive');
	};

	window.WH.ratings.setButtonSubmitting = function () {
		if (this.buttonLocked) {
			return;
		}

		$('.ar_button,.ar_spinner')
			.removeClass('ar_submit_active ar_submit_inactive')
			.addClass('ar_submit_submitting');
	};

	window.WH.ratings.setButtonLock = function () {
		this.buttonLocked = true;
	};

	window.WH.ratings.unsetButtonLock = function () {
		this.buttonLocked = false;
	};

	window.WH.ratings.ratingReasonSubmit = function(itemId, rating, reason, ratingId) {
		if (!reason) {
			return false;
		}

		var postData = {
			'item_id': itemId,
			'type': 'article_mh_style',
			'ratingId': ratingId,
			'rating': rating,
			'detail': reason // Note: counter-intuitive naming
		};

		if (mw.config.get('wgArticleId') > 0) {
			postData.page_id = wgArticleId;
		}

		var requestUrl = '/Special:RatingReason';

		this.setButtonSubmitting();
		this.setButtonLock();

		$.ajax({
			type: 'POST',
			url: requestUrl,
			data: postData
		}).done($.proxy(WH.ratings.reasonSubmitSuccess, this));
	};

	window.WH.ratings.reasonSubmitSuccess = function (data) {
		$('#ar_inner_radios').hide();
		$('#ar_inner_details').removeClass('ar_hidden');
		$('#ar_item_id').val(data.item_id);
		this.unsetButtonLock();
		this.detectButtonActive();
	};

	window.WH.ratings.ratingDetailsSubmit = function (itemId, rating, details, email, reason, isPublic, firstname, lastname) {
		details = $.trim(details);

		if (!details) {
			return false;
		}

		if(isPublic == "yes") {
			if(firstname == "" || lastname == "") {
				$("#ar_public_info .ar_border_thin").each(function(){
					if($(this).val() == "") {
						$(this).addClass("error");
					} else {
						$(this).removeClass("error");
					}
				});
				$("#ar_public_error").show();
				return false;
			}
		}

		var postData = {
			'item_id': itemId,
			'type': 'article_mh_style',
			'rating': rating,
			'reason': details // Note: counter-intuitive naming
		};

		email = $.trim(email);

		if (email) {
			postData.email = email;
		}

		if (reason) {
			postData.detail = reason; // Note: counter-intuitive naming
		}

		if (isPublic == "yes") {
			postData.isPublic = true;
			postData.firstname = firstname;
			postData.lastname = lastname;
		}

		if (typeof(wgArticleId) != 'undefined') {
			postData.page_id = wgArticleId;
		}

		var requestUrl = '/Special:RatingReason';

		this.setButtonSubmitting();
		this.setButtonLock();

		$.ajax({
			type: 'POST',
			url: requestUrl,
			data: postData
		}).done(function (data) {
			data = '<div class="article_rating_result">' + data + '</div>';

			if (WH.DiscussTab && $('#article_rating_modal')) {
				WH.DiscussTab.addFinalResponse(data);
			}
			else {
				$('#article_rating').html(data);
			}
		});
	};
})(jQuery, mw);

