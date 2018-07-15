jQuery.extend(WH, (function($) {

	function TPCoachAdmin() {
		var toolURL = "/Special:TPCoachAdmin";
		var selectedTab = "scores";
		var tabs = ["scores", "tests"];
		this.init = function() {
			console.log("init TPCoachAdmin");
			selectTab('tests');
		}

		$('#tpc_enable').click(function(e) {
			e.preventDefault();
			var answer = confirm("Are you sure you want to enable TipsPatrol Coach?");
			if (!answer) {
				return;
			}
			$("#tpc_enable").hide();
			$("#tpc_disable").show();
			toggleTPCoach("on");
		});

		$('#tpc_disable').click(function(e) {
			e.preventDefault();
			var answer = confirm("Are you sure you want to disable TipsPatrol Coach?");
			if (!answer) {
				return;
			}
			$("#tpc_disable").hide();
			$("#tpc_enable").show();
			toggleTPCoach("off");
		});

		function toggleTPCoach(setting) {
			alert('Change the mediawiki message named "tp_coach_enabled" from 1 to 0');
		/*
			$.post(toolURL, {action:"tpc_toggle", setting:setting},
				function (result) {
					debugResult(result);
					if (result['success'] == true) {
						alert("TipsPatrol Coach has been set to: " + setting);
					}
				},
				'json'
			);
		*/
		}

		$('.tpc_delete_test').click(function(e) {
			e.preventDefault();
			var testId = $(this).attr("testId");
			var action = "delete_test";
			$.post(toolURL, {
				testId:testId,
				action:action
				},
				function (result) {
					debugResult(result);
					if (result['success'] == true) {
						location.reload(true);
					}
				},
				'json'
			);
		});

		$('#tpc_newtest_submit').click(function(e) {
			e.preventDefault();
			var tip = $('#tpc_input_tip').val();
			var page = $('#tpc_input_page').val();
			var failMessage = $('#tpc_input_fail_message').val();
			var successMessage = $('#tpc_input_success_message').val();
			var difficulty = $('#tpc_select_difficulty').val();
			var answer = $('#tpc_select_answer').val();
			var action = "newtest";
			if (tip && page && answer) {
				$.post(toolURL, {
					tip:tip,
					page:page,
					failMessage:failMessage,
					successMessage:successMessage,
					difficulty:difficulty,
					answer:answer,
					action:action
					},
					function (result) {
						debugResult(result);
						if (result['success'] == true) {
							location.reload(true);
							//$('#tpc_input_tip').val("");
							//$('#tpc_input_page').val("");
						}
					},
					'json'
				);
			} else {
				alert("can't add test: incomplete data");
			}
		});

		function debugResult(result) {
			console.log("debug: ");
			for (i in result['debug']) {
				console.log(result['debug'][i]);
			}
		}

		function selectTab(tab) {
			if (tab == selectedTab) {
				return;
			}

			selectedTab = tab;

			for (var i in tabs) {
				if (tab == tabs[i]) {
					$('#tab-'+tabs[i]).addClass("on");
					if ($('#content-'+tabs[i]).length) {
						$('#content-'+tabs[i]).show();
					} else {
						$('#content-loading').show();
					}
				} else {
					$('#tab-'+tabs[i]).removeClass("on");

					// if we have the tab turn it off
					if ($('#content-'+tabs[i]).length) {
						$('#content-'+tabs[i]).hide();
					}
				}
			}
		}

		$('.tpc-tab').click(function(e) {
			e.preventDefault();
			selectTab($(this).attr('title'));
		});

		$('.blockuser').click(function(e) {
			e.preventDefault();
			var userId = $(this).attr("userId");
			var action = "blockuser";
			if ($(this).html() == "Unblock") {
				action = "unblockuser";
			}
			$.post(toolURL, {
					userId:userId,
					action:action
					},
					function (result) {
						debugResult(result);
						if (result['success'] == true) {
							location.reload(true);
						}
					},
					'json'
				);
			return;
		});

		$('.reset_test').click(function(e) {
			e.preventDefault();
			var userId = $(this).attr("userId");
			var action = "reset";
			console.log("reset test for: " + userId);
			$.post(toolURL, {
					userId:userId,
					action:action
					},
					function (result) {
						debugResult(result);
						if (result['success'] == true) {
							location.reload(true);
						}
					},
					'json'

				);

			return;
		});

	}

	$(document).ready(function() {
		var tpCoachAdmin = new TPCoachAdmin();
		tpCoachAdmin.init();
	});

})(jQuery));
