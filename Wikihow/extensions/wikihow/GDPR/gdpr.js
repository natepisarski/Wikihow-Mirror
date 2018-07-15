WH.gdpr = (function () {
	var acceptCookieName = 'gdpr_accept';
	var delineCookieName = 'gdpr_decline';
	function hasAcceptCookie() {
		var hasCookie = document.cookie.indexOf('gdpr_accept=');
		if (hasCookie >= 0) {
			return true;
		}
		return false;
	}

	function hasEUQueryParam() {
		var url = window.location.href;
		if (url.indexOf('?EU=') != -1) {
			return true;
		} else if( url.indexOf('&EU=') != -1) {
			return true;
		}
		return false;
	}

	function isEULocation() {
		var value = "; " + document.cookie;
		var parts = value.split("; vi=");
		var res = null;
		if (parts.length == 2){
			res = parts.pop().split(";").shift();
		}

		if (hasEUQueryParam()) {
			res = "EU";
		}

		if (res == "EU") {
			return true;
		}
		return false;
	}
	function popupClosed() {
		createCookie("gdpr_accept", 1, 365);
		createCookie("gdpr_decline", "", -1);
		gdpr.style.display = "none";
	}

	function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

	function declinePage() {
		var decline = document.getElementById('gdpr_decline');
		if (decline) {
			decline.style.display = "block";
			decline.addEventListener ("click", function() {
				var cookies = document.cookie.split(";");
				for (var i = 0; i < cookies.length; i++) {
					createCookie(cookies[i].split("=")[0],"",-1);
				}
				createCookie("gdpr_decline", 1, 1);
				var declineConfirm = document.getElementById('gdpr_decline_confirm');
				if (declineConfirm) {
					declineConfirm.style.display = 'block';
				}
			});
		}
		var declineOk = document.getElementById('gdpr_decline_confirm_dismiss');
		if (declineOk) {
			declineOk.addEventListener ("click", function() {
				var declineConfirm = document.getElementById('gdpr_decline_confirm');
				if (declineConfirm) {
					declineConfirm.style.display = 'none';
				}
			});
		}
	}

	function pageLoaded() {
		if (!isEULocation()) {
			return;
		}
		declinePage();
		document.body.className += ' ' + 'geo-group-eu';
		var sidebarShare = document.getElementById('sidebar_share');
		if (sidebarShare) {
			sidebarShare.remove();
		}
	}

	function initialize() {
		var isEU = isEULocation();
		var gdpr = document.getElementById('gdpr');
		if (gdpr && isEU && !hasAcceptCookie() ) {
			gdpr.style.display = "block";
		}
		var accept = document.getElementById('gdpr_accept');
		if (accept) {
			accept.addEventListener ("click", popupClosed);
		}
		var close = document.getElementById('gdpr_close');
		if (close) {
			close.addEventListener ("click", popupClosed);
		}
		return;
	}
	return {
		'initialize':initialize,
		'pageLoaded':pageLoaded,
		'isEULocation':isEULocation
	};
})();
document.addEventListener('DOMContentLoaded', function() {WH.gdpr.pageLoaded();}, false);
