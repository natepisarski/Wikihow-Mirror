(function($, mw) {

var rcElements = [];
var rcReset = true;
var rcCurrent = 0;
var rcElementCount = 0;
var rcServertime;
var rcwDPointer = 0;
var rcwToggleDiv = true;
var rcInterval = '';
var rcReloadInterval = '';
var rcwGCinterval = '';
var rcUnpatrolled = 0;
var rcNABcount = 0;
var isBooster = false;
var rcPause = false;
var rcExternalPause = false;
var rcUser = -1;
var rcThresholds = null;
var nabThresholds = null;

var rcwIsFull = 0;
var rcLoadCounter = 1;
var RCW_LOAD_COUNTER_MAX = 3;
var RCW_MAX_DISPLAY = 3;
var RCW_DEBUG_FLAG = false;
var RCW_DIRECTION = 'down';
var RCW_DEFAULT_URL = "/Special:RCWidget";
var RCW_ENGLISH = mw.config.get('wgContentLanguage') == 'en';
var server = mw.config.get('wgServer');
var RCW_CDN_SERVER = typeof server == 'string' ? server : '';
var rcwTestStatusOn = false;

var rc_URL = RCW_DEFAULT_URL;
var rc_ReloadInterval, rc_nabRedThreshold, rc_patrolRedThreshold;

// external params
function setParams(params) {
	rc_URL = params['rc_URL'];
	rc_ReloadInterval = params['rc_ReloadInterval'];
	rc_nabRedThreshold = params['rc_nabRedThreshold'];
	rc_patrolRedThreshold = params['rc_patrolRedThreshold'];
}

function getNextSpot() {
	if (rcwDPointer >= RCW_MAX_DISPLAY) {
		return 0;
	} else {
		return rcwDPointer;
	}
}

function getRCElem(listid, type) {

	if (typeof(rcElements) != "undefined") {
		var elem;

		var newelem = $('<div></div>');
		var newid = getNextSpot();
		var newdivid = 'welement'+newid;
		newelem.attr('id', newdivid);
		newelem.css('display', 'none');
		newelem.css('overflow', '');
		if (rcwToggleDiv) {
			newelem.attr('class', 'rc_widget_line even');
			rcwToggleDiv = false;
		} else {
			newelem.attr('class', 'rc_widget_line odd');
			rcwToggleDiv = true;
		}

		elem = "<div class='rc_widget_line_inner'>";

		elem += rcElements[ rcCurrent ].text + "<br />";
		//elem += "<span style='color: #AAAAAA;font-size: 11px;'>" + rcElements[ rcCurrent ].ts +" ("+rcCurrent+")</span>";
		elem += "<span class='rc_widget_time'>" + rcElements[ rcCurrent ].ts + "</span>";
		elem += "</div>";

		newelem.html($(elem));

		rcwDPointer = newid + 1;

		if (RCW_DIRECTION == 'down') {
			var firstChild = listid.children()[0];
			newelem.insertBefore(firstChild);
		} else {
			listid.append(newelem);
		}

		if (type == 'blind') {
			if (RCW_DIRECTION == 'down') {
				//new Effect.SlideDown(newelem);
				//newelem.show('blind', {direction: 'vertical'});
				//newelem.show('slide', {direction: 'up'});
				newelem.slideDown();
			} else {
				//new Effect.BlindDown(newelem);
				//newelem.show('blind', {direction: 'vertical'});
			}
		} else {
			//new Effect.Appear(newelem);
			newelem.fadeIn();
		}

		if (rcCurrent < rcElementCount - 1) {
			rcCurrent++;
		} else {
			rcCurrent = 0;
		}

		return newelem;
	} else {
		return "undefined";
	}
}

function rcUpdate() {
	if (rcPause || rcExternalPause) {
		return false;
	}

	var listid = $('#rcElement_list');

	if (rcwIsFull == RCW_MAX_DISPLAY) {
		var oldid = getNextSpot();
		var olddivid = $('#welement'+oldid);
	
		if (RCW_DIRECTION == 'down') {
			//new Effect.BlindUp(olddivid);
			//olddivid.effect('blind', {direction: 'up'});
			//olddivid.show('blind', {direction: 'vertical'});
		} else {
			//new Effect.SlideUp(olddivid);
			//olddivid.effect('slide', {direction: 'up'});
		}
		olddivid.attr('id','rcw_deleteme');
	}

	var elem = getRCElem(listid, 'blind');
	if (rcwIsFull < RCW_MAX_DISPLAY) { rcwIsFull++ }

}

var rcwRunning = true;
function rcTransport(obj) {
	var rcwScrollCookie = $.cookie('rcScroll');

	obj = $(obj);
	if (rcwRunning) {
		$.cookie('rcScroll', 'stop', {expires: 7});
		rcStop();
		rcwRunning = false;
		obj.addClass('play');
	} else {
		$.removeCookie('rcScroll');
		rcStart();
		obj.removeClass('play');
		rcwRunning = true;
   }
    
}

function rcStop() {
	clearInterval(rcInterval);
	clearInterval(rcReloadInterval);
	clearInterval(rcwGCinterval);

	rcInterval = '';
	rcReloadInterval = '';
	rcwGCinterval = '';
	rcGC();
	var obj = $('#play_pause_button');
	obj.addClass('play');
	rcwRunning = false;
}

function rcStart() {
	rcUpdate();
	rcLoadCounter = 1;
	if (rcReloadInterval == '') { rcReloadInterval = setInterval(rcwReload, rc_ReloadInterval); }
	if (rcInterval == '') { rcInterval = setInterval(rcUpdate, 3000); }
	if (rcwGCinterval == '') { rcwGCinterval = setInterval(rcGC, 30000); }
}

function rcwReadElements(nelem) {
	var Current = 0;
	var Elements = [];
	var Servertime = 0;
	var ElementCount = 0;
	var Unpatrolled = 0;
	var NABcount = null;

	for (var i in nelem) {
		if (typeof(i) != "undefined") {
			if (i == 'servertime'){
				Servertime = nelem[i];
			} else if(i == 'unpatrolled'){
				Unpatrolled = nelem[i];
			} else if (i == 'NABcount'){
				NABcount = nelem[i];
			} else if (i == 'rcThresholds') {
				rcThresholds = nelem[i];
			} else if (i== 'nabThresholds') {
				nabThresholds = nelem[i];
			} else {
				Elements.push(nelem[i]);
				ElementCount++;
			}
		}
	}

	Current = 0;

	rcServertime = Servertime;
	rcElements = Elements;
	rcElementCount = ElementCount;
	rcCurrent = Current;
	rcReset = true;
	rcUnpatrolled = Unpatrolled;
	rcNABcount = NABcount;
}

function rcwReload() {
	rcLoadCounter++;

	if (rcLoadCounter > RCW_LOAD_COUNTER_MAX) {
		rcStop();
		if (rcwTestStatusOn) $('#teststatus').innerHTML = "Reload Counter...Stopped:"+rcLoadCounter;
		return true;
	} else {
		if (rcwTestStatusOn) $('#teststatus').innerHTML = "Reload Counter..."+rcLoadCounter;
	}

	var url = RCW_CDN_SERVER + rc_URL + '?function=WH.RCWidget.rcwOnReloadData';
	rcwLoadUrl(url);
}

function rcwOnReloadData(data) {
	rcwReadElements(data);
	if (isBooster && rcNABcount != null) {
		$('#nabheader').show();
		rcwLoadNabWeather();
	} else {
		$('#nabheader').hide();
	}
	rcwLoadWeather();
}

function rcwLoad() {
	isBooster = $.inArray( "newarticlepatrol", mw.config.get('wgUserGroups')) >= 0;

	var listid = $('#rcElement_list');
	listid.css('height', (RCW_MAX_DISPLAY * 65) + 'px');
	listid.css('overflow', 'hidden');
	if (RCW_DEBUG_FLAG) { $('#rcwDebug').css('display', 'block'); }

	if (listid) {
		listid.mouseover(function(e) {
			rcPause = true;
		});
		listid.mouseout(function(e) {
			rcPause = false;
		});
	}


	var url = RCW_CDN_SERVER + rc_URL + '?function=WH.RCWidget.rcwOnLoadData';
	if(rcUser != -1)
		url += "&userId=" + rcUser;
	rcwLoadUrl(url);
}

function rcwLoadUrl(url) {
	var siterev = mw.config.get('wgWikihowSiteRev');
	if (url.indexOf('?') >= 0) {
		url += '&' + siterev;
	} else {
		url += '?' + siterev;
	}
	if (isBooster) {
		url += '&nabrequest=1';
	} else {
		url += '&nabrequest=0';
	}
	if (rcExternalPause) return false;
	var activateWidget = true;
	if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)){ //test for MSIE x.x;
		var ieversion = new Number(RegExp.$1) // capture x.x portion and store as a number
		// don't activate rcwidget for IE6
		if (ieversion < 7) {
			activateWidget = false;
		}
	}

	if (activateWidget) {
		// We need to change ajax caching to true so that jQuery won't append
		// the _ timestamp param to files loaded (which busts our cache).
		//
		// from: http://bugs.jquery.com/ticket/4898
		$.ajaxSetup( {cache: true} ); 

		// NOTE: I changed this to jQuery.getScript because creating our own
		// script element that we insert into the DOM caused a Chrome debugger
		// warning. We had done this originally to get around the cross-domain
		// restrictions on loading JS. (We used to load this payload from our
		// whstatic.com domain.)
		//
		// Here is the warning we'd see:
		// "Synchronous XMLHttpRequest on the main thread is deprecated because of
		// its detrimental effects to the end user's experience. For more help,
		// check https://xhr.spec.whatwg.org/."
		$.getScript(url, function() {
			// after loading happens, revert the ajax setting
			$.ajaxSetup( {cache: false} );
		} );
	}
}

