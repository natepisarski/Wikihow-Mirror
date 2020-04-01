(function ($, mw) {

var SCROLL_SPEED_MS = 500;

function add(key) {
	document.temp.related.options[document.temp.related.length] = new Option(key, key);
	document.temp.related.selectedIndex = document.temp.related.length - 1;
	$.scrollTo('.firstHeading', SCROLL_SPEED_MS); 
}

function submitForm() {
	document.temp.related_list.value = '';
	if (document.temp.related) {
		for (var f = 0; f < document.temp.related.length; ++f) {
			document.temp.related_list.value += document.temp.related.options[f].value + '|';
		}
	}
	document.temp.submit();
}

function removeRelated() {
	for (var f = 0; f < document.temp.related.length; ++f) {
		if (document.temp.related.options[f].selected) {
			document.temp.related.options[f] = null;
			break;
		}
	}
	return false;
}

function preview(key, title) {
	if (!title) {
		title = key.replace(/-/g, ' ');
	}

	WH.ManageRelated.titleToOpen = mw.config.get('wgCanonicalServer') + '/' + key;
	WH.ManageRelated.titleToAdd = key;

	$.get(
		'/Special:PreviewPage/' + key,
		function (data) {
			var results = $('#preview');
			results.html('<span style="font-size: 18px; font-weight: bold;">How to ' + title + '</span><br/>');
			results.append('[<a href="#" onclick="WH.ManageRelated.add(WH.ManageRelated.titleToAdd); return false;">add related</a>] [<a href="#" onclick="window.open(WH.ManageRelated.titleToOpen); return false;">open in a new window</a>]<br/><br/>');
			results.append(data);
			$.scrollTo('#preview', SCROLL_SPEED_MS);
		}
	);
}

function check() {
	var query = document.temp.q.value;
	if (query) {
		var results = $('#lucene_results');
		results.html('Retrieving results...');
		$.get(
			'/Special:LSearch?raw=true&search=' + encodeURI(query),
			alertContentsCallback
		);
	}
	return false;
}

function alertContentsCallback(data) {
	var results = $('#lucene_results');
	var arr = data.split('\n');
	var count = 0;
	var html = '<p><i>Related wikiHows</i></p><ol first="2"><li>Click on a link to preview the article. To add the article as a related wikiHow, click the + symbol in the list or the "add this" link in the preview. When you are finished, hit the "Save" button.</li></ol><ul id="results">';
	var target = $("input[name='target']").val();
	for (var i = 0; i < arr.length; i++) {
		var key = decodeURIComponent(arr[i]);

		// don't include categories
		if (key.indexOf("Category:") >= 0) {
			continue;
		}

		// remove hostname if it exists
		key = $.trim(key).replace(/^(https?:)?\/\//, '');
		var idx = key.indexOf('/');
		if (idx > 0) {
			key = key.substr(idx + 1);
		}

		key = unescape( key.replace(/-/g, ' ') );
		var displayTitle = key;
		key = key.replace(/'/g, "\\'");

		// don't include this page in search results
		var targetTitle = target.replace(/-/g, ' ');
		if (targetTitle == displayTitle) {
			continue;
		}

		if (displayTitle) {
			html += '<li><a href="#" onclick="WH.ManageRelated.preview(\'' + key + '\'); return false;">' + displayTitle + '</a> [<a href="#" onclick="WH.ManageRelated.add(\'' + key + '\'); return false;">+</a>]</li>';
			count++;
		}
	}

	if (count == 0) {
		html += 'Sorry - no results.';
	}

	results.html(html);
	$.scrollTo('#lucene_results', SCROLL_SPEED_MS);
}

function viewRelated() {
	for(var f = 0; f < document.temp.related.length; ++f){
		if (document.temp.related.options[f].selected) {
			window.open('http://www.wikihow.com/' + document.temp.related.options[f].value);
			break;
		}
	}
}

function moveRelated(dir) {
	var el = document.temp.related;
	var idx = el.selectedIndex
	if (idx == -1) {
		return;
	} else {
		var nxidx = idx + ( dir == 'up' ? -1 : 1)
		if (nxidx < 0) nxidx=el.length-1
		if (nxidx >= el.length) nxidx=0
		var oldVal = el[idx].value
		var oldText = el[idx].text
		el[idx].value = el[nxidx].value
		el[idx].text = el[nxidx].text
		el[nxidx].value = oldVal
		el[nxidx].text = oldText
		el.selectedIndex = nxidx
	}
}

// module exports
WH.ManageRelated = {};

WH.ManageRelated.add = add;
WH.ManageRelated.moveRelated = moveRelated;
WH.ManageRelated.viewRelated = viewRelated;
WH.ManageRelated.check = check;
WH.ManageRelated.preview = preview;
WH.ManageRelated.removeRelated = removeRelated;
WH.ManageRelated.submitForm = submitForm;

// globals
WH.ManageRelated.titleToOpen = '';
WH.ManageRelated.titleToAdd = '';

})(jQuery, mw);
