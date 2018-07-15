(function () {
	'use strict';
	window.WH = WH || {};
	window.WH.MethodHelpfulness.BottomForm = function () {};
	window.WH.MethodHelpfulness.BottomForm.prototype = new window.WH.MethodHelpfulness();

	window.WH.MethodHelpfulness.BottomForm.prototype.parentElement = $('#article_rating');
	window.WH.MethodHelpfulness.BottomForm.prototype.buttonLocked = false;
	window.WH.MethodHelpfulness.BottomForm.prototype.methodSelectionDone = false;
	window.WH.MethodHelpfulness.BottomForm.prototype.eventId = undefined;

	window.WH.MethodHelpfulness.BottomForm.prototype.setButtonActive = function () {
		if (this.buttonLocked) {
			return;
		}
		$('.mhbf-button,.mhbf-spinner')
			.removeClass('mh-submit-inactive mh-submit-submitting')
			.addClass('mh-submit-active');
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.setButtonInactive = function () {
		if (this.buttonLocked) {
			return;
		}
		$('.mhbf-button,.mhbf-spinner')
			.removeClass('mh-submit-active mh-submit-submitting')
			.addClass('mh-submit-inactive');
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.setButtonSubmitting = function () {
		if (this.buttonLocked) {
			return;
		}
		$('.mhbf-button,.mhbf-spinner')
			.removeClass('mh-submit-inactive mh-submit-active')
			.addClass('mh-submit-submitting');
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.setButtonLock = function () {
		this.buttonLocked = true;
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.unsetButtonLock = function () {
		this.buttonLocked = false;
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.detectButtonActive = function (e) {
		if (
			(!this.methodSelectionDone && $('.mhbf-selector-checkbox').is(':checked'))
			|| (this.methodSelectionDone && $.trim($('#mhbf-details').val()))
		) {
			this.setButtonActive();
		} else {
			this.setButtonInactive();
		}

		return false;
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.initialize = function (elem) {
		WH.MethodHelpfulness.prototype.initialize.call(this, elem);

		elem.delegate(
			$('#mhbf-method-selector').contents().find(':checbox'),
			'change',
			$.proxy(this.detectButtonActive, this)
		);

		elem.delegate(
			$('#mhbf-details'),
			'input propertychange paste',
			$.proxy(this.detectButtonActive, this)
		);

		elem.delegate(
			$('#mhbf-form'),
			'submit',
			$.proxy(this.prepareSubmit, this)
		);
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.prepareSubmit = function (e) {
		var tgtId = $(e.target).prop('id');
		if (tgtId !== 'mhbf-submit' && tgtId !== 'mhbf-form') {
			return false;
		}

		var methodData = {};
		var methodsChecked = 0;
		var methodIndex = 0;

		$('.mhbf-selector-checkbox').each(function () {
			var methodName = $($('#method-title-info>.mti-title')[methodIndex]).data('title');
			methodData[methodName] = $(this).is(':checked');
			
			methodsChecked += $(this).is(':checked') ? 1 : 0;
			methodIndex += 1;
		});

		if (methodsChecked == 0) {
			alert('Please select at least one method.');
			return false;
		}

		var data = {
			type: 'bottom_form',
			aid: wgArticleId,
			platform: this.platform,
			label: '', // TODO
			methodsChecked: JSON.stringify(methodData)
		};

		this.setButtonSubmitting();
		this.setButtonLock();

		this.submit(data);

		return false;
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.submitError = function (result) {
		this.unsetButtonLock();
		this.detectButtonActive();
		
		WH.MethodHelpfulness.prototype.submitError.call(this, result);
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.submitFail = function (result) {
		this.unsetButtonLock();
		this.detectButtonActive();
		
		WH.MethodHelpfulness.prototype.submitFail.call(this, result);
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.submitSuccess = function (result) {
		if (!this.methodSelectionDone) {
			this.methodSelectionDone = true;
			this.eventId = result.eventId;
			$('#mh-bottom-form-inner').remove();
			$('#mhbf-inner-details').removeClass('mhbf-hidden');
			this.unsetButtonLock();
			this.detectButtonActive();
			$('#mhbf-details-submit').click($.proxy(this.prepareDetailsSubmit, this));
			$(".mhbf-public").on("change", function(){
				if($(this).val() == "yes") {
					$("#mhbf-public-info").show();
				} else {
					$("#mhbf-public-info").hide();
				}
			});
		} else {
			$('#mh-bottom-form').remove();
			$('#mhbf-thanks').removeClass('mhbf-hidden');
		}
	};

	window.WH.MethodHelpfulness.BottomForm.prototype.prepareDetailsSubmit = function (e) {
		var email = $.trim($('#mhbf-email').val());
		var details = $.trim($('#mhbf-details').val());
		var isPublic = ($("input[name=mhbf-public]:checked").val() == "yes");
		var firstname = $.trim($("#mhbf-firstname").val());
		var lastname = $.trim($("#mhbf-lastname").val());

		if (!details) {
			alert('Please enter some details.');
			return false;
		}

		if(isPublic && (firstname == "" || lastname == "")) {
			if (firstname == "") {
				$("#mhbf-firstname").addClass('mhbf-error');
			}
			if (lastname == "") {
				$("#mhbf-lastname").addClass('mhbf-error');
			}
			$("#mhbf-public-error").show();
			return false;
		}

		var data = {
			type: 'details_form',
			aid: wgArticleId,
			platform: this.platform,
			label: '',
			details: details,
			email: email,
			eventId: this.eventId,
			isPublic: isPublic,
			firstname: firstname,
			lastname: lastname
		};

		this.setButtonSubmitting();
		this.setButtonLock();

		this.submit(data);

		return false;
	};
}());

