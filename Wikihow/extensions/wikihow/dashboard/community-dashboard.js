if (typeof console == 'undefined') console = {};
if (typeof console.log == 'undefined') console.log = {};

if (typeof WH == 'undefined') WH = {};

WH.dashboard = (function ($) {

	// constants
	var REFRESH_GLOBAL_DATA = 15, // default, while there's user activity
		REFRESH_GLOBAL_DATA_THROTTLE_SECS_1 = 30,
		REFRESH_GLOBAL_DATA_THROTTLE_AFTER_1 = 300,
		REFRESH_GLOBAL_DATA_THROTTLE_SECS_2 = 300,
		REFRESH_GLOBAL_DATA_THROTTLE_AFTER_2 = 1800,
		REFRESH_USER_DATA = 180,
		REFRESH_USER_DATA_THROTTLE_SECS_1 = 300,
		REFRESH_USER_DATA_THROTTLE_AFTER_1 = 600,
		REFRESH_USER_DATA_THROTTLE_SECS_2 = 900,
		REFRESH_USER_DATA_THROTTLE_AFTER_2 = 1800;
	//var CDN_BASE = wgCDNbase,
	var CDN_BASE = '//www.wikihow.com',
		MAX_INT = 2147483647; // 2^31 - 1

	// module (closure) variables
	var appThresholds = {},
		isPaused = false,
		globalTimer = null,
		userTimer = null,
		apps = {},
		lastActivity = 1000.0 * MAX_INT,
		lastNetworkUser = 0,
		lastNetworkGlobal = 0;

	// called on DOM ready event
	function init() {
		if (wgServer.indexOf('www.wikihow.com') == -1) {
			CDN_BASE = '';
		}

		listenAllActivity();

		addNetworkListeners();
		addUIListeners();

		if (!WH.isMobileDomain) startTimers();

		initApps();

		/*$('.comdash-more').click(function(){
			gatTrack('comm_engagement', 'view_leaderboard', 'dashboard');
		});

		$('#comdash-header-customize').click(function(){
			gatTrack('comm_engagement', 'view_settings', 'dashboard');
		});

		$('.cd-user-more').click(function(){
			gatTrack('comm_engagement', 'view_contribs', 'dashboard');
		});

		$('.comdash-topcontributor a').click(function(){
			gatTrack('comm_engagement', 'view_leader', 'dashboard');
		});

		$('.comdash-lastcontributor a').click(function(){
			gatTrack('comm_engagement', 'view_last', 'dashboard');
		});*/

		$(".comdash-more").click(function(){
			id = $(this).attr("id");
			widgetId = id.substring(13); //13 = comdash-more-

			$(".comdash-widget-" + widgetId + " .comdash-topcontributor .content").hide();
			$(".comdash-widget-" + widgetId + " .comdash-topcontributor .waiting").show();

			$.ajax({
				url: '/Special:CommunityDashboard/leaderboard?widget=' + widgetId,
				dataType: 'json',
				success: function(data) {
					json = $.parseJSON(data);

					leader = $(".comdash-widget-" + widgetId + " .comdash-widget-leaders");
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-body table").html(json.leaderboard);

					//temp hack b/c you can't get the height of an hidden element
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").css("visibility", "hidden");
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").show();
					height = $(leader).height() - 10; //not sure why we need 5, but it doesn't match up without
					position = $(leader).position();
					newTop = position.top - height;

					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").hide();
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").css("visibility", "visible");

					$(leader).animate({"top": newTop+"px"}, "slow");

					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders-content").slideToggle("slow");

					$(".comdash-widget-" + widgetId + " .comdash-topcontributor .content").show();
					$(".comdash-widget-" + widgetId + " .comdash-topcontributor .waiting").hide();
				}
			});

			return false;
		});

		$(".comdash-widget-leaders .comdash-close").click(function(){
			id = $(this).attr("id");
			widgetId = id.substring(14); //14 = comdash-close-

			$(".comdash-widget-" + widgetId + " .comdash-widget-leaders").animate({"top": "190px"}, "slow");
			$(".comdash-widget-" + widgetId + " .comdash-widget-leaders-content").slideToggle("slow");

			return false;
		});

		$("#comdash-header-customize").click(function(){
			$('.comdash-settings').click();
			return false;
		});

		$("#cd-user-more").click(function(){
			$("#cd-user-box").html("<center><img src='/extensions/wikihow/rotate.gif'/></center>");
			$("#cd-user-box").dialog({
				width: 500,
				minHeight: 500,
				modal: true,
				title: 'Your Contributions This Week',
				closeText: 'x',
			});
			$.ajax({
				url: '/Special:CommunityDashboard/userstats',
				dataType: 'json',
				success: function(data) {
					//alert(data);
					json = $.parseJSON(data);
					html = "<table><tr><th class='cd-user-start'></th><th class='cd-user-widgetname'></th><th>You</th><th class='cd-user-average'>Avg wikiHow Contributor</th></tr>";
					$.each(data, function(i, item){
						html += "<tr>";
						html += "<td class='cd-user-start'>" + item.start + "</td>";
						html += "<td class='cd-user-widgetname'><span>" + item.usercd + "</span></td>";
						html += "<td class='cd-user-you'><span>" + item.usercount + "</span></td>";
						html += "<td class='cd-user-average'>" + item.averagecount + "</td>";
						html += "</tr>";
					});
					html += "</table>";
					$("#cd-user-box").html(html);
				}
			});
			return false;
		});

		$(".comdash-widget-box.disabled .status").hover(function(){
			id = $(this).attr("id");
			widgetId = id.substring(7); //7 = status-
			$(".comdash-widget-"+widgetId + " .cd-info").show();
		}, function(){
			id = $(this).attr("id");
			widgetId = id.substring(7); //7 = status-
			$(".comdash-widget-"+widgetId + " .cd-info").hide();
		});

		$(".cd-info").hover(function(){
			$(this).show();
		}, function(){
			$(this).hide();
		});

	}

	/**
	 * Listen for all browser activity and populate the lastActivity variable.
	 */
	function listenAllActivity() {
		$(document).bind('mousemove mousedown keydown scroll', function(evt) {
			lastActivity = evt.timeStamp ? evt.timeStamp : new Date().getTime();
		});
	}

	/**
	 * Utility function to return the time in seconds instead of milliseconds.
	 */
	function timeInSecs(time) {
		return Math.round(time / 1000.0);
	}

	// To be called by all subclasses of WH.DashboardWidget so that they
	// can hook into the data feed from the site.
	//
	// Note: it's not necessary to call init() before this method (by design).
	function registerDataListener(app, instance) {
		apps[app] = instance;
	}

	// Start the timers which ping the server for data and reload the page
	// after a day
	function startTimers() {
		globalTimer = setInterval(onGlobalTimer, refreshData('global') * 1000);
		userTimer = setInterval(onUserTimer, refreshData('user') * 1000);

		// run this callback after a day of being on the page
		setTimeout(function () {
			// reload page
			window.location.href = window.location.href;
		}, 24 * 60 * 60 * 1000);
	}

	// Display and hide a notification based on AJAX activity
	function addNetworkListeners() {
		$('.cd-network-loading').bind({
		    ajaxStart: function() { $(this).show(); },
			ajaxStop: function() { $(this).fadeOut('slow'); }
		});
	}

	// Listen for actions on DOM nodes relating to UI stuff
	function addUIListeners() {
		$('.comdash-pause').click(function () {
			if (!paused()) {
				$(this).html(wfMsg('cd-resume-updates'));
				paused(true);
			} else {
				$(this).html(wfMsg('cd-pause-updates'));
				paused(false);
			}
			return false;
		});

		$('.comdash-settings').click(function () {
			var wasPaused = isPaused;
			paused(true);

			var priorities = {};
			$(WH.dashboard.priorityWidgets).each(function () {
				priorities[this] = true;
			});

			$('.cd-customize-sortable li').remove();
			$(WH.dashboard.prefsOrdering).each(function () {
				var tmpl = '<li class="cd-customize-li"><span class="cust-name">$3</span> $4<span class="cust-widget">$1</span><input type="checkbox"$2 /></li>';
				var checked = this['show'] ? ' checked="yes"' : '';
				var title = WH.dashboard.widgetTitles[ this['wid'] ];
				if (!title) return; //invalid widget
				var priority = priorities[ this['wid'] ] ? wfMsg('cd-current-priority') : '';
				var node = wfTemplate(tmpl, this['wid'], checked, title, priority);
				$('.cd-customize-sortable').append(node)
			});
			$('.cd-customize-sortable')
				.sortable()
				.disableSelection();
			$('.cd-customize-dialog').dialog({
                width: 520,
                modal: true,
				closeText: 'x'
            });

			return false;
		});

		$('.cd-customize-cancel').click(function () {
			$('.cd-customize-dialog').dialog('close');
			return false;
		});

		$('.cd-customize-save').click(function () {
			// serialize ordering
			var ordering = [];
			$('.cd-customize-sortable li').each(function () {
				var widget = $('.cust-widget', this).text();
				var checked = $('input:checked', this).length;
				ordering.push({'wid': widget, 'show': checked});
			});

			// save ordering locally and to server
			WH.dashboard.prefsOrdering = ordering;
			$.post('/Special:CommunityDashboard/customize',
				{ ordering: JSON.stringify(ordering) },
				function (data) {
					if (data && !data['error']) {
						// reload this page to reload widget order
						window.location.href = window.location.href;
					} else {
						var error = '';
						if (!data) error = wfMsg('cd-network-error');
						else error = data['error'];
						// TODO: display error in a dialog
						//console.log('error', error);
					}
				},
				'json');

			$('.cd-customize-dialog').dialog('close');
			return false;
		});
	}

	// Get around the same origin problem by creating a <script> elements to
	// grab the data off a different domain and setting a callback (JSONP).
	function loadData(type, callbackFunc) {
		var REFRESH_URL = '/Special:CommunityDashboard/$1?function=$2&$3';
		if (!paused()) {
			var action = type == 'global' ? 'refresh' : 'userrefresh';
			var url = CDN_BASE + wfTemplate(REFRESH_URL, action, callbackFunc, wgWikihowSiteRev);
			var node = $('<script src="' + url + '"></script>');
			$('body').append(node);
		}
	}

	// The setInterval callback to fetch new global data once in a while
	function onGlobalTimer() {
		var now = new Date().getTime();
		var userActivityDelta = timeInSecs( now - lastActivity );
		var netActivityDelta = timeInSecs( now - lastNetworkGlobal );

		if ((userActivityDelta > REFRESH_GLOBAL_DATA_THROTTLE_AFTER_2 &&
			 netActivityDelta < REFRESH_GLOBAL_DATA_THROTTLE_SECS_2) ||
			(userActivityDelta > REFRESH_GLOBAL_DATA_THROTTLE_AFTER_1 &&
			 netActivityDelta < REFRESH_GLOBAL_DATA_THROTTLE_SECS_1))
		{
			//console.log('GLOBAL ignore net ping', userActivityDelta, netActivityDelta);
			return;
		}

		lastNetworkGlobal = now;
		loadData('global', 'WH.dashboard.globalDataCallback');
	}

	// The setInterval callback to fetch new user data once in a while
	function onUserTimer() {
		var now = new Date().getTime();
		var userActivityDelta = timeInSecs( now - lastActivity );
		var netActivityDelta = timeInSecs( now - lastNetworkUser );

		if ((userActivityDelta > REFRESH_USER_DATA_THROTTLE_AFTER_2 &&
			 netActivityDelta < REFRESH_USER_DATA_THROTTLE_SECS_2) ||
			(userActivityDelta > REFRESH_USER_DATA_THROTTLE_AFTER_1 &&
			 netActivityDelta < REFRESH_USER_DATA_THROTTLE_SECS_1))
		{
			//console.log('USER ignore net ping', userActivityDelta, netActivityDelta);
			return;
		}

		lastNetworkUser = now;
		loadData('user', 'WH.dashboard.userDataCallback');
	}

	// Gets/sets to control whether updating the widgets is paused
	function paused(_isPaused) {
		if (typeof _isPaused != 'undefined') {
			isPaused = _isPaused;
		}
		return isPaused;
	}

	// Gets/sets the thresholds for all apps
	function allThresholds(_thresholds) {
		if (typeof appThresholds != 'undefined') {
			appThresholds = _thresholds;
		}
		return appThresholds;
	}

	// Gets/sets the thresholds for a particular app
	function thresholds(app, _thresholds) {
		if (appThresholds[app]) {
			if (typeof _thresholds != 'undefined') {
				appThresholds[app] = _thresholds;
			}
			return appThresholds[app];
		} else {
			return {};
		}
	}

	// Gets/sets the update (via server call) interval
	//
	// Note: these settings must be made before init() is called
	function refreshData(type, secs) {
		if (type == 'user') {
			if (typeof secs != 'undefined') {
				REFRESH_USER_DATA = secs;
			}
			return REFRESH_USER_DATA;
		} else if (type == 'global') {
			if (typeof secs != 'undefined') {
				REFRESH_GLOBAL_DATA = secs;
			}
			return REFRESH_GLOBAL_DATA;
		} else {
			console.log('refreshData: unknown type');
		}
	}

	// ping each of the registered app with their data
	function sendDataToApps(type, data) {
		var codes = WH.dashboard.appShortCodes;
		$.each(apps, function(name, app) {
			if (typeof app.listenData == 'function'
				&& typeof codes[name] != 'undefined')
			{
				var code = codes[name];
				var appData = null;
				if (type == 'global') {
					if (data && data['widgets'] && data['widgets'][code]) {
						appData = data['widgets'][code];
					}
				} else {
					if (data && data['completion'] && data['completion'][code]) {
						appData = data["completion"][code];
					}
				}
				if (appData !== null) {
					if(type == 'global'){
						$(".comdash-widget-" + name + " .comdash-count .cd-error").hide();
						$(".comdash-widget-" + name + " .comdash-count .cd-count-div").show();
					}
					app.listenData(type, appData);
				}
			}
		});
	}

	// call init on each app, if it exists
	function initApps() {
		$.each(apps, function(name, app) {
			if (typeof app.init == 'function') {
				app.init();
			}
		});
	}

	// This callback is made after the data is loaded by the <script> tag.
	// It only needs to be public because of the way JSONP happens.
	function userDataCallback(data) {
		//first use non-app-specific data

		//user counts
		//these are at the top of the page next to the users
		//avatar
		$.each(data.counts, function(name, value){
			$("#header-" + name).html(value);
		});

		sendDataToApps('user', data);
	}

	// This callback is made after the data is loaded by the <script> tag.
	// It only needs to be public because of the way JSONP happens.
	function globalDataCallback(data) {
		sendDataToApps('global', data);
	}

	function animateUpdateImage(oldImage, newImageHtml) {
		var oldImageHtml = oldImage.first().html();
		var oldSrc = $(oldImageHtml).attr('src').replace(/^https?:\/\/[^\/]*/, '');
		var newSrc = $(newImageHtml).attr('src').replace(/^https?:\/\/[^\/]*/, '');
		if (newSrc != oldSrc) {
			oldImage.fadeOut('slow', function () {
				$(this).html(newImageHtml);
				$(this).fadeIn('slow');
			});
		}
	}

	// To be called by widgets to make updates more interactive
	function animateUpdate(div, newValue, widgetName) {
		var oldValue = div.html();

		if (oldValue == newValue) return;

		$(".comdash-widget-" + widgetName).addClass("active");
		var activeInterval = setInterval(function(){
			$(".comdash-widget-" + widgetName).removeClass("active");
			clearInterval(activeInterval);
		}, 3000);

		var offset = div.offset(),
			offsetTop = Math.round(offset['top']),
			offsetLeft = Math.round(offset['left']),
			offsetRight = offsetLeft + div.width(),
			offsetBottom = offsetTop + div.height();

		var containerNode = $('<div style="overflow: hidden; z-index: 10; width: 100%; float: none;"></div>'),
			contentNode = $('<div style="position: relative; z-index: 1; width: 100%; float: none;"></div>'),
			beforeNode = $('<div style="height: auto; float:none; padding: 0px;">' + oldValue + '</div>'),
			afterNode = $('<div style="float:none; padding: 0px;">' + newValue + '</div>');

		div.html(containerNode);
		containerNode.append(contentNode);
		contentNode.append(beforeNode);
		contentNode.append(afterNode);

		var height = beforeNode.height();
		containerNode.css({
			'clip': 'rect(' + offsetTop + 'px ' + offsetRight + 'px ' + offsetBottom + 'px ' + offsetLeft + 'px)',
			'height': height + 'px',
			'top': '0px'
		});

		var initial = 0,
			duration = 500,
			timeIncrement = 50,
			change = height,
			startTime = new Date().getTime();

		var quadEaseOut = function (interval, start, delta, duration) {
			var percent = interval / duration;
			return start + delta * percent * (percent - 2);
		};

		var timerID = setInterval(function () {
			var delta = new Date().getTime() - startTime,
				ypos = quadEaseOut(delta, initial, change, duration);
			if (delta >= duration) {
				div.html(newValue);
				clearInterval(timerID);
			} else {
				contentNode.css('top', Math.round(ypos) + 'px');
			}
		}, timeIncrement);

	}

	// the public interface -- only these methods can be called from outside
	// the module
	return {
		init: init,
		thresholds: thresholds,
		allThresholds: allThresholds,
		refreshData: refreshData,
		paused: paused,
		userDataCallback: userDataCallback,
		globalDataCallback: globalDataCallback,
		registerDataListener: registerDataListener,
		animateUpdate: animateUpdate,
		animateUpdateImage: animateUpdateImage
	};
})(jQuery);