function rcwOnLoadData(data) {
	rcwReadElements(data);

	var listid = $('#rcElement_list');
	if (rcwTestStatusOn) $('#teststatus').innerHTML = "Nodes..."+listid.childNodes.length;
	var rcwScrollCookie = $.cookie('rcScroll');

	if (!rcwScrollCookie) {
		var elem = getRCElem(listid, 'new');
		if (rcwIsFull < RCW_MAX_DISPLAY) { rcwIsFull++ }

		rcStart();
	} else {
		for (i = 0; i < RCW_MAX_DISPLAY; i++) {
			var elem = getRCElem(listid, 'new');
			if (rcwIsFull < RCW_MAX_DISPLAY) { rcwIsFull++ }
		}
		rcStop();
	}
	//if the user is an article booster
	if (isBooster && rcNABcount != null) {
		$('#nabheader').show();
		rcwLoadNabWeather();
	} else {
		$('#nabheader').hide();
	}
	rcwLoadWeather();
}

function rcwLoadWeather() {
	var rcWeather = jQuery('#rcwweather');
	var rcWeatherUnpatrolled = jQuery('.weather_unpatrolled');
	rcWeather.removeClass('sunny partlysunny cloudy rainy');
	if(rcUnpatrolled < rcThresholds.low) //sunny
		rcWeather.addClass("sunny");
	else if(rcUnpatrolled < rcThresholds.med) //sunny/cloudy
		rcWeather.addClass("partlysunny");
	else if(rcUnpatrolled < rcThresholds.high) //cloudy
		rcWeather.addClass("cloudy");
	else //rainy
		rcWeather.addClass("rainy");
	rcWeatherUnpatrolled.html(rcUnpatrolled);
	//threshold passed in from RCwidget.body.php
	if(rcUnpatrolled >= rc_patrolRedThreshold ){
		rcWeatherUnpatrolled.css('color','red');
	}
}

