var request = null;

function logHeight() {
    try {
        request = new XMLHttpRequest();
    } catch (error) {
        try {
			request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
            return false;
        }
    }
    var b = document.getElementById('body');
    var params = "height=" + b.scrollHeight;
    params += "&page=" + wgArticleId;
    params += "&client=" + encodeURIComponent(navigator.userAgent);
    url = "http://" + window.location.hostname + "/Special:LogHeight?" + params;
	request.open('GET', url,false);
	request.send('');
}

var r = Math.random();
if (r < 0.10) {
	window.setTimeout(logHeight, 300);
}
