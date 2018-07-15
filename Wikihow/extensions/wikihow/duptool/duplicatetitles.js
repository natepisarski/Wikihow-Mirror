(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.DupTool = {
		lastVote : 0,
		tool: '/Special:DuplicateTitles',
		init: function() {
			WH.xss.addToken();

			$(".firstHeading").after("<p id='dt_remaining'><span>-</span><br />remaining</p>")

			this.getNext();

			$(document).on('click', '#dt_yes', function(e) {
				e.preventDefault();
				if($(this).hasClass("disabled")) {
					return false;
				} else {
					$(".dt_button").addClass("disabled");
				}
				
				var data = $("#dt_info").data();
				data.vote = 1;
				WH.DupTool.save(data);
				return false;
			});

			$(document).on('click', '#dt_no', function(e) {
				e.preventDefault();
				if($(this).hasClass("disabled")) {
					return false;
				} else {
					$(".dt_button").addClass("disabled");
				}

				var data = $("#dt_info").data();
				data.vote = -1;
				WH.DupTool.save(data);
				return false;
			});

			$(document).on('click', '#dt_maybe', function(e) {
				e.preventDefault();
				if($(this).hasClass("disabled")) {
					return false;
				} else {
					$(".dt_button").addClass("disabled");
				}

				WH.DupTool.getNext();
				return false;
			});
		},

		voteDone: function() {
			this.getNext();
		},

		getNext: function() {
			var url = this.tool+'?getNext=1';
			
				$('.spinner').fadeIn(function() {
					$.getJSON(url, function(data) {
						if (data.remaining == 0) {
							$('#header-count').html(data.remaining);
						} else {
							$('#dt_title_1').html(data.title1);
							$('#dt_title_2').html(data.title2);
							$("#dt_info").data(data);
							$("#dt_remaining span").html(data.count);
						}
						
						$(".dt_button").removeClass("disabled");

						$('.spinner').fadeOut(function() {
						});
					});
				});
		},

		updateStats : function() {
			var statboxes = '#iia_stats_today_duplicatetitlesvoted,#iia_stats_week_duplicatetitlesvoted,#iia_stats_all_duplicatetitlesvoted';
			$(statboxes).each(function(index, elem) {
					$(this).fadeOut(function () {
						var cur = parseInt($(this).html());
						$(this).html(cur + 1);
						$(this).fadeIn();
					});
				}
			);
		},

		save : function (data) {
			$.post(this.tool, data).done($.proxy(this, 'voteDone'));
			if(data.vote != 0) {
				WH.DupTool.updateStats();
			}
		},
	};
	$(document).ready(function() {
		WH.DupTool.init();
	});
})();
