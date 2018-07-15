( function($, mw) {

var nextrev = null;
var marklink = null;
var skiplink = null;
var loaded = false;
var backsize = 20;
var backurls = new Array(backsize);
var backindex = 0;
var rev = false;
var ns = -1;
var rc_user_filter = "";
var ignore_rcid = 0;

// refresh the leaderboard every n seconds
var RC_WIDGET_LEADERBOARD_REFRESH = 10 * 60;
var RC_WIDGET_USERSTATS_REFESH = 5 * 60;

var search = window.location.search.replace(/^\?/, "");
var parts = search.split("&");
for (i = 0; i < parts.length; i++) {
	var term = parts[i];
	var keyterm = term.split("=");
	if (keyterm.length == 2 && keyterm[0] == 'rc_user_filter') {
		rc_user_filter = keyterm[1];
	}
}

var rollbackUrl = '', readyForRollback = false;
//var previousHTML = 'none';

// Init shortcut key bindings
function initKeyBindings() {
	initToolTitle(); // from wikihowbits.js

	var title = $('#articletitle').html();
	if (!title) return;
	$(".firstHeading").html(title);

	var mod = Mousetrap.defaultModifierKeys;

	Mousetrap.bind(mod + 'm', function() {$('#markpatrolurl').click();});
	Mousetrap.bind(mod + 's', function() {$('#skippatrolurl').click();});
	Mousetrap.bind(mod + 'e', function() {$('#qe_button').click();});
	Mousetrap.bind(mod + 'r', function() {$('#rb_button').click();});
	Mousetrap.bind(mod + 'b', function() {$('#gb_button').click();});
	Mousetrap.bind(mod + 't', function() {$('.thumbbutton').click();});
	Mousetrap.bind(mod + 'q', function() {$('#qn_button').click();});

	$(document).bind('rcdataloaded', function () {
		setupTracking();
		WH.showEmbedVideos();
	});

	setupTracking();
}

function setupTracking() {
	$('body').data({
		event_type: 'rc_patrol'
	});

	$('#markpatrolurl').addClass('op-action');
	$('#rb_button').addClass('op-action');
	$('#qe_button').addClass('op-action');
	$('#skippatrolurl').addClass('op-action');
}

function setRCLinks() {
	var e = document.getElementById('bodycontents2');
	var links = e.getElementsByTagName("a");
	for (i = 0; i < links.length; i++) {
		if (links[i].href != wgServer + "/" + wgPageName) {
			links[i].setAttribute('target','new');
		}
	}

	if ($('#numrcusers') && $('#numrcusers').html() != "1") {
		e = $("#mw-diff-ntitle2 #mw-diff-oinfo");
		var ehtml = e.html();
		if (ehtml && ehtml.indexOf("and others") < 0) {
			$( "#mw-diff-ntitle2 #mw-diff-oinfo #mw-diff-ndaysago" ).before( "<b>and others</b>." );
		}
	}

	$('.button').each( function() {
		if ($(this).html() == "quick edit") {
			$(this).click(function () {
				hookSaveButton();
			});
			return;
		}
	});
}

function parseIntWH(num) {
	if (!num) {
		return 0;
	}
	return parseInt(num.replace(/,/, ''), 10);
}

function addCommas(nStr) {
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

function incQuickEditCount() {
	// increment the active widget
	$("#iia_stats_group, #iia_stats_today_rc_quick_edits, #iia_stats_week_rc_quick_edits").each(function() {
		$(this).fadeOut();
		var cur = parseIntWH($(this).html());
		$(this).html(addCommas(cur + 1));
		$(this).fadeIn();
	});
}

function hookSaveButton() {
	if ( ! $("#wpSave").html() ) {
		setTimeout(hookSaveButton, 200);
		return;
	}
	$("#wpSave").click(function() {
		incQuickEditCount();
	});
}

function setContentInner(html, fade) {
	$("#bodycontents2").html(html);
	if (fade) {
		$("#bodycontents2").fadeIn(300);
	} else {
		$("#bodycontents2").show();
	}

	var title = $('#articletitle').html();
	if (!title) return;
	$(".firstHeading").html(title);
	//$('h1').first().html(title);
	//document.title = title;

	var matches = html.match(/<div id='newrollbackurl'[^<]*<\/div>/);
	if (matches) {
		newlink = matches[0];
		rollbackUrl = newlink.replace(/<(?:.|\s)*?>/g, "");
	}
	setRCLinks();
	addBackLink();
	if (rev) {
		$("#reverse").prop('checked', true);
	}
	$("#namespace").val(ns);
	$("#rc_user_filter").val(rc_user_filter);
	if (rc_user_filter) openSubMenu('user');
	if (rev || ns >= 0) openSubMenu('ordering');

	// Fire event to initialize youtube embeds
	$(document).trigger('rcdataloaded');
}

function setContent(html) {
	var e = document.getElementById('bodycontents2');
	if (navigator.appVersion.indexOf("MSIE") >= 0) {
		$("#bodycontents2").hide(300, function() {
			setContentInner(html, false);
		});
	} else {
		$("#bodycontents2").fadeOut(300, function() {
			setContentInner(html, true);
		});
	}
	return;
}

function resetRCLinks() {
	var matches = nextrev.match(/<div id='skiptitle'[^<]*<\/div>/);
	if (!matches || matches.length === 0) {
		return;
	}
	var newlink = matches[0];
	var skiptitle = "&skiptitle=" + newlink.replace(/<(?:.|\s)*?>/g, "");

	/// set the mark link to the current contents
	if (navigator.userAgent.indexOf('MSIE') > 0) {
		marklink = document.getElementById('newlinkpatrol').innerText + skiptitle;
		skiplink = document.getElementById('newlinkskip').innerText + skiptitle;
	} else {
		marklink = document.getElementById('newlinkpatrol').textContent + skiptitle;
		skiplink = document.getElementById('newlinkskip').textContent + skiptitle;
	}
}

function setupTabs() {
	$('#rctab_advanced a').click(function() {
		openSubMenu('advanced');
		return false;
	});
	$('#rctab_ordering a').click(function() {
		openSubMenu('ordering');
		return false;
	});
	$('#rctab_user a').click(function() {
		openSubMenu('user');
		return false;
	});
	$('#rctab_help a').click(function() {
		openSubMenu('help');
		return false;
	});

	$("#rcpatrol_keys").on("click", function(e) {
		e.preventDefault();
		$("#rcpatrol_info").dialog({
			width: 500,
			minHeight: 300,
			modal: true,
			title: 'RCPatrol Keys',
			closeText: 'x',
			position: 'center',
		});
	});
}

function skip() {
	if (!loaded) {
		setTimeout(skip, 500);
		return;
	}

	sendMarkPatrolled(skiplink);
	resetQuickNoteLinks();
	return false;
}

function resetQuickNoteLinks() {
	$.get(
		"/Special:QuickNoteEdit/quicknotebuttons",
		function (response, xhr) {
			$('#qnote_buttons').html(response);
		}
	);
}

function changeReverse() {
	var tmp = $("input[name='reverse']:checked").val();
	ignore_rcid = 2;
	if (tmp == 1) {
		rev = true;
	} else {
		rev = false;
	}
	nextrev = null;
}

function changeUserFilter() {
	rc_user_filter = $("#rc_user_filter").val();
}

function changeUser(user) {
	var url = '/Special:RCPatrol';
	if (user) {
		url += '?rc_user_filter=' + encodeURIComponent( $('#rc_user_filter').val() );
	}
	window.location.href = url;
}

function modUrl(url) {
	url = url.replace(/reverse=[0-9]?/, "&");

	// If it's a test, let the special page know
	var RCTestObj = RCTestObj ||  null;
	if (RCTestObj) {
		url += "&rctest=1";
	}
	// If we're debugging, let the special page know
	var mode = extractParamFromUri(document.location.search, 'rct_mode');
	if (mode) {
		url += "&rct_mode=" + mode;
	}

	if (rev) {
		url += "&reverse=1";
	}
	if (ns >= 0) {
		url += "&namespace=" + ns;
	}
	if (ignore_rcid > 0) {
		url += "&ignore_rcid=1";
		ignore_rcid--;
	}
	url += "&rc_user_filter=" + encodeURIComponent(rc_user_filter);
	return url;
}

function loadData(url) {
	url = modUrl(url);
	loaded = false;
	$.ajax({
		url: url,
		success: function(data) {
			setContent(data.html);
		},
		error: function(jqxhr, textStatus, errorThrown) {
			setContent('There was a problem. Status: ' + textStatus + ', error: ' + errorThrown);
		},
		dataType: 'json'
	});
	return false;
}

function setUnpatrolledTitleNumber(count) {
	$("#rcpatrolcount h3").fadeOut(400, function() {
		$("#rcpatrolcount h3").html(count)
			.fadeIn();
	});
}

function setPreloadedFromErrorCallback(jqxhr, textStatus, errorThrown) {
	var data = {err: 'Status: ' + textStatus + ', reported error: ' + errorThrown}
	setPreloaded(data);
}

function setPreloaded(data) {
	// If there was an error reported by RCP on server side, give an option to view it
	if (!data || !data.html || data.err) {
		console.log('setPreloaded detected RCP error', data);
		var msg = data ? JSON.stringify(data) : '<no available data>';
		$('.rcp_err_dump').text( msg );
		$('.rcp_err').show();
		loaded = true;
		return;
	}

	nextrev = data.html;
	resetRCLinks();
	loaded = true;
	setUnpatrolledTitleNumber(data.unpatrolled);
}

function sendMarkPatrolled(url) {
	url = modUrl(url);
	if (nextrev) {
		loaded = false;
		$.ajax({
			url: url,
			success: setPreloaded,
			error: setPreloadedFromErrorCallback,
			dataType: 'json'
		});
		addBackLink();
		setContent(nextrev);
	} else {
		loadData(url);
	}
	return false;
}

function markPatrolled() {
	if (!loaded) {
		setTimeout(markPatrolled, 500);
		return;
	}

	var numedits = parseIntWH($('#numedits').html());
	$("#iia_stats_today_rc_edits, #iia_stats_week_rc_edits, #iia_stats_all_rc_edits").each(function(index, elem) {
		$(this).fadeOut();
		var cur = parseIntWH($(this).html());
		$(this).html(addCommas(cur + numedits));
		$(this).fadeIn();
	});

	sendMarkPatrolled(marklink);

	//change quick note links
	resetQuickNoteLinks();
	return false;
}

function preloadNext(url) {
	url = modUrl(url);
	$.ajax({
		url: url,
		success: setPreloaded,
		error: setPreloadedFromErrorCallback,
		dataType: 'json'
	});
	return false;
}

function addBackLink() {
	// If it's a test, don't add this revision to the back links
	if (WH.RCTest) {
		return;
	}
	var link = $('#permalink').val();
	backurls[backindex % backsize] = link;
	backindex++;
}

function goback() {
	if (backindex > 0) {
		backindex--;
		var index = backindex - 1;
		if (index < 0) index += backsize;
		var backlink = backurls[index % backsize];
		loadData(backlink);
	} else {
		alert('No diff to go back to, sorry!');
	}
	return false;
}

function handleQESubmit() {
	incQuickEditCount();
}

function updateWidget(id, x) {
	var url = '/Special:Standings/' + x;
	$.get(url,
		function (data) {
			$(id).fadeOut();
			$(id).html(data['html']);
			$(id).fadeIn();
		},
		'json'
	);
}

function updateLeaderboard() {
	updateWidget("#iia_standings_table", "QuickEditStandingsGroup");
	var min = RC_WIDGET_LEADERBOARD_REFRESH / 60;
	$("#stup").html(min);
	setTimeout(updateLeaderboard, 1000 * RC_WIDGET_LEADERBOARD_REFRESH);
	return false;
}

function updateTimers() {
	WH.updateTimer("stup");
	setTimeout(updateTimers, 1000 * 60);
}

function openSubMenu(menuName) {
	var menu = $("#rc_" + menuName);
	if (menu.is(":visible")) {
		menu.hide();
		$("#rctab_" + menuName).removeClass("on");
	} else {
		$(".rc_submenu").hide();
		$("#rc_help").hide();
		menu.show();
		$("#rc_subtabs div").removeClass("on");
		$("#rctab_" + menuName).addClass("on");
	}
}

function initRCPatrol() {
	if (rc_user_filter) {
		$('#rc_user_filter').val(rc_user_filter);
		openSubMenu('user');
	}

	$('.rcp_err_reload').click( function() {
		// Reload RCP if user requests it in error situation
		window.location.href = window.location.href;
		return false;
	});

	$('.rcp_err_show').click( function() {
		$('.rcp_err').css('width', '300px');
		$('.rcp_err_dump').show();
		return false;
	});

	setTimeout(updateLeaderboard, 1000 * RC_WIDGET_LEADERBOARD_REFRESH);
	setTimeout(updateTimers, 1000 * 60);

	if ($('#rcpatrolcount').length === 0) {
		$('#article').prepend('<div id="rcpatrolcount" class="tool_count"><h3></h3></div>');
	}

	$(document).on("change", "#namespace", function(){
		ns = $('#namespace').val();
		nextrev = null;
	});

	readyForRollback = true;
}

function postRollbackCallback() {
	span = $('#rollback-status');
	if (span.length && span.html().toLowerCase().indexOf('reverted edits by') >= 0) {
		// Special hack for BR because he didn't like not being able
		// to quick edit after a rollback
		//var exceptionsList = ['BR', 'JuneDays', 'Zack', 'KommaH'];
		var exceptionsList = ['JuneDays', 'Zack', 'KommaH'];
		if ($.inArray(wgUserName, exceptionsList) == -1) {
			// Change to skip per lighthouse bug #572
			setTimeout( skip, 250 );
			//setTimeout( markPatrolled, 250 );
		}
	}
}

/*
function cancelRollback() {
	$('#rollback-link').html(previousHTML);
}
*/

function setRollbackURL(url) {
	rollbackUrl = url;
}

function rollback() {
	var span = $('#rollback-link');
	if (!span.length) span = $('#rollback-status');
	span.html('<b>' + msg_rollback_inprogress + '</b>');

	$.get(rollbackUrl, function(response) {
		var span = $('#rollback-link');
		if (!span.length) {
			$('#rollback-status').html(response);
			if (readyForRollback) postRollbackCallback();
			return false;
		} else {
			if (response.indexOf("<title>Rollback failed") > 0) {
				var msg = '<br/><div style="background: red;"><b>' + msg_rollback_fail + '</b></div>';
			} else {
				var msg = '<br/><div style="background: yellow;"><b>' + msg_rollback_complete + '</b></div>';
			}
			span.html(msg);
			if (readyForRollback) postRollbackCallback();
		}
	});

	$('body').trigger('trackAction');

	return false;
}

$(document).ready(initKeyBindings);
$(document).ready(initRCPatrol);

// External methods
window.WH.RCPatrol = {
	setupTabs : setupTabs,
	skip : skip,
	changeReverse : changeReverse,
	changeUserFilter : changeUserFilter,
	changeUser : changeUser,
	markPatrolled : markPatrolled,
	preloadNext : preloadNext,
	goback : goback,
	handleQESubmit : handleQESubmit,
	setRollbackURL : setRollbackURL,
	rollback : rollback
};

}(jQuery, mediaWiki) );
