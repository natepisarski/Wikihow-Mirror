( function ( mw, $ ) {


	WH.uciPatrol = (function() {
		var toolURL = "/Special:UCIPatrol";
		var thumbUrl;
		var pageId;
		var nextImageData;
		var LEADERBOARD_REFRESH = 10 * 60;
		var upVotes;
		var downVotes;
		var imageWidth;
		var action;
		var article;
		var articleTitle;
		var articleId;
		var articleDisplayTitle;
		var articleURL;
		var voteMultiplier;
		var requiredUpvotes;
		var requiredDownvotes;
		var userVoter;
		var userVoterShown = false;
		var isMobile = false;
		var pqu_id;

		// Init shortcut key bindings
		$(document).on( "click", "#uci_bad", function(e) {
			e.preventDefault();
			if (!jQuery(this).hasClass('clickfail')) {
				if (isMobile) WH.ArticleDisplayWidget.onBeginArticleChange();
				downVotes = downVotes + voteMultiplier;
				action = "bad";
				showVoteCompletion();
				$.post(toolURL, {
					bad: true,
					pageId: pageId,
					thumbUrl: thumbUrl,
					articleTitle: articleTitle,
					pqu_id: pqu_id
					},
					function (result) {
						debugResult(result);
						incrementStats();
						if (isMobile) next();
					},
					'json'
				);
			}
		});

		$(document).on( "click", "#uci_good", function(e) {
			e.preventDefault();
			if (!jQuery(this).hasClass('clickfail')) {
				if (isMobile) WH.ArticleDisplayWidget.onBeginArticleChange();
				upVotes = upVotes + voteMultiplier;
				action = "good";
				showVoteCompletion();
				$.post(toolURL, {
					good: true,
					pageId: pageId,
					thumbUrl: thumbUrl,
					articleTitle: articleTitle,
					pqu_id: pqu_id
					},
					function (result) {
						debugResult(result);
						incrementStats();
						if (isMobile) next();
					},
					'json'
				);
			}
		});

		function skip() {
			$.post(toolURL, {
				skip: true,
				thumbUrl: thumbUrl,
				pageId: pageId,
				pqu_id: pqu_id
				},
				function (result) {
					debugResult(result);
					next();
				},
				'json'
			);
		}

		function next() {
			$.post(toolURL, {
				next: true,
				thumbUrl: thumbUrl,
				pageId: pageId
				},
				function (result) {
					loadResult(result);
				},
				'json'
			);
		}

		$(document).on( "click", "#uci_errortest", function(e) {
			e.preventDefault();
			$("#uci").hide();
			$(".tool_count").hide();
			$.post(toolURL, {
				error: true,
				pageId: pageId,
				articleTitle: articleTitle
				},
				function (result) {
					loadResult(result);
					incrementStats();
				},
				'json'
			);
		});

		$(document).on( "click", "#uci_resetskip", function(e) {
			e.preventDefault();
			$.post(toolURL, {
				resetskip: true,
				pageId: pageId,
				thumbUrl: thumbUrl
				},
				function (result) {
					loadResult(result);
					incrementStats();
				},
				'json'
			);
		});

		$(document).on( "click", "#uci_undo", function(e) {
			e.preventDefault();

			if (!jQuery(this).hasClass('clickfail')) {
				$("#uci_votecomplete").hide();
				$("#uci_complete_buttons").hide();
				$("#uci_patrol_buttons").show();
				$("#uci_img_wrap").css({"margin-top":"0"});
				$("#uci_header").css({"margin-bottom":""});

				if (action == "good") {
					upVotes = upVotes - voteMultiplier;
				} else {
					downVotes = downVotes - voteMultiplier;
				}

				clearVoters();

				$.post(toolURL, {
					undo: true,
					pageId: pageId,
					thumbUrl: thumbUrl,
					articleTitle: articleTitle,
					action: action
					},
					function (result) {
						debugResult(result);
					},
					'json'
				);
			}
		});

		$(document).on( "click", "#uci_confirm", function(e) {
			e.preventDefault();
			if (!jQuery(this).hasClass('clickfail')) {
				clearTool();
				next();
			}
		});

		$(document).on( "click", "#uci_skip", function(e) {
			e.preventDefault();
			if (!jQuery(this).hasClass('clickfail')) {
				if (isMobile) WH.ArticleDisplayWidget.onBeginArticleChange();
				(isMobile) ? showMobileInterstitial() : clearTool();
				skip();
			}
		});

		function updateWidgetTimer() {
			WH.updateTimer('stup');
			window.setTimeout(updateWidgetTimer, 60*1000);
		}

		function debugResult(result) {
			// adds debugging log data to the debug console if exists
			if (WH.consoleDebug) {
				WH.consoleDebug(result['debug']);
			}
		}

		function loadResult(result) {
			debugResult(result);
			
			$('body').data({
				event_type: 'picture_patrol',
				article_id: result.pageId,
				label: (result.pqu_id != null) ? 'plant' : '',
				assoc_id: (result.pqu_id != null) ? result.pqu_id : ''
			});

			thumbUrl = result['thumb_url'];
			pageId = result['pageId'];
			pqu_id = result['pqu_id'];

			if (result['error']) {
				if (!isMobile) {
					$("#uci").hide();
					$(".tool_count").hide();
				}

				// in the case the user already voted on this..just skip it
				if (result['error'] == 'alreadyvoted') {
					skip();
					return;
				}

				if (result['error'] == 'notitle' || result['error'] == 'norevision') {
					$("#uci_error").show();
					return;
				}

				$.post(toolURL, {
					error: true,
					pageId: result['pageId'],
					articleTitle: result['articleTitle']
					},
					function (r) {
						loadResult(r);
						incrementStats();
					},
					'json'
				);

				return;
			}
			upVotes = parseInt(result['upvotes']);
			downVotes = parseInt(result['downvotes']);
			imageWidth = result['width'];
			imageHeight = result['height'];
			articleTitle = result['articleTitle'];
			articleId = result['articleId'];
			articleDisplayTitle = result['articleDisplayTitle'];
			articleURL = result['articleURL'];
			article = result['article'];
			voteMultiplier = result['vote_mult'];
			requiredDownvotes = result['required_downvotes'];
			requiredUpvotes = result['required_upvotes'];
			requiredDownvotes = result['required_downvotes'];
			userVoter = result['user_voter'];
			voters = result['voters'];

			nextImageData = result;

			setCount(result['uciCount']);
			showNext();
		}

		function showVoteCompletion() {
			//mobile just grabs the next one
			if (isMobile) {
				showMobileInterstitial();
				return;
			}

			$("#uci_votecomplete").show();
			$("#uci_complete_buttons").show();
			$("#uci_patrol_buttons").hide();

			if (imageWidth < $("#uci_header").width()) {
				$("#uci_img_wrap").insertAfter($("#uci_votecomplete"));
				$("#uci_img_wrap").css({"margin-top":"69px"});
			} else {
				$("#uci_img_wrap").insertBefore($("#uci_votecomplete"));
				$("#uci_img_wrap").css({"margin-top":"0"});
			}

			addVoters(voters);

			var showGreenBoxes = true;

			if (action == "bad") {
				if (downVotes >= requiredDownvotes) {
					showGreenBoxes = false;
					$("#uci_complete_message").html("That's it. It's out!");
				} else {
					$("#uci_complete_message").html("Thanks for your vote!");
				}

				$("#uci_complete_message").css('color', 'darkred');
				$("#uci_user_vote").addClass("uci_vote_no");
				$("#uci_user_vote").removeClass("uci_vote_yes");
				$("#uci_complete_sub").html("Click Next to continue.");

			} else {
				$("#uci_user_vote").addClass("uci_vote_yes");
				$("#uci_user_vote").removeClass("uci_vote_no");
				$("#uci_complete_message").css('color', '#363');
				$("#uci_complete_sub").html("Click Next to continue.");

				if (upVotes >= requiredUpvotes) {
					$("#uci_complete_message").html("Fantastic. It's In!");
				} else {
					$("#uci_complete_message").html("Thanks for your vote!");
				}

			}

			// now fill up the blanks to display the "missing" divs
			if (showGreenBoxes == true) {
				for (var i = 0; i < requiredUpvotes - upVotes; i++) {
					$("#uci_voter_wrapper").append('<div class="uci_voter uci_voter_placeholder"></div>');
				}
			}
		}
		
		function showMobileInterstitial() {
			$('#uci_waiting').show();
			$('h1.firstHeading').css('visibility','hidden');
			$('#uci_img').css('background-image', 'none');
			$('#uci_article').html('');
		}

		function showNext() {			
			if (!userVoterShown) {
				$("#user_voter").prepend(userVoter['name']);
				$("#user_voter").prepend(userVoter['image']);
				userVoterShown = true;
			}
			
			if (!articleTitle) {
				$.get('/Special:EndOfQueue?this_tool=uci', function(data) {
					if (isMobile) {
						$('#ti_icon').hide();
						$('#ti_outer_box').hide();
						$('#uci_header').hide();
						$('#uci_img_wrap').removeClass('mt_box_black').addClass('mt_box').html(data);
						WH.ArticleDisplayWidget.gone();
					}
					else {
						$("#uci_waiting").hide();
						$("#uci_article").hide();
						$("#uci_img_wrap").hide();
						$("#uci_patrol_buttons").hide();
						$(".tip_bubble").hide();
						$(".wh_block").hide();
						$("#uci_header h1").html('Picture Patrol');
						$("#uci_header h1").after(data);
					}
					
					$("#uci").show();
				});
				return;
			}

			$("#uci").show();
			$(".tool_count").show();
			$('#uci_img').css({
				height: imageHeight,
				'background-image': 'url('+thumbUrl+')',
				'max-width': imageWidth
			});
			
			$("#uci_waiting").fadeOut();
			if (isMobile) {
				WH.ArticleDisplayWidget.updateArticleId(articleId);
			} else {
				$("#uci_article").html(article);
			}

			if(isMobile) {
				$('.mt_prompt_article_title').html(mw.message('howto', articleTitle).text());
			}

			if($("h1.firstHeading").length) {
				$("h1.firstHeading").html($("<a/>").attr('href',articleURL).attr('target', '_blank').text(articleTitle))
					.css('visibility','visible');
			}

			if (typeof mw.mobileFrontend !== 'undefined') {
				mw.mobileFrontend.emit('page-loaded');
			}

			if (!isMobile && imageWidth < $("#uci_header").width()) {
				$("#uci_img_wrap").insertAfter($("#uci_patrol_buttons"));
				$("#uci_patrol_buttons").css({position:"relative", top:"10px"});
			} else {
				$("#uci_img_wrap").insertBefore($("#adw_root"));
			}

			$("#uci_patrol_buttons a").removeClass("clickfail");

			// this will trigger any wikivideos
			$(document).trigger('rcdataloaded');
		}

		function clearVoters() {
			// remove the non user voters
			$(".uci_voter").each(function() {
				if ($(this).attr('id') != "user_voter") {
					$(this).remove();
				}
			});
		}

		function clearEmptyVoters() {
			// remove all non user or real voters
			$(".uci_voter").each(function() {
				if ($(this).attr('id') != "user_voter" && !$(this).hasClass("voter_real")) {
					$(this).remove();
				}
			});
		}

		function addVoters(voters) {
			clearEmptyVoters();

			var yesVote = '<div class="uci_vote_yes"><div>';
			var adminYesVote = '<div class="admin_vote_yes"><div>';
			var noVote = '<div class="uci_vote_no"><div>';
			var clear = '<div class="clearall"></div>';

			yesVotes = 0;
			noVotes = 0;

			for (var i = 0; i < voters.length; i++) {
				if (i > requiredUpvotes - 2) {
					break;
				}
				var voterImage = voters[i]['image'];
				var voterName = voters[i]['name'];
				var admin_voter = voters[i]['admin_vote'];

				var yesDiv = '<div class="uci_voter voter_real">'+voterImage+voterName+clear+yesVote+'</div>';
				var noDiv = '<div class="uci_voter voter_real">'+voterImage+voterName+clear+noVote+'</div>';

				var vote = voters[i]['vote'];
				if (vote == 0) {
					continue;
				} else if (vote > 0) {
					yesVotes = yesVotes + 1;
					if (admin_voter) {
						yesVotes = yesVotes + 1;
						$("#uci_voter_wrapper").append(yesDiv);
					} else {
						$("#uci_voter_wrapper").append(yesDiv);
					}
				} else {
					noVotes = noVotes + 1;
					if (admin_voter) {
						noVotes = yesVotes + 1;
					}
					$("#uci_voter_wrapper").append(noDiv);
				}
			}

			$("#uci_voter_wrapper").append($("#user_voter"));
			if (!isMobile) {
				$('#uci_voters').css({'margin-top':imageHeight / 4 - 10});
			}
		}

		function setCount(count) {
			$("#uci_count_val").fadeOut(400, function() {
				$("#uci_count_val").html(count).fadeIn();
			});
		}

		function clearTool() {
			$('#uci_img').css('background-image', 'none');
			$("#uci_patrol_buttons a").addClass("clickfail");
			$("#uci_waiting").show();
			$("#uci_article").html("");
			$("h1.firstHeading").css('visibility','hidden');
			$("#uci_votecomplete").hide();
			$("#uci_complete_buttons").hide();
			$("#uci_patrol_buttons").show();
			$("#uci_img_wrap").css({"margin-top":"0"});
			$("#uci_header").css({"margin-bottom":""});
			$(".special_title").html('Picture Patrol');

			clearVoters();
		}

		// currently not used because there is no way to delete log messages and therefore the stats never go down
		function decrementStats() {
			updateStats(-1);
		}

		function incrementStats(amount) {
			updateStats(1);
		}
		function updateStats(amount) {
			var statboxes = '#iia_stats_today_ucitool_indiv1,#iia_stats_week_ucitool_indiv1,#iia_stats_all_ucitool_indiv1,#iia_stats_group';
			$(statboxes).each(function(index, elem) {
				$(this).fadeOut(function () {
					var cur = parseInt($(this).html());
					$(this).html(cur + amount);
					$(this).fadeIn();
				});
			});
		}

		function initToolTitle() {
			if ( $(".firstHeading").length) {
				$(".firstHeading").before("<h5>" + $(".firstHeading").html() + "</h5>")
			}
		}

		function initMousetrap() {
			if (typeof Mousetrap !== 'undefined') {
				var mod = Mousetrap.defaultModifierKeys;
				Mousetrap.bind(mod + 's', function() {$('#uci_skip').click();});
				Mousetrap.bind(mod + 'p', function() {$('#uci_good').click();});
				Mousetrap.bind(mod + 'd', function() {$('#uci_bad').click();});
				Mousetrap.bind(mod + 'u', function() {$('#uci_undo').click();});
				Mousetrap.bind(mod + 'n', function() {$('#uci_confirm').click();});
			}
		}

		return {
			init : function() {
				if (typeof mw.mobileFrontend !== 'undefined') {
					isMobile = true;
					WH.ArticleDisplayWidget.init();
				}
				
				if (!isMobile) initToolTitle();
				initMousetrap();

				$("#uci_keys").click(function(e) {
					e.preventDefault();
					$("#uci_info").dialog({
						width: 500,
						minHeight: 300,
						modal: true,
						title: 'Picture Patrol Keys',
						closeText: 'Close',
						position: 'center'
					});
				});

				$("#article").prepend("<div id='uci_count' class='tool_count'><h3 id='uci_count_val'></h3></div>");

				next();

				if ($('#iia_standings_table').length) {
					window.setTimeout(self.updateStandingsTable, 100);
				}
				if ($('#iia_stats').length) {
					window.setTimeout(updateWidgetTimer, 60*1000);
				}
			},

			updateStandingsTable : function() {
				var url = '/Special:Standings/UCIPatrolStandingsGroup';
				$.get(url, function (data) {
					$('#iia_standings_table').html(data['html']);
				}, 'json');
				$("#stup").html(LEADERBOARD_REFRESH / 60);
				window.setTimeout(self.updateStandingsTable, 1000 * LEADERBOARD_REFRESH);
			}
		};

	}());

	$( function() {
		WH.uciPatrol.init();
	});

}( mediaWiki, jQuery ) );

