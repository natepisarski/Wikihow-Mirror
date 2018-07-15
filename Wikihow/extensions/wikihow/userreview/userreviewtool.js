(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.UserReviewTool = {
		toolUrl: "/Special:UserReviewTool",
		eventCategory: "userreview",
		cookieNameApproved: "approved_count",
		cookieNameDeleted: "deleted_count",

		init: function () {
			$(".firstHeading").before("<p class='ur_info'><span id='ur_now'>0</span> approved today<br /><span id='ur_delete_now'>0</span> deleted today<br /><span id='ur_queue'>XXXX</span> in queue</p>");

			this.addEventHandlers();

			this.getNext();

			this.getUserCounts();
		},

		getUserCounts: function() {
			var approvedCount = $.cookie(this.cookieNameApproved);
			var deletedCount = $.cookie(this.cookieNameDeleted);
			if(!approvedCount) {
				approvedCount = 0;
			}
			if(!deletedCount) {
				deletedCount = 0;
			}
			var $count = $("#ur_now");
			$count.text(approvedCount);
			var $count = $("#ur_delete_now");
			$count.text(deletedCount);
		},

		addEventHandlers: function() {
			$(document).on('focus', '.ur_review', function (e) {
				e.preventDefault();
				$(this).parent().find(".ur_original").css("visibility", "visible");
			});

			$(document).on('click', '.ur_skip', this.handleSkip);
			$(document).on('click', '.ur_delete', this.delete);
			$(document).on('click', '.ur_approve', this.approve);
			$(document).on('click', '.ur_edit', this.edit);
			$(document).on('click', '.ur_save', this.save);
		},

		handleSkip: function(e){
			e.preventDefault();
			var ids = [];
			$(".ur_submitted:visible").each(function(){
				ids.push($(this).attr("reviewid"));
			});
			var aid = $(this).parent().find('input[name="aid"]').val();

			$.post(
				this.toolUrl,
				{
					a: 'skip',
					ids: ids,
					aid: aid
				},
				function(result) {
					WH.UserReviewTool.handleNewData(result)
				},
				'json'

			);
		},

		getNext: function(){
			$.post(
				this.toolUrl,
				{
					a: 'getNext',
					article: mw.util.getParamValue('article')
				},
				this.handleNewData,
				'json'

			);
		},

		delete: function(e) {
			e.preventDefault();
			var id = $(this).parent().attr("reviewid");
			var aid = $(this).parent().find('input[name="aid"]').val();
			$.post(
				this.toolUrl,
				{
					a: 'delete',
					id: id,
					aid: aid
				},
				function(result) {

				},
				'json'

			);
			WH.whEvent(WH.UserReviewTool.eventCategory, 'review_tool_delete');
			if ($(this).parents().hasClass("ur_curated")) {
				WH.UserReviewTool.decreaseCurated();
			} else {
				WH.UserReviewTool.decreaseSubmitted();
				WH.UserReviewTool.increaseDeleteNowCount();
			}
			$(this).parent().hide();
		},

		approve: function(e) {
			e.preventDefault();
			var data = $(this).parent().find("form").serializeArray();
			data.push({name: 'a', value: 'approve'});
			data.push({name: 'id', value: $(this).parent().attr("reviewid")});
			$.post(
				this.toolUrl,
				data,
				function(result) {
				},
				'json'

			);
			WH.whEvent(WH.UserReviewTool.eventCategory, 'review_tool_approve');
			$(this).parent().appendTo($("#ur_curated_list")).addClass("disabled ur_curated locked").removeClass("ur_submitted");
			WH.UserReviewTool.increaseCurated();
			WH.UserReviewTool.decreaseSubmitted();
			WH.UserReviewTool.increaseNowCount();
		},

		edit: function(e) {
			e.preventDefault();
			var $review = $(this).parents(".ur_curated");
			$review.removeClass("locked");
			$review.find("input, textarea").prop("disabled", false);
			$(".ur_save", $review).show();
		},

		save: function(e) {
			e.preventDefault();
			$(this).hide();
			var $review = $(this).parent();
			var data = $review.find("form").serializeArray();
			$review.addClass("locked").find("input, textarea").prop("disabled", true);
			data.push({name: 'a', value: 'save'});
			data.push({name: 'id', value: $(this).parent().attr("reviewid")});
			$.post(
				this.toolUrl,
				data,
				function(result) {
					$(".ur_edit").show();
				},
				'json'

			);
		},

		handleNewData: function(result) {
			if(result.success == false) {
				$("#bodycontents").html("That's all for now! Please check back later for more testimonials to curate.");
			} else {
				$("#bodycontents").html(result.html);
				$("#ur_queue").html(result.count);

				$(".ur_submitted").each(function () {
					$(".ur_review", this).height($(".ur_original div", this).height());
				});
			}
		},

		decreaseSubmitted: function() {
			var $count = $("#ur_submitted_list h3 span");
			$count.text(parseInt($count.text())-1);
		},

		increaseCurated: function(){
			var $count = $("#ur_curated_list h3 span");
			$count.text(parseInt($count.text())+1);
		},

		decreaseCurated: function(){
			var $count = $("#ur_curated_list h3 span");
			$count.text(parseInt($count.text())-1);
		},

		increaseNowCount: function(){
			var $count = $("#ur_now");
			var newCount = parseInt($count.text())+1;
			$count.text(newCount);
			var d = new Date();
			d.setHours(24,0,0,0); //expire at midnight their time
			document.cookie = this.cookieNameApproved + "=" + newCount + "; expires=" + d.toGMTString() + "; path=/";
		},

		increaseDeleteNowCount: function(){
			var $count = $("#ur_delete_now");
			var newCount = parseInt($count.text())+1;
			$count.text(newCount);
			var d = new Date();
			d.setHours(24,0,0,0); //expire at midnight their time
			document.cookie = this.cookieNameDeleted + "=" + newCount + "; expires=" + d.toGMTString() + "; path=/";
		}

	};

	$(document).ready(function(){
		WH.UserReviewTool.init();
	});
}($, mw));