function rcwLoadNabWeather() {
	var nabWeather = jQuery('#nabweather');
	var rcWeatherUnpatrolled = jQuery('.weather_nab');
	nabWeather.removeClass('sunny partlysunny cloudy rainy');
	if(rcNABcount < nabThresholds.low) //sunny
		nabWeather.addClass("sunny");
	else if(rcNABcount < nabThresholds.med) //sunny/cloudy
		nabWeather.addClass("partlysunny");
	else if(rcNABcount < nabThresholds.high) //cloudy
		nabWeather.addClass("cloudy");
	else //rainy
		nabWeather.addClass("rainy");
	rcWeatherUnpatrolled.html(rcNABcount);
	//threshold passed in from RCwidget.body.php
	if(rcNABcount >= rc_nabRedThreshold){
		rcWeatherUnpatrolled.css('color','red');
	}
}

function rcGC() {
	if (rcwTestStatusOn) {
		var tmpHTML = $('#teststatus').innerHTML;
		$('#teststatus').innerHTML = "Garbage collecting...";
	}
	$('#rcElement_list #rcw_deleteme').remove();

	if (rcwTestStatusOn) $('#teststatus').innerHTML = tmpHTML;
}

// Module exports
WH.RCWidget = {};
WH.RCWidget.rcwLoad = rcwLoad;
WH.RCWidget.rcTransport = rcTransport;
WH.RCWidget.setParams = setParams;
WH.RCWidget.rcwOnLoadData = rcwOnLoadData;
WH.RCWidget.rcwOnReloadData = rcwOnReloadData;

})(jQuery, mw);

