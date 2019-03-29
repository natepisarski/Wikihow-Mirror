( function ( mw, $ ) {

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
			$('.sp_top_box_hoverable').hover(function() {
				dialog_box(true, this, 'icon_hover');
			}, function() {
				dialog_box(false, this, 'icon_hover');
			});

			//(i) icon & top badge hover
			$('.sp_info_icon, #sp_icon_hover, .sp_expert_inline, .expert_coauthor_link').hover(function() {
				dialog_box(true, this, 'icon_hover');
				if ($(this).hasClass('sp_info_icon')) WH.maEvent('article-information-hover');
			}, function() {
				dialog_box(false, this, 'icon_hover');
			});

			$('.sp_expert_inline, .expert_coauthor_link').click(function() {
				return false;
			});

			//(i) dialog link tracking
			$(document).on('click', '.sp_learn_more_link', function() {
				WH.maEvent('article-information-hover-learnmore-click');
			});
		}
		else {
			var clickable_elements = '.ec_view, .sp_expert_inline, .expert_coauthor_link';

			// badge at the top
			$(clickable_elements).click(function(e) {
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
				var obj = $(this).parent().find(clickable_elements);
				dialog_box(false, obj, 'badge_click');
			});

			$("body").click(function(){
				if($("#sp_icon_hover").is(':visible')) {
                    dialog_box(false, this, 'badge_click');
				}
			})
		}

		var on_bubble = false;

		function dialog_box(show, obj, type) {
			var finalTopPopupPosition, startTopPopupPosition, popupContainer;

			if (type == 'icon_hover') {
				if ($('#sp_icon_hover').length == 0) return;

				popupContainer = $('#sp_icon_hover');

				if ($(obj).is($(popupContainer)))
					finalTopPopupPosition = $(popupContainer).position().top;
				else
					finalTopPopupPosition = $(obj).position().top + $(obj).height() + 2;

				if ($(obj).is($('.expert_coauthor_link'))) {
					$(popupContainer).css('left', $(obj).position().left);
				}
				else {
					if (!$(obj).is($('#sp_icon_hover'))) {
						$(popupContainer).css('left', 667); //reset (gotta keep in sync w/ main.css; lame)
					}
				}

				startTopPopupPosition = finalTopPopupPosition + 10;
			}
			else if (type == 'badge_click') {
				if ($('#sp_icon_hover').length == 0) return;

				popupContainer = $('#sp_icon_hover');
				finalTopPopupPosition = $(obj).position().top + $(obj).height() + 15;
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
