function logJSTime(time, key) {
	if (time != null) {
		value = +(Math.round(time + "e+2")  + "e-2")
	}
	console.log("here", value);
	var xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/Special:Articlestats", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	var params = "key=" + key;
	params = params + "&val=" + value;
	params = params + "&hostname=" + window.location.hostname;
	params = params + "&pageid=" + window.WH.pageID;
	xhttp.send(params);
}
function recordTTI(time) {
	logJSTime(time, 'tti');
}
ttiPolyfill.getFirstConsistentlyInteractive().then(recordTTI);
