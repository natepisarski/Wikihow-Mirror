( function($, mw) {

	function WelcomeWagon() {
		var selected_tab = 'contributions';
		var toolURL = "/Special:WelcomeWagon";
		var postComment = "/Special:PostComment";
		var postCommentPreview = "/Special:PostCommentPreview";
		var tabs = ["contributions", "summary", "profile", "talkpage"];
		var messageAreaHTML;
		var userMessage = null;
		var usersCount = null;
		var LEADERBOARD_REFRESH = 10 * 60;
		var error = false;
		var topMessage = 'Welcome Wagon';

		function postLoadTab(tab) {
			if (tab == "contributions") {
				$(".mw-rollback-link").each( function (index) {
					$(this).children("a").addClass("mwrollback");
					$(this).click(function (e) {
						e.preventDefault();
						var url = $(this).children("a").attr("href");
						$.get(url, function (response) {
							//reload the tab
							var tab = selected_tab;
							clearTabContent();
							selected_tab = null;
							selectTab(tab);
						});
					});
				});
			}
		}

		function loadTab(tab, data) {
			var cacheOrigVal = $.ajaxSetup()['cache'];
			$.ajaxSetup({ cache: true });
			$('#welcome-wagon-content').append(data);
			$.ajaxSetup({ cache: cacheOrigVal });

			//make the prev next arrows open in a new page
			$("body #differences-prevlink").each(function() {
				$(this).attr("target", "new");
			});
			$("body #differences-nextlink").each(function() {
				$(this).attr("target", "new");
			});

			postLoadTab(tab);
		}

		function clearTabContent() {
			for (var i in tabs) {
				$('#content-'+tabs[i]).remove();
			}
		}

		function resetMessageArea(message) {
			$('#message-text').html(messageAreaHTML);
			$('#preview-message').text('Preview');

			$('#message-box').focus();
			if (message) {
				$('#message-box').val(message);
			} else {
				userMessage = null;
			}
		}

		function selectTab(tab) {
			if (tab == selected_tab) {
				return;
			}

			selected_tab = tab;

			for (var i in tabs) {
				if (tab == tabs[i]) {
					$('#tab-'+tabs[i]).addClass("on");
					if ($('#content-'+tabs[i]).length) {
						$('#content-'+tabs[i]).show();
					} else {
						$('#content-loading').show();
						$.post(toolURL, {
								action: 'switchTab',
								userName: userName,
								userId: userId,
								tabName: tabs[i]
							},
							function (result) { // called on success
								$('#content-loading').hide();
								loadTab(tab, result['html']);
							},
							'json')
							.fail( function(xhrResult) {
								alert('There was a problem loading the response from the server.');
								if (typeof console != 'undefined' && typeof console.log != 'undefined') {
									console.log(toolURL + ' failed! xhrResult:', xhrResult);
								}
							} );
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

		function loadUser(name, realName, id, link, article) {
			userName = name;
			userRealName = realName;
			userId = id;
			$('.firstHeading').html(link);
			if (userRealName) {
				$('#form-real-name').html(userRealName);
			} else {
				$('#form-real-name').html("them");
			}
			lastArticleLink = article;

			//reselect our current tab with the new user to reload data
			var tab = selected_tab;
			selected_tab = null;
			selectTab(tab);
		}

		function disablePage(errorMessage) {
			$('.ww_topmessage').html(errorMessage);
			$('#welcome-wagon').fadeTo(500, 0.2);
			$('#article-tabs').fadeTo(500, 0.2);
			$('#content-loading').hide();
			$('#welcome-wagon :input').attr("disabled", true);
			$('.welcome-tab').off('click');
		}

		function nextUser(currentId, skip, complete) {
			clearTabContent();
			resetMessageArea();
			$.post(toolURL, {
				action: 'nextUser',
				userId: currentId,
				skip:skip
				},
				function (result) {
					if (!result) {
						updateCount(0);
						disablePage("There are no users to welcome at the moment.  Please try again later.");
					} else {
						$('#welcome-wagon :input').attr("disabled", false);
						loadUser(result['userName'],
								result['userRealName'],
								result['userId'],
								result['userLink'],
								result['lastArticleLink']);
						updateCount(result['usersCount']);
					}

					if (complete) {
						complete();
					}
				},
				'json'
			);
		}

		function updateStats(data) {
			$('#iia_individual_table_welcomewagon_indiv1').html(data['stats']);
		}

		function logMessageSent(targetId, revId, message) {
			$.post(toolURL, {
				action: 'logMessage',
				toId: targetId,
				revId: revId,
				message: message
				},
				function (result) {
					updateStats(result);
				},
				'json'
			);
		}

		function updateCount(count) {
			if (count == null) {
				usersCount = null;
				$("#users_count").hide();
				return;
			}

			if (usersCount == null) {
				$("#users_count").show();
			}

			if (count == usersCount) {
				return;
			}
			usersCount = count;

			$("#users_count h3").fadeOut(400, function() {
				$("#users_count h3").html(count).fadeIn();
			});
		}

		updateStandingsTable = function() {
			var url = '/Special:Standings/WelcomeWagonStandingsGroup';
			$.get(url, function (data) {
				$('#iia_standings_table').html(data['html']);
			}, 'json');
			$("#stup").html(LEADERBOARD_REFRESH / 60);
			window.setTimeout(updateStandingsTable, 1000 * LEADERBOARD_REFRESH);
		}

		function showError(error) {
			$('#preview-message').text('Edit');
			userMessage = $('textarea#message-box').val();
			$('#message-text').html('<div id="ww_error"></div>');
			$('#ww_error').html(error);
		}

		function hasTemplate(message) {
			var reg = /{{.*?}}/;
			if (reg.test(message)) {
				return true;
			}
			return false;
		}

		function initHandlers() {
			$('#skip-user').click(function(e) {
				e.preventDefault();
				var skip = true;
				nextUser(userId, skip, function () {
					$('#message-box').focus();
					$('#message-box').val("");
				});
			});

			$('#send-message').click(function (e) {
				e.preventDefault();

				if (error == true) {
					return;
				}

				var message = $('textarea#message-box').val();

				// message may be hidden due to preview
				if ( message === undefined) {
					message = userMessage;
				}

				if (hasTemplate(message)) {
					error = true;
					showError('Sorry, no templates allowed.');
					return;
				}

				if (message.length < 1) {
					error = true;
					showError("please enter a message");
					return;
				}

				$('#welcome-wagon :input').attr("disabled", true);

				$.post(postComment, {
					fromajax: true,
					jsonresponse: true,
					target: 'User_talk:'+userName,
					comment_text: message
					},
					function (result) {
						logMessageSent(userId, result['revId'], message);

						//clear the text box
						$('textarea#message-box').val('');
						var skip = false;
						nextUser(userId, skip);
					}
				).fail(function(xhr) {
					showError(xhr.responseText);
				});
			});

			$('#preview-message').click(function (e) {
				e.preventDefault();
				if ($(this).text() === "Edit") {
					resetMessageArea(userMessage);
					error = false;
				} else {
					$(this).text('Edit');
					userMessage = $('textarea#message-box').val();
					if (hasTemplate(userMessage)) {
						error = true;
						showError('Sorry, no templates allowed.');
						return;
					}
					$('#message-text').html('<div class="testclass">Generating Preview...</div>');
					$.post(postCommentPreview, {
						comment: userMessage
						},
						function (result) {
							$('#message-text').html(result);
						}
					);
				}
			});

			$('#insert_last_article').click(function(e) {
				e.preventDefault();
				var input = $('#message-box').val() + lastArticleLink;
				$('#message-box').focus();
				insertAtCaret('message-box', lastArticleLink);
			});

			$('#insert_user_name').click(function(e) {
				e.preventDefault();
				var userNameLink = userName;
				$('#message-box').focus();
				insertAtCaret('message-box', userNameLink);
			});

			$('.welcome-tab').click(function(e) {
				e.preventDefault();
				selectTab($(this).attr('title'));
			});
		}

		function insertAtCaret(areaId, text) {
			var txtarea = document.getElementById(areaId);
			var scrollPos = txtarea.scrollTop;
			var strPos = 0;
			var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
				"ff" : (document.selection ? "ie" : false ) );
			if (br == "ie") {
				txtarea.focus();
				var range = document.selection.createRange();
				range.moveStart ('character', -txtarea.value.length);
				strPos = range.text.length;
			}
			else if (br == "ff") strPos = txtarea.selectionStart;

			var front = (txtarea.value).substring(0,strPos);
			var back = (txtarea.value).substring(strPos,txtarea.value.length);
			txtarea.value=front+text+back;
			strPos = strPos + text.length;
			if (br == "ie") {
				txtarea.focus();
				var range = document.selection.createRange();
				range.moveStart ('character', -txtarea.value.length);
				range.moveStart ('character', strPos);
				range.moveEnd ('character', 0);
				range.select();
			}
			else if (br == "ff") {
				txtarea.selectionStart = strPos;
				txtarea.selectionEnd = strPos;
				txtarea.focus();
			}
			txtarea.scrollTop = scrollPos;
		}

		this.init = function() {
			initHandlers();

			$("#article").prepend("<div id='users_count' class='tool_count'><h3></h3><span>users remaining</span></div>");
			window.setTimeout(updateStandingsTable, 100);
			window.setTimeout(updateWidgetTimer, 60*1000);

			messageAreaHTML = $('#message-text').html();

			$('.ww_topmessage').html(topMessage);

			if (typeof userName != 'undefined') {
				updateCount(null);
				loadUser(userName, userRealName, userId, userLink, lastArticleLink);
			}
			else {
				nextUser(0, false);
			}

			$('#message-box').focus();
		}

		function updateWidgetTimer() {
			WH.updateTimer('stup');
			window.setTimeout(updateWidgetTimer, 60*1000);
		}
	}

	$(document).ready(function() {
		var welcomeWagon = new WelcomeWagon();
		welcomeWagon.init();
		initToolTitle();
	});

}(jQuery, mediaWiki) );
