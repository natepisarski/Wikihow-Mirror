function logJSTime(time, key) {
	if (time != null) {
		value = +(Math.round(time + "e+2")  + "e-2")
	}
	console.log("logjstime key", key, 'val', value);
	var xhttp = new XMLHttpRequest();
	xhttp.open("POST", "/Special:Articlestats", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	var params = "key=" + key;
	params = params + "&val=" + value;
	params = params + "&hostname=" + window.location.hostname;
	params = params + "&pageid=" + window.WH.pageID;
	xhttp.send(params);
}
if ('PerformanceLongTaskTiming' in window) {
	var g=window.__tti={e:[]};g.o=new PerformanceObserver(function(l){
		for (const entry of l.getEntries()) {
			const metricName = entry.name;
			const time = Math.round(entry.startTime + entry.duration);
			logJSTime(time, metricName);
		}
	});
	g.o.observe({entryTypes:['paint']})
};
