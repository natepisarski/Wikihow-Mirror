
/*
the below javascript was taken from original swapEm script
!!!!!!!!!!!!!!!! DANGER !!!!!!!!!!!!!!!!
THE FOLLOWING WINDOW VARIABLE DELCARATIONS CANNOT BE RENAMED OR MOVED
THEY ARE REFERENCED BY MW MESSAGES IN TRANSLATIONS AS WELL AS ENGLISH
AND WOULD REQUIRE TESTING IN ALL LANGUAGES
*/

(function () {
    'use strict';
    window.isBig = true;
    window.isLandscape = (document.documentElement.clientHeight < document.documentElement.clientWidth);

    if (screen.width < 500 || (screen.height < 421 && isLandscape)) {
        window.isBig = false;
    }

    window.isRetina = window.devicePixelRatio !== undefined && devicePixelRatio > 1;
    window.showAds = true;
    window.isOldAndroid = false;
	window.isIPhone5 = false;
	window.isOldIOS = false;
	window.isWindowsPhone = false;

    // taken from http://docs.aws.amazon.com/silk/latest/developerguide/detecting-silk-ua.html
    //var match = /(?:; ([^;)]+) Build\/.*)?\bSilk\/([0-9._-]+)\b(.*\bMobile Safari\b)?/.exec(navigator.userAgent);
    var ua = navigator.userAgent.toLowerCase();

    //TODO: determine if this will ever be used again...
    //if (typeof match !== 'undefined' && match != null && typeof match[1] === 'undefined') {
        //showAds = false;
    //}

    // determining the version of android
    if (ua.indexOf('android') != -1) {
        //first check for firefox, we'll make all these small
        if (ua.indexOf('firefox') != -1) {
            window.isBig = false;
        }

        var androidVersion = parseFloat(ua.match(/android\s+([\d\.]+)/)[1]);

        //if (androidVersion < 2) {
            //showAds = false;
        //}
        // Show small ads on androids 2.x and lower to address too wide ad sizes we've been encountering
        if (androidVersion < 3.0) {
            window.isOldAndroid = true;
            window.isBig = false;
        }
    }

	var osIndex = navigator.userAgent.indexOf('OS');
	if ((navigator.userAgent.indexOf('iPhone') > -1 || navigator.userAgent.indexOf('iPad') > -1) && osIndex > -1) {
		var iOSversion = window.Number(navigator.userAgent.substr(osIndex + 3, 3).replace('_', '.'));
		window.isIPhone5 = iOSversion >= 6 && window.devicePixelRatio >= 2 && screen.availHeight == 548;
		window.isOldIOS = iOSversion < 6;
	}

	if (navigator.userAgent.indexOf('iemobile') > -1) {
		window.isWindowsPhone = true;
	}
}());
