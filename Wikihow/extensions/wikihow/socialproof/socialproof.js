( function ( mw, $ ) {

	var toolUrl = '/Special:SocialProof';
	var starVotingEnabled = false;

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
		var box = $(this).closest('.sp_box');
		$(box).find('.sp_helpful_rating_count').hide();
		$(box).find('.sp_star_rating_text').show();
		$(box).find('.sp_star_container').addClass('star_editing');
	}

	function hideStarRating() {
		var box = $(this).closest('.sp_box');
		$(box).find('.sp_helpful_rating_count').show();
		$(box).find('.sp_star_rating_text').hide();
		$(box).find('.sp_star_container').removeClass('star_editing');
	}

	function votedStarRating() {
		$(".sp_star_container").off("click");
		$(".sp_star_container").off("hover");
		$(".sp_star_section_upper").off("hover");
		$(".sp_star_section_upper").off('mouseenter mouseleave');
		$(".sp_helpful_lower").off("hover");
		$(".sp_helpful_hoverable").removeClass("sp_box_hoverable");
		$(".sp_star_section_hoverable,.helpfulness_text").off("hover");

		var thanks = mw.msg('sp_votethanks');
		if ($('.helpful_sidebox').length) {
			$('#sp_helpful_text_sidebox').html(thanks);
		} else {
			$(".sp_star_rating_text").hide();
			$('.helpfulness_text').html(thanks);
		}

		// Second star hover on desktop that needs turning off
		for (var k = 1; k <= 5; k++) {
			$('#sidebar').off('mouseenter mouseleave click', '#star' + k);
		}
	}

	function enableStarVoting() {
		starVotingEnabled = true; //for everyone!
	}

	$(document).ready( function() {
		enableStarVoting();

		if (starVotingEnabled) {
			$(".sp_star_section_upper").on({
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
			var currStarId = i;

			$(".star" + i).bind({
				mouseenter: function () {
					var box = $(this).closest('.sp_box');
					for (var j = 1; j <= currStarId; j++){
						$(box).find(".star" + j + " > div").addClass("mousevote");
					}
					$(box).find(".sp_star_rating_text").text(voteText[currStarId]);
					$("#sp_star_rating_text").text(voteText[currStarId]);
				},
				mouseleave: function () {
					var box = $(this).closest('.sp_box');
					for (var j = 1; j <= currStarId; j++){
						$(box).find(".star" + j + " > div").removeClass("mousevote");
					}
					$(box).find(".sp_star_rating_text").text("");
				},
				click: function () {
					var box = $(this).closest('.sp_box');
					for (var j = 1; j <= currStarId; j++){
						$(box).find(".star" + j + " > div").addClass("mousedone");
					}
					var postData = {
							'action': 'rate_page',
							'page_id': wgArticleId,
							'rating': currStarId,
							'type': 'star',
							'source': WH.isMobile ? 'mobile' : 'desktop'
					};
					$.post('/Special:RateItem',
						postData,
						function(result) {
						},
						'json'
						);
					votedStarRating()
				}
			} );
			$('#sidebar').on('mouseleave', '#star' + i, function () {
				for (var j = 1; j <= currStarId; j++) {
					$("#star" + j + " > div").removeClass("mousevote");
				}
				$("#sp_star_rating_text").text("");
			} );
			$('#sidebar').on('click', '#star' + i, function () {
				for (var j = 1; j <= currStarId; j++) {
					$("#star" + j + " > div").addClass("mousedone");
				}
				var postData = {
					'action': 'rate_page',
					'page_id': wgArticleId,
					'rating': currStarId,
					'type': 'star',
					'source': WH.isMobile ? 'mobile' : 'desktop'
				};
				$.post('/Special:RateItem',
					postData,
					function(result) { },
					'json' );
				votedStarRating();
			} );
		}

		if (starVotingEnabled) {
			for (var k = 1; k <= 5; k++) {
				starBehavior(k);
			}
		}

		if ($(".sp_helpful_box").length > 0) {
			$('.sp_popup_container').css("top", $(".sp_star_section_upper").position().top - $(".sp_popup_container").height() + 5);
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

		$(".helpfulness_text, #sp_helpful_text_sidebox").on({
			mouseenter: displayHelpfulnessPopup,
			mouseleave: hideHelpfulnessPopup
		});

		if ($('.sp_expert').length > 0  && $(window).width() >= WH.largeScreenMinWidth) {
			$('.sp_expert').click(function(e) {
				//e.preventDefault();
				if ($('#sp_namelink').length) {
					var target = $('#sp_namelink').attr('href');
					if (WH && WH.ga) {
						var action = 'expert_name_click';
						var val = $('#sp_namelink').text();
						WH.ga.sendEvent('socialproof', action, val, null, 0);
						/*WH.sp.sendEvent(action, val, target, function() {
							//document.location = target;
						});*/
						return true;
					}
				}
			});
		}

		if ($('.sp_namelink').length && $(window).width() >= WH.largeScreenMinWidth) {
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
					/*WH.sp.sendEvent(action, val, target, function() {
						//document.location = target;
					});*/
					return true;
				}
			});
		}

		$('.expert_coauthor_link').click(function() {
			return false;
		});

		//(i) dialog link tracking
		$(document).on('click', '.sp_learn_more_link', function() {
			WH.maEvent('article-information-hover-learnmore-click');
		});

		var elements = '.sp_info_icon, .expert_coauthor_link, '+
			'#sidebar .sp_top_box_hoverable.sp_nonverifier_text, '+
			'#sidebar .sp_community .sp_top_box_hoverable';
		var actions = $(window).width() >= WH.largeScreenMinWidth ? 'mouseenter mouseleave click' : 'click';

		// badge at the top
		$(elements).on(actions, function(e) {
			e.preventDefault();

			//slightly different logic for mobile taps than the desktop hover
			var show = e.type == 'click' ? !$('#sp_icon_hover').is(':visible') : e.type == 'mouseenter';

			dialog_box(show, this, e.type);

			if (!show && e.type == 'click') WH.maEvent('article-info-badge-click-mobile');
		});

		//badge dialog closing
		$('#sp_icon_hover').on(actions, function(e) {
			if (e.type == 'click' && $(e.target).length && $(e.target).is('a')) return;

			e.preventDefault();

			//slightly different logic for mobile taps than the desktop hover
			var show = e.type == 'click' ? !$(this).is(':visible') : e.type == 'mouseenter';

			dialog_box(show, this, e.type);
		});

		$("body").click(function(e){
			if($("#sp_icon_hover").is(':visible')) {
				dialog_box(false, this, e.type);
			}
		})

		var on_bubble = false;

		function dialog_box(show, obj, type) {
			var finalTopPopupPosition, startTopPopupPosition;
			var finalLeftPopupPosition = 0;
			var popupContainer = $('#sp_icon_hover');
			var objHeight = $(obj).height();

			if (type != 'click') {
				if ($('#sp_icon_hover').length == 0) return;

				if (!$(obj).is($(popupContainer))) {
					//for sidebar, use the whole box because it's positioned correctly
					var offset = $(obj).is('.sp_top_box_hoverable') ? $('#social_proof_sidebox').position().top : 2;
					finalTopPopupPosition = $(obj).position().top + objHeight + offset;
				}

				if ($(obj).is($('.expert_coauthor_link'))) {
					finalLeftPopupPosition = $(obj).position().left;
				}
				else if ($(obj).is($('.sp_top_box_hoverable'))) {
					finalLeftPopupPosition = 619;
				}

				startTopPopupPosition = finalTopPopupPosition + 10;
			}
			else {
				if ($(popupContainer).length == 0) return;

				finalTopPopupPosition = $(obj).position().top + objHeight + 15;
				startTopPopupPosition = finalTopPopupPosition + 10;

				if (WH.shared.isDesktopSize && $(obj).is($('.expert_coauthor_link'))) {
					finalLeftPopupPosition = $(obj).position().left;
				}
			}

			if (show) {
				var wait = on_bubble && type != 'click' ? 300 : 0;
				var speed = 75;
				clearTimeout($(obj).data('sp_timeout2'));

				if (!$(obj).is(popupContainer)) $(popupContainer).css('left', finalLeftPopupPosition);

				$(obj).data('sp_timeout', setTimeout( function () {
					$(popupContainer)
						.stop(true)
						.fadeIn({queue: false, duration: speed})
						.animate({ top: finalTopPopupPosition, opacity: 1 }, speed);

					on_bubble = true;
					}, wait)
				);
			}
			else {
				var wait = type != 'click' ? 300: 0;
				var speed = 65;
				clearTimeout($(obj).data('sp_timeout'));

				$(obj).data('sp_timeout2', setTimeout( function () {
					$(popupContainer)
						.stop(true)
						.fadeOut({queue: false, duration: speed})
						.animate({ top: startTopPopupPosition }, speed);
					on_bubble = false;
					}, wait)
				);
			}
		}
	});
}( mediaWiki, jQuery ) );
