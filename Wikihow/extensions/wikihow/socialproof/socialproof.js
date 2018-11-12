( function ( mw, $ ) {

	WH.convertSocialProofDates = (function() {
		// TODO why would there be more than one of something with an ID?
		$('#sp_modified').each( function() {
			if ( $(this).attr('data-datestamp') ) {
				if ($(this).attr('data-datestamp').length < 1 ) {
					$(this).html("");
					return;
				}
				var d = $.format.prettyDateSP($(this).attr('data-datestamp'));
				if (!d) {
					$(this).html("");
					return;
				}
				var text = d.text();

				if (text.length < 1) {
					$(this).html("");
					return;
				}
				var len = text.length;
				var text = '<span class="sp_text_data">' + text + "</span>";
				if (len > 12) {
					$(this).parent('#sp_updated_text').css("font-size", 12);
				}
				$(this).html($(this).html() + text);
			}
		});
	});

	var toolUrl = '/Special:SocialProof';

	WH.sp = (function() {
		function sendEvent(action, expert_name, target, callbackFunc) {
			var data = { 'action': action, 'expert_name' : expert_name, 'article_id': wgArticleId, 'target' :target };

			$.post(toolUrl, data, function (result) {
					callbackFunc();
				}, "json").fail( function() {
					callbackFunc();
				});

		}
		return {
			sendEvent : function(action, expert_name, target, callbackFunc) {
				sendEvent(action, expert_name, target, callbackFunc);
			},
		};
	}());

	function showStarRating() {
		$('#sp_helpful_rating_count').hide();
		$('#sp_star_rating_text').show();
		$('.sp_star_container').addClass('star_editing');
	}
	function hideStarRating() {
		$('#sp_helpful_rating_count').show();
		$('#sp_star_rating_text').hide();
		$('.sp_star_container').removeClass('star_editing');
	}
	function votedStarRating() {
		$(".sp_star_container").off("click");
		$(".sp_star_container").off("hover");
		$("#sp_star_section_upper").off("hover");
		$("#sp_helpful_lower").off("hover");
		$(".sp_helpful_hoverable").removeClass("sp_box_hoverable");
		$(".sp_star_section_hoverable,#helpfulness_text").off("hover");

		var thanks = mw.msg('sp_votethanks');
		if ($('.helpful_sidebox').length) {
			$('#sp_helpful_text_sidebox').html(thanks);
		}
		else {
			$("#sp_star_rating_text").hide();
			$('#helpfulness_text').html(thanks);
		}
	}

	$(document).ready( function() {

		if (!WH.isMobileDomain) {
			$("#sp_star_section_upper").on({
				mouseenter: showStarRating,
				mouseleave: hideStarRating
			});
		}

		var voteText = {
			1: mw.msg('sp_star_label_1'),
			2: mw.msg('sp_star_label_2'),
			3: mw.msg('sp_star_label_3'),
			4: mw.msg('sp_star_label_4'),
			5: mw.msg('sp_star_label_5'),
		};

		function starBehavior(i) {
			//for some reason we only allow mobile voting...UNLESS there are zero votes (?!?)
			if (WH.isMobileDomain && $("#sp_helpful_box").data('helpful') > 0) return;

			var currStarId = i;
			$("#star" + i).bind({
		  		mouseenter: function () {
		  			for (var j = 1; j <= currStarId; j++){
		  				$("#star" + j + " > div").addClass("mousevote");
		  			}
		  			$("#sp_star_rating_text").text(voteText[currStarId]);
		  		},
				mouseleave: function () {
	  			for (var j = 1; j <= currStarId; j++){
	  				$("#star" + j + " > div").removeClass("mousevote");
	  			}
	  			$("#sp_star_rating_text").text("");
				},
				click: function () {
		  		for (var j = 1; j <= currStarId; j++){
						$("#star" + j + " > div").addClass("mousedone");
					}
					var postData = {
							'action': 'rate_page',
							'page_id': wgArticleId,
							'rating': currStarId,
							'type': 'star',
							'source': 'desktop'
					};
					$.post('/Special:RateItem',
						postData,
						function(result) {
						},
						'json'
						);
					votedStarRating()
				}
			});

		}
		for (k = 1; k <= 5; k++) {
			starBehavior(k);
		}

		WH.convertSocialProofDates();

		if ($("#sp_helpful_box").length > 0) {
			$('.sp_popup_container').css("top", $("#sp_star_section_upper").position().top - $(".sp_popup_container").height() + 5);
		}

		function displayHelpfulnessPopup() {
			$('.sp_popup_container')
				.fadeIn({queue: false, duration: 150})
				.animate({ top: "-=13px" }, 150);
		}

		function hideHelpfulnessPopup() {
			$('.sp_popup_container')
				.fadeOut({queue: false, duration: 130})
				.animate({ top: "+=13px" }, 130);
		}

		$("#helpfulness_text, #sp_helpful_text_sidebox").on({
			mouseenter: displayHelpfulnessPopup,
			mouseleave: hideHelpfulnessPopup
		});

		if ($('#sp_expert').length > 0 ) {
			$('#sp_expert').click(function(e) {
				//e.preventDefault();
				if ($('#sp_namelink').length) {
					var target = $('#sp_namelink').attr('href');
					if (WH && WH.ga) {
						var action = 'expert_name_click';
						var val = $('#sp_namelink').text();
						WH.ga.sendEvent('socialproof', action, val, null, 0);
						WH.sp.sendEvent(action, val, target, function() {
							//document.location = target;
						});
						return true;
					}
				}
			});
		}

		if ($('.sp_blurblink').length) {
			$('.sp_blurblink').click(function(e) {
				//e.preventDefault();
				var target = $(this).attr('href');
				if (WH && WH.ga) {
					var action = 'blurb_click';
					var val = $('.sp_blurblink').text();
					WH.ga.sendEvent('socialproof', action, val, null, 0);
					WH.sp.sendEvent(action, val, target, function() {
						//document.location = target;
					});
					return true;
				}
			});
		}

		if ($('.sp_namelink').length) {
			$('.sp_namelink').click(function(e) {
				var target = $(this).attr('href');
				if (WH && WH.ga) {
					//e.preventDefault();
					var action = 'expert_name_click';
					// check if in hover box or regular box for click tracking
					if ($(this).parents('.hint_box').length > 0) {
						action = 'hover_name_click';
					}
					var val = $('.sp_namelink').text();
					WH.ga.sendEvent('socialproof', action, val, null, 0);
					WH.sp.sendEvent(action, val, target, function() {
						//document.location = target;
					});
					return true;
				}
			});
		}

		if (!WH.isMobileDomain) {
			//side bar hover
			$('.sp_top_box_hoverable, .sp_top_popup_container').hover(function() {
				dialog_box(true, this, 'expert_dialog');
			}, function() {
				dialog_box(false, this, 'expert_dialog');
			});

			//(i) icon hover
			$('.sp_info_icon, #sp_icon_hover').hover(function() {
				dialog_box(true, this, 'icon_hover');
				if ($(this).hasClass('sp_info_icon')) WH.maEvent('article-information-hover');
			}, function() {
				dialog_box(false, this, 'icon_hover');
			});

			//(i) dialog link tracking
			$(document).on('click', '.sp_learn_more_link', function() {
				WH.maEvent('article-information-hover-learnmore-click');
			});
		}
		else {
			// badge at the top
			$('.sp_intro_expert, .tech_article_stamp, .sp_intro_user').click(function(e) {
				e.preventDefault();
				if ($('#sp_icon_hover').is(':visible')) {
					dialog_box(false, this, 'badge_click');
				}
				else {
					dialog_box(true, this, 'badge_click');
					WH.maEvent('article-info-badge-click-mobile');
				}
			});

			//badge dialog click (close it)
			$('#sp_icon_hover').click(function() {
				var obj = $(this).parent().find('.sp_intro_expert, .tech_article_stamp, .sp_intro_user');
				dialog_box(false, obj, 'badge_click');
			});

			// (i) in the bottom section
			$('.sp_expert_icon_info').click(function() {
				if ($('.sp_top_popup_container').is(':visible')) {
					dialog_box(false, this, 'expert_dialog');
				}
				else {
					dialog_box(true, this, 'expert_dialog');
				}
			});
		}

		var on_bubble = false;

		function dialog_box(show, obj, type) {
			var finalTopPopupPosition, startTopPopupPosition, popupContainer;

			if (type == 'icon_hover') {
				if ($('#sp_icon_hover').length == 0) return;

				popupContainer = $('#sp_icon_hover');
				finalTopPopupPosition = $('.sp_info_icon').position().top + $('.sp_info_icon').height() + 2;
				startTopPopupPosition = finalTopPopupPosition + 10;
			}
			else if (type == 'expert_dialog') {
				if ($('.sp_top_popup_container').length == 0 || ($('.sp_expert_text').length == 0)) return;

				popupContainer = $('.sp_top_popup_container');
				finalTopPopupPosition = $('.sp_expert_text').position().top - $(".sp_top_popup_container").height();
				startTopPopupPosition = finalTopPopupPosition - 10;
			}
			else if (type == 'badge_click') {
				if ($('#sp_icon_hover').length == 0) return;

				popupContainer = $('#sp_icon_hover');
				finalTopPopupPosition = $(obj).position().top + $(obj).height() + 31;
				startTopPopupPosition = finalTopPopupPosition + 10;
			}
			else {
				return;
			}

			if (show) {
				var wait = on_bubble && type != 'badge_click' ? 300 : 0;
				var speed = 75;
				clearTimeout($(obj).data('sp_timeout2'));

				$(obj).data('sp_timeout', setTimeout( function () {
					$(popupContainer)
						.stop(true)
						.fadeIn({queue: false, duration: speed})
						.animate({ top: finalTopPopupPosition, opacity: 1 }, speed);

					on_bubble = true;
				  }, wait));
			}
			else {
				var wait = type != 'badge_click' ? 300: 0;
				var speed = 65;
				clearTimeout($(obj).data('sp_timeout'));

				$(obj).data('sp_timeout2', setTimeout( function () {
					$(popupContainer)
						.stop(true)
						.fadeOut({queue: false, duration: speed})
						.animate({ top: startTopPopupPosition }, speed);
					on_bubble = false;
				  }, wait));
			}
		}
	});
}( mediaWiki, jQuery ) );
