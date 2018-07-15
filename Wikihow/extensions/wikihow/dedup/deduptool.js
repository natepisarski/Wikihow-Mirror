(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.DedupTool = {
		lastVote : 0,
		tool: '/Special:DedupTool',
		ids: [],
		titles: [],
		currentId: 0,
		lastIdSeen: 0,
		init: function() {
			WH.xss.addToken();

			$(".firstHeading").after("<p id='ddt_remaining'><span>-</span><br />remaining</p>")

			this.getNext();

			$(document).on('click', '#ddt_yes', function(e) {
				e.preventDefault();
				if($(this).hasClass("disabled")) {
					return false;
				} else {
					$(".ddt_button").addClass("disabled");
				}

				var info = {};
				info.ddt_id = $("#ddt_info").data("ddt_id");
				info.ddt_final = WH.DedupTool.ids[WH.DedupTool.currentId];
				WH.DedupTool.save(info);
				return false;
			});

			$(document).on('click', '#ddt_no', function(e) {
				e.preventDefault();
				if($(this).hasClass("disabled")) {
					return false;
				} else {
					$(".ddt_button").addClass("disabled");
				}

				//check to see if it's the last one
				if(WH.DedupTool.currentId == WH.DedupTool.titles.length - 1) {
					var info = {};
					info.ddt_id = $("#ddt_info").data("ddt_id");
					info.ddt_final = -1;
					WH.DedupTool.save(info);
				} else {
					//show the next one
					WH.DedupTool.showNextPair();
					$(".ddt_button").removeClass("disabled");
				}

				return false;
			});

			$(document).on('click', '#ddt_back', function(e) {
				e.preventDefault();
				WH.DedupTool.showLastPair();
			});

			$(document).on('click', '#ddt_forward', function(e) {
				e.preventDefault();
				WH.DedupTool.showNextPair();
			});

		},

		getNext: function() {
			var url = WH.DedupTool.tool+'?getNext=1';

			$('.spinner').fadeIn(function() {
				$.getJSON(url, function(data) {
					WH.DedupTool.handleNext(data);
				});
			});
		},

		handleNext: function(data) {
			if(data.error) {
				$(".ddt_body").hide();
				$("#ddt_error").show();
				$("#ddt_remaining span").html(data.count);
			} else {
				$('#ddt_title_1').html(data.title1);
				WH.DedupTool.ids = data.idsTo;
				WH.DedupTool.titles = data.titlesTo;
				WH.DedupTool.currentId = 0;
				$('#ddt_title_2').html(WH.DedupTool.titles[WH.DedupTool.currentId]);
				$("#ddt_count2").html(WH.DedupTool.titles.length);
				$("#ddt_info").data(data);
				$("#ddt_back").hide();
				$("#ddt_forward").hide();
				$("#ddt_vote_info").hide();
				$("#ddt_remaining span").html(data.count);
				WH.DedupTool.updateCurrentQuestionCount();
			}

			$(".ddt_button").removeClass("disabled");
		},

		showNextPair: function() {
			WH.DedupTool.currentId++;
			if(WH.DedupTool.currentId >= WH.DedupTool.lastIdSeen) {
				WH.DedupTool.lastIdSeen = WH.DedupTool.currentId;
				$("#ddt_forward").hide();
				$("#ddt_vote_info").hide();
			}
			$('#ddt_title_2').html(WH.DedupTool.titles[WH.DedupTool.currentId]);
			WH.DedupTool.updateCurrentQuestionCount();
			$("#ddt_back").show();
			if(WH.DedupTool.currentId == WH.DedupTool.titles.length - 1) {
				$("#ddt_forward").hide();
				$("#ddt_vote_info").hide();
			}
		},

		showLastPair: function() {
			WH.DedupTool.currentId--;
			$('#ddt_title_2').html(WH.DedupTool.titles[WH.DedupTool.currentId]);
			WH.DedupTool.updateCurrentQuestionCount();
			if(WH.DedupTool.currentId == 0) {
				$("#ddt_back").hide();
			}
			$("#ddt_forward").show();
			$("#ddt_vote_info").show();
		},

		updateCurrentQuestionCount: function() {
			$("#ddt_count1").html(WH.DedupTool.currentId+1);
		},

		save : function (data) {
			$.post(WH.DedupTool.tool, data, function(data){
				WH.DedupTool.handleNext(data);
			}, "json");
		},
	};
	$(document).ready(function() {
		WH.DedupTool.init();
	});
})();
