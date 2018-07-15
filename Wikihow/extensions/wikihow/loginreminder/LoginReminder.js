(function($, mw) {

window.WH.LoginReminder = {};

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var focusElement = "";

$(document).ready(function() {

	//show the instructions if they exist
	$(".input_med").focus(function() {
		id = $(this).attr("id");
		focusElement = id;
		if (id == "wpName2" && !$(this).val()) {
			$("#wpName2_mark").hide();
			$("#wpName2_info").show();
		} else if ($("#" + id + "_mark").is(":visible")) {
			$("#" + id + "_error").show();
		} else {
			$("#" + id + "_info").show();
		}
	});

	//hide the instructions if user isn't hovering
	//over them/
	$(".input_med").blur(function() {
		id = $(this).attr("id");
		if (focusElement == id)
			focusElement = "";
		$("#" + id + "_error").hide();

		if ($("#" + id + "_showhide").val() != 1)
			$("#" + id + "_info").hide();
	});

	// Make info and error boxes clickable
	$(".mw-info, .mw-error").mousedown(function(e) {
		e.preventDefault();
	});

	// Autofill the username field when clicking on a suggestion
	$("#wpName2_error").on("click", ".username-suggestion", function() {
		$("#wpName2").val($(this).text());
	});

	$(".mw-info").hover(function() {
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_showhide").val(1);
	}, function() {
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_showhide").val(0);
		if (id != focusElement)
			$(this).hide();
	});

	$("#wpUseRealNameAsDisplay").change(function() {
		if ($("#wpUseRealNameAsDisplay").is(':checked')) {
			$("#real_name_row").show();
		} else {
			$("#real_name_row").hide();
			$("#wpRealName").val('');
		}
	});

	if ($("#wpUseRealNameAsDisplay").is(':checked')) {
		$("#real_name_row").show();
	}

	$("#userloginForm #wpName, #userloginForm #wpName2").blur(function() {
		checkName($(this).val());
	})

	$("#userloginForm #wpPassword2").blur(function() {
		pass1 = $(this).val();
		if (pass1.length < 4 && pass1.length > 0) {
			$("#wpPassword2_error").html(mw.message('lr_choose_longer_password').text());
			$("#wpPassword2_mark").show();
		} else {
			$("#wpPassword2_mark").hide();
		}
	});

	$("#userloginForm #wpRetype").blur(function() {
		pass1 = $("#userloginForm #wpPassword2").val();
		pass2 = $("#userloginForm #wpRetype").val();

		if (pass1 != pass2) {
			$("#wpRetype_error").html(mw.message('lr_passwords_dont_match').text());
			$("#wpRetype_mark").show();
		} else {
			$("#wpRetype_mark").hide();
		}
	});

	$(".wpMark").click(function() {
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_error").show();
		$("#" + id).focus();
	});

	$(document).on('click', "#forgot_pwd", function() {
		if ($("#wpName1").val() == 'Username or Email') $("#wpName1").val('');
		getPassword($("#wpName1").val());
		return false;
	});

	$("#forgot_pwd_head").click(function() {
		if ($("#wpName1_head").val() == 'Username or Email') $("#wpName1_head").val('');
		getPassword($("#wpName1_head").val());
		return false;
	});
});

function checkName(username) {
	var params = 'username=' + encodeURIComponent(username);
	var that = this;
	var url = '/Special:LoginCheck?' + params;
	$.get(url, function(json) {
		if (json) {
			data = $.parseJSON(json);
			if (data.error) {
				$("#wpName2_error").html(data.error);
				$("#wpName2_mark").show();
			} else {
				$("#wpName2_mark").hide();
			}
		} else {
			$("#wpName2_mark").hide();
		}
	});
}

var whWasPasswordReset = false;

function getPassword(username) {
	//close any open modals
	if ($.modal) $.modal.close();

	//since this can be called from an article page
	//make sure the jquery ui is loaded
	mw.loader.using( ['jquery.ui.dialog'], function () {
		$('#dialog-box').html('');
		url = "/Special:LoginReminder?name=" + encodeURIComponent(username);

		$('#dialog-box').load(url, function() {
			whWasPasswordReset = false;
			$('#dialog-box').dialog({
				width: 650,
				modal: true,
				title: mw.message('lr_password_reset').text(),
				closeText: 'x',
				close: function() {
					if (whWasPasswordReset) {
						$('#password-reset-dialog').dialog({
							width: 250,
							modal: true
						});
						$('#password-reset-ok').click(function() {
							$('#password-reset-dialog').dialog('close');
							return false;
						});
					}
				}
			});
		});
	});
}

WH.LoginReminder.checkSubmit = function(name, captchaWord, captchaId) {
	var params = 'submit=true&name=' + encodeURIComponent($("#" + name).val()) + '&wpCaptchaId=' + $("#" + captchaId).val() + '&wpCaptchaWord=' + $("#" + captchaWord).val();
	var that = this;
	var url = '/Special:LoginReminder?' + params;
	$.get(url, function(json) {
		if (json) {
			data = $.parseJSON(json);
			$(".mw-error").hide();
			if (data.success) {
				whWasPasswordReset = true;
				$('#form_message').html(data.success);
				$('#dialog-box').dialog('close');
			} else {
				if (data.error_username) {
					$('#wpName2_error').html(data.error_username);
					$('#wpName2_error').show();
				}
				if (data.error_captcha) {
					$('#wpCaptchaWord_error').html(data.error_captcha);
					$('#wpCaptchaWord_error').show();
					$('.captcha').html(decodeURI(data.newCaptcha));
				}
				if (data.error_general) {
					$('#wpName2_error').html(data.error_general);
					$('#wpName2_error').show();
				}
			}
		} else {

		}
	});
	return false;
}

})(jQuery, mw);
