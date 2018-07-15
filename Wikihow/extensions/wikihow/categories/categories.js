
var cat_request; 
var gIndex = null;

function categoryHandler2() {
	if ( cat_request.readyState == 4) {
		if ( cat_request.status == 200) {
			text = cat_request.responseText;
    	
			// add in the new ones	
    		var subs = document.getElementById("category" + gIndex);
			var lines = text.split("\n");
			for (i = 0; i < lines.length; i++) {
				var opt = document.createElement('option');
				var value = lines[i].replace(/^\s+|\s+$/g,"");
				if (value == "") continue;
				value = value.substring(1);
				opt.value = value.replace(/\*/, "");
				opt.text = value.replace(/\*/g, "");
				if (value.substring(0,1) == "*" && value.substring(0,2) != "**") {
					opt.setAttribute('style', 'font-weight:bold;');
				} else {
					opt.setAttribute('style', 'padding-left: 10px;');
				}
				subs.options[subs.length] = opt;

			}
		}
	}
}

function categoryHandler() {
    if ( cat_request.readyState == 4) {
        if ( cat_request.status == 200) {
            text = cat_request.responseText;

            // add in the new ones  
            var subs = document.getElementById("category" + gIndex);
    		for (i = subs.length - 1; i >= 0; i--) {
        		subs.remove(i);
    		}
			var ch_div = document.getElementById('category_div' + gIndex);
			var html = "<SELECT onchange='catHelperUpdateSummary();' name='category" + gIndex + "' id='category" + gIndex + "' tabindex=3 class='subcategory_dropdown'>" 
				+ "<OPTION VALUE=''>" + gCatHelperSMsg + "</OPTION>"
				+ text + "</SELECT>";
			ch_div.innerHTML = html;
        }
    }
}

function updateCategories (index) {
	try {
		cat_request = new XMLHttpRequest();
	} catch (error) {
		try {
			cat_request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}

	//erase previous contents
	var subs = document.getElementById("category" + index);
	for (i = subs.length - 1; i >= 0; i--) {
		subs.remove(i);
	}
//	var msg = document.getElementById('category_message');
//	msg.innerHTML = "Loading...";
	opt = new Option("Loading...", "");
	subs.options[subs.length] = opt;
	cat = document.getElementById("topcategory" + index).value;		
	var url = gCatHelperUrl + "?cat=" + encodeURIComponent(cat);
	cat_request.open('GET', url,true);
	cat_request.send(''); 
	cat_request.onreadystatechange = categoryHandler;
	gIndex = index;
}

function showanother() {
	var i;
	for (i = 0; i < gMaxCats; i++) {
		var top = document.getElementById("topcategory" + i);
		var sub = document.getElementById("category" + i);
		var style = top.getAttribute('style');	
		if (top && style == 'display: none;') {
			top.setAttribute('style', 'display:inline;');
			sub.setAttribute('style', 'display:inline;');	
			i++;
			break;
		} else {
		}
	}
	if (i == gMaxCats) {
		var link = document.getElementById("showmorecats");
		link.setAttribute('style', 'display:none;');
	}
	return;
}
	
function catHelperUpdateSummary() {
    if (gCatMsg != '' && document.editform.wpSummary.value.indexOf(gCatMsg) < 0) {
        if (document.editform.wpSummary.value != '') {
            document.editform.wpSummary.value += ', ';  
        }   
        document.editform.wpSummary.value += gCatMsg;
    }
    return true;
}
