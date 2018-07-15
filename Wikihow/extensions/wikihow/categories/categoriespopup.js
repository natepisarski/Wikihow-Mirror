//Global
var mycathash = location.hash;
var SUP = ""; //Special Uncategorized Page Flag
var articleTitle = "";

function handleSAVE(form) {
	if (SUP) {
		if (getFormData(form)) {
		} else {
			//resetAll(form);
			//top.document.getElementById('modalPage').style.display = 'none';
			//top.location.reload();
		}

	} else {
		if (getFormData(form)) {
			alert("Maximum of 2 categories allowed.  Please unselect a category.");
		} else {
			showSelected();
			top.document.getElementById('modalPage').style.display = 'none';
		}
	}
	return false;
}

function handleCancel() {
	top.document.getElementById('modalPage').style.display = 'none';
	return false;
}

function resetAll(form) {
	document.catsearchform.category_search.value = "";

	for (var i = 0; i < form.elements.length; i++) {
		if (form.elements[i].type == "checkbox") {
			if (form.elements[i].checked == true) {
				form.elements[i].checked = false;
			}
		}
	}

	collapseAll();
	return false;
}

function collapseAll() {
	var divs = document.getElementsByTagName("div");
	for (i=0;i < divs.length; i++){
		if ((divs[i].id.match("toggle_")) && (divs[i].style.display != "none")) {
			imgdiv = divs[i].id.replace(/toggle_/, "img_");
			var idivid = document.getElementById(imgdiv);
			if (idivid) { imgOff(idivid); }

			Effect.Fade(divs[i], {duration: 0.0});
		}
	}
	return false;
}

function checkSelected() {
	var catcount = 0;

	if (top.document.getElementById("editform") == null) {
		SUP = true;
	} else {
		while (typeof(eval(top.document.editform["topcategory"+catcount])) != 'undefined') {
			var topcat = top.document.editform["topcategory"+catcount].value;
			var subcat = top.document.editform["category"+catcount].value;
			var idx = topcat+","+subcat;

			if (topcat != "" && document.forms.category && document.forms.category[idx]) {
				if (document.forms.category[idx].type == "checkbox") {
					document.forms.category[idx].checked = true;
				};
			}
	
			catcount++;
		}
	}
	return false;
}

function supAC(title) {
	title = urldecode(title);

	top.document.getElementById('modalPage').style.display = 'block';
	initToCategory(title);
	document.forms.catsearchform.category_search.focus();
	
}

function supResetLI(title) {
	title = htmlentities(title, "ENT_NOQUOTES");
	var lidiv = top.document.getElementById(title);

	if (lidiv != null) {
		lidiv.innerHTML = lidiv.innerHTML.replace(/<input(.*?)>/i,"");
	} else {
		alert("LI DIV is null. "+title);
	}
}
 
function expandSelected() {
	var catdiv = top.document.getElementById("catdiv");
	var categories = catdiv.innerHTML;

	if (catdiv.innerHTML != "Article has not been categorized.") {
		var cats = categories.split(",");
		var newcat = "";
		var gotocat = "";
	
		if (cats.length > 0) {
			for (i=0;i < cats.length; i++){
				newcat = cats[i].replace(/^\s+/, "");
				expandDIV(catSub(urlencode(newcat)));
				if (i == 0) {
					gotocat = catSub(urlencode(newcat));
				}
			}
			
			gotoCategorySelected(gotocat);
		}
	}

	return false;
}

function expandDIV(cat) {
	var togglediv = "toggle_"+cat;
	var imgdiv = "img_"+cat;

	//var tdivid = document.getElementById(togglediv);
	//var idivid = document.getElementById(imgdiv);

	expandDIV2(cat);

	var ptdivid = document.getElementById(togglediv).parentNode.parentNode;
	var ptag = ptdivid.id;
	var matchpos = ptag.search(/toggle_/);
	
	while(matchpos != -1) {
		var x = ptag.replace(/toggle_/,"");
		expandDIV2(x);

		ptdivid = ptdivid.parentNode.parentNode;
		ptag = ptdivid.id;
		matchpos = ptag.search(/toggle_/);
	}
}

function expandDIV2(cat) {
	var togglediv = "toggle_"+cat;
	var imgdiv = "img_"+cat;
	var idivid = document.getElementById(imgdiv);
	var tdivid = document.getElementById(togglediv);

	if (idivid) { imgOn(idivid); }
	Effect.Appear(togglediv, {duration: 0.0});
	return false;
}

function getFormData(form) {
	var searchString = "?";
	var onePair;
	var catcount = 0;
	var catSplit;
	var catlist = "";
	var selectioncount = 0;
    
	if (top.document.getElementById("editform") == null) {
		SUP = true;
		var url = "/Special:Categoryhelper";
		//var url = "/x/f.php";

		var JSONObj = new Object;
		JSONObj.type = "supSubmit";
		JSONObj.json = "true";
		var ctitle = articleTitle.replace(/\./g, "-whPERIOD-");
		ctitle = ctitle.replace(/\"/g, "-whDOUBLEQUOTE-");
		JSONObj.ctitle = urlencode(ctitle);
		for (var i = 0; i < form.elements.length; i++) {
			if (form.elements[i].type == "checkbox") {
				if (form.elements[i].checked == true) {
					selectioncount++;
					if (selectioncount > 2) {
						alert("Only 2 categories allowed per article.");
						return true;
					}
					catSplit = form.elements[i].name.split(",");
					JSONObj["topcategory"+catcount] = urlencode(catSplit[0]);
					JSONObj["category"+catcount] = urlencode(catSplit[1]);
					catcount++;
				}
			}
		}


		new Ajax.Request(url,
		{
			method:'get',
			parameters:Object.toJSON(JSONObj),
			onSuccess: function(transport){
				var response = transport.responseText || "No response.";
				if (response.match("Category Successfully Saved.")) {
					//alert("Category Successfully Saved!\n");
					resetAll(form);
					supResetLI(articleTitle);
					top.document.getElementById('modalPage').style.display = 'none';
				} else {
					alert("ERROR: Categories not saved. "+response);
				}
			},
			onFailure: function(){ 
				alert('Failed Saving Categories.');
			}
		});
	} else {
		top.document.editform["topcategory0"].value = "";
		top.document.editform["category0"].value = "";
		top.document.editform["topcategory1"].value = "";
		top.document.editform["category1"].value = "";
	
		for (var i = 0; i < form.elements.length; i++) {
  	      if (form.elements[i].type == "checkbox") {
					if (form.elements[i].checked == true) {
						selectioncount++;
						if (selectioncount > 2) {
							return true;
						}
						if (typeof(eval(top.document.editform["topcategory"+catcount])) != 'undefined') {
							catSplit = form.elements[i].name.split(",");
							top.document.editform["topcategory"+catcount].value = catSplit[0];
							top.document.editform["category"+catcount].value = catSplit[1];
							if (catcount == 0) {
								catlist = catSplit[1];
							} else {
								catlist += ", "+catSplit[1];
							}
							catcount++;
						}
	
					}
  	      } else continue
		}
		if (catlist == "") {
			catlist = "Article has not been categorized."
		}
		var catdiv = top.document.getElementById("catdiv");
		catdiv.innerHTML = catlist;
		top.document.editform.wpSummary.value = "categorization";
	}

	return false;
}

function showSelected() {
	var newdiv = "";
	var selectdiv = document.getElementById("selectdiv");
	var catcount = 0;

	if (top.document.getElementById("editform") == null) {
		SUP = true;
	} else {
		while (typeof(eval(top.document.editform["topcategory"+catcount])) != 'undefined') {
			var topcat = top.document.editform["topcategory"+catcount].value;
			var subcat = top.document.editform["category"+catcount].value;
			if (subcat != "") {
				newdiv += "<a href=\"#\" onclick=\"gotoCategorySelected('"+catSub(urlencode(subcat))+"'); return false;\">"+subcat+"</a><br />";
			}
			catcount++;
		}
	}	

	if (newdiv == "") {
		selectdiv.innerHTML = "Article has not been categorized.";
	} else {
		selectdiv.innerHTML = newdiv;
	}

	return false;
}

function checkCategory() {
	var cat = urlencode(document.catsearchform.category_search.value.toUpperCase());
	if (document.getElementById(catSub(cat)) != null)  {
		gotoCategorySelected(catSub(cat));
		setTimeout("document.catsearchform.category_search.focus()",900);
	}
	return false;
}

function searchCategory() {
	var cat = urlencode(document.catsearchform.category_search.value.toUpperCase());
	if (document.getElementById(catSub(cat)) == null)  {
		alert("Category does not exist.")
	} else {
		gotoCategorySelected(catSub(cat));
	}

	document.catsearchform.category_search.focus();
	return false;
}

function initToCategory(title){
	articleTitle = title;

	if (top.document.getElementById("catdiv") == null) {
		SUP = true;
		var ctitleid = top.document.getElementById("ctitle");
		ctitleid.innerHTML = "Select category for: How to " + title.replace(/-/g, " ");
		ctitleid.title = title.replace(/-/g, " ");
	} else {
		var catdiv = top.document.getElementById("catdiv");
		var categories = catdiv.innerHTML;
	
		if (catdiv.innerHTML != "Article has not been categorized.") {
			var cats = categories.split(",");
			if (cats.length > 0) {
				if (navigator.userAgent.match(/Firefox/i)) {
					//What to do for firefox???
					expandDIV(catSub(urlencode(cats[0].toUpperCase())));
					mycathash = catSub(urlencode(cats[0].toUpperCase()));
					setTimeout("gotoCategoryFF()",900);
					
				} else {
					gotoCategorySelected(catSub(urlencode(cats[0])));
				}
			}
		}
	}
	return false;
}

function gotoCategorySelected(cat) {
	cat = cat.toUpperCase();
	expandDIV(cat);
	location.hash = cat;
	setTimeout("gotoCategory()",900);
	return false
}

function gotoCategory() {
	var newcat = location.hash;
	location.hash = newcat;
	//for webkit browsers
	setHash(newcat);
}

function gotoCategoryFF() {
	var newcat = mycathash;
	location.hash = newcat;
	//for webkit browsers
	setHash(newcat);
}


function toggleImg(img) {
	if ( img.src.match(/off/) ) {
		img.src = "/skins/WikiHow/topics-arrow-on.gif";
	} else {
		img.src = "/skins/WikiHow/topics-arrow-off.gif";
	}
	return false
}
function imgOff(img) {
	img.src = "/skins/WikiHow/topics-arrow-off.gif";
	return false
}
function imgOn(img) {
	img.src = "/skins/WikiHow/topics-arrow-on.gif";
	return false
}

function catSub(str) {
	var newstr = "";
	newstr = str.replace(/'/g, "%27");
	newstr = newstr.replace(/\(/g, "%28");
	newstr = newstr.replace(/\)/g, "%29");
	newstr = newstr.replace(/\./g, "%2E");
	newstr = newstr.replace(/%26amp%3B/g, "%26");

	return newstr;
}

//##################################################



function setHash(hash) {
  if (navigator.userAgent.match(/Safari/i)) {
   if (parseFloat(navigator.userAgent.substring(navigator.userAgent.indexOf('Safari') + 7)) < 412) {
     // the form doesn't need to be attached to the document.
     var f = document.createElement('form');
     f.action = hash;
     f.submit();
   } else {
       var evt = document.createEvent('MouseEvents');
       evt.initEvent('click', true, true);
       var anchor = document.createElement('a');
       anchor.href = hash;
       anchor.dispatchEvent(evt);
   }
  } else {
    window.location.hash = hash;
  }
} 


function urlencode( str ) {
    // URL-encodes string
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_urlencode/
    // +       version: 901.1411
    // +   original by: Philip Peterson
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: AJ
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir
    // %          note: info on what encoding functions to use from: http://xkr.us/articles/javascript/encode-compare/
    // *     example 1: urlencode('Kevin van Zonneveld!');
    // *     returns 1: 'Kevin+van+Zonneveld%21'
    // *     example 2: urlencode('http://kevin.vanzonneveld.net/');
    // *     returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
    // *     example 3: urlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
    // *     returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'
                             
    var histogram = {}, tmp_arr = [];
    var ret = str.toString();
    
    var replacer = function(search, replace, str) {
        var tmp_arr = [];
        tmp_arr = str.split(search);
        return tmp_arr.join(replace);
    };
    
    // The histogram is identical to the one in urldecode.
    histogram["'"]   = '%27';
    histogram['(']   = '%28';
    histogram[')']   = '%29';
    histogram['*']   = '%2A';
    histogram['~']   = '%7E';
    histogram['!']   = '%21';
    histogram['%20'] = '+';
    
    // Begin with encodeURIComponent, which most resembles PHP's encoding functions
    ret = encodeURIComponent(ret);
    
    for (search in histogram) {
        replace = histogram[search];
        ret = replacer(search, replace, ret) // Custom replace. No regexing
    }
    
    // Uppercase for full PHP compatibility
    return ret.replace(/(\%([a-z0-9]{2}))/g, function(full, m1, m2) {
        return "%"+m2.toUpperCase();
    });
    
    return ret;
}


function urldecode( str ) {
    // Decodes URL-encoded string
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_urldecode/
    // +       version: 901.1411
    // +   original by: Philip Peterson
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: AJ
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir
    // %          note: info on what encoding functions to use from: http://xkr.us/articles/javascript/encode-compare/
    // *     example 1: urldecode('Kevin+van+Zonneveld%21');
    // *     returns 1: 'Kevin van Zonneveld!'
    // *     example 2: urldecode('http%3A%2F%2Fkevin.vanzonneveld.net%2F');
    // *     returns 2: 'http://kevin.vanzonneveld.net/'
    // *     example 3: urldecode('http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a');
    // *     returns 3: 'http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a'
    
    var histogram = {};
    var ret = str.toString();
    
    var replacer = function(search, replace, str) {
        var tmp_arr = [];
        tmp_arr = str.split(search);
        return tmp_arr.join(replace);
    };
    
    // The histogram is identical to the one in urlencode.
    histogram["'"]   = '%27';
    histogram['(']   = '%28';
    histogram[')']   = '%29';
    histogram['*']   = '%2A';
    histogram['~']   = '%7E';
    histogram['!']   = '%21';
    histogram['%20'] = '+';

    for (replace in histogram) {
        search = histogram[replace]; // Switch order when decoding
        ret = replacer(search, replace, ret) // Custom replace. No regexing   
    }
    
    // End with decodeURIComponent, which most resembles PHP's encoding functions
    ret = decodeURIComponent(ret);

    return ret;
}

function get_html_translation_table(table, quote_style) {
    // Returns the translation table used by htmlspecialchars() and htmlentities()
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_get_html_translation_table/
    // +       version: 901.714
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js. Meaning the constants are not
    // %          note: real constants, but strings instead. integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // %          note: Table from http://www.the-art-of-web.com/html/character-codes/
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    
    var entities = {}, histogram = {}, decimal = 0, symbol = '';
    var constMappingTable = {}, constMappingQuoteStyle = {};
    var useTable = {}, useQuoteStyle = {};
    
    useTable      = (table ? table.toUpperCase() : 'HTML_SPECIALCHARS');
    useQuoteStyle = (quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT');
    
    // Translate arguments
    constMappingTable[0]      = 'HTML_SPECIALCHARS';
    constMappingTable[1]      = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';
    
    // Map numbers to strings for compatibilty with PHP constants
    if (!isNaN(useTable)) {
        useTable = constMappingTable[useTable];
    }
    if (!isNaN(useQuoteStyle)) {
        useQuoteStyle = constMappingQuoteStyle[useQuoteStyle];
    }
    
    if (useQuoteStyle != 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }

    if (useQuoteStyle == 'ENT_QUOTES') {
        entities['39'] = '&#039;';
    }

    if (useTable == 'HTML_SPECIALCHARS') {
        // ascii decimals for better compatibility
        entities['38'] = '&amp;';
        entities['60'] = '&lt;';
        entities['62'] = '&gt;';
    } else if (useTable == 'HTML_ENTITIES') {
        // ascii decimals for better compatibility
	    entities['38']  = '&amp;';
	    entities['60']  = '&lt;';
	    entities['62']  = '&gt;';
	    entities['160'] = '&nbsp;';
	    entities['161'] = '&iexcl;';
	    entities['162'] = '&cent;';
	    entities['163'] = '&pound;';
	    entities['164'] = '&curren;';
	    entities['165'] = '&yen;';
	    entities['166'] = '&brvbar;';
	    entities['167'] = '&sect;';
	    entities['168'] = '&uml;';
	    entities['169'] = '&copy;';
	    entities['170'] = '&ordf;';
	    entities['171'] = '&laquo;';
	    entities['172'] = '&not;';
	    entities['173'] = '&shy;';
	    entities['174'] = '&reg;';
	    entities['175'] = '&macr;';
	    entities['176'] = '&deg;';
	    entities['177'] = '&plusmn;';
	    entities['178'] = '&sup2;';
	    entities['179'] = '&sup3;';
	    entities['180'] = '&acute;';
	    entities['181'] = '&micro;';
	    entities['182'] = '&para;';
	    entities['183'] = '&middot;';
	    entities['184'] = '&cedil;';
	    entities['185'] = '&sup1;';
	    entities['186'] = '&ordm;';
	    entities['187'] = '&raquo;';
	    entities['188'] = '&frac14;';
	    entities['189'] = '&frac12;';
	    entities['190'] = '&frac34;';
	    entities['191'] = '&iquest;';
	    entities['192'] = '&Agrave;';
	    entities['193'] = '&Aacute;';
	    entities['194'] = '&Acirc;';
	    entities['195'] = '&Atilde;';
	    entities['196'] = '&Auml;';
	    entities['197'] = '&Aring;';
	    entities['198'] = '&AElig;';
	    entities['199'] = '&Ccedil;';
	    entities['200'] = '&Egrave;';
	    entities['201'] = '&Eacute;';
	    entities['202'] = '&Ecirc;';
	    entities['203'] = '&Euml;';
	    entities['204'] = '&Igrave;';
	    entities['205'] = '&Iacute;';
	    entities['206'] = '&Icirc;';
	    entities['207'] = '&Iuml;';
	    entities['208'] = '&ETH;';
	    entities['209'] = '&Ntilde;';
	    entities['210'] = '&Ograve;';
	    entities['211'] = '&Oacute;';
	    entities['212'] = '&Ocirc;';
	    entities['213'] = '&Otilde;';
	    entities['214'] = '&Ouml;';
	    entities['215'] = '&times;';
	    entities['216'] = '&Oslash;';
	    entities['217'] = '&Ugrave;';
	    entities['218'] = '&Uacute;';
	    entities['219'] = '&Ucirc;';
	    entities['220'] = '&Uuml;';
	    entities['221'] = '&Yacute;';
	    entities['222'] = '&THORN;';
	    entities['223'] = '&szlig;';
	    entities['224'] = '&agrave;';
	    entities['225'] = '&aacute;';
	    entities['226'] = '&acirc;';
	    entities['227'] = '&atilde;';
	    entities['228'] = '&auml;';
	    entities['229'] = '&aring;';
	    entities['230'] = '&aelig;';
	    entities['231'] = '&ccedil;';
	    entities['232'] = '&egrave;';
	    entities['233'] = '&eacute;';
	    entities['234'] = '&ecirc;';
	    entities['235'] = '&euml;';
	    entities['236'] = '&igrave;';
	    entities['237'] = '&iacute;';
	    entities['238'] = '&icirc;';
	    entities['239'] = '&iuml;';
	    entities['240'] = '&eth;';
	    entities['241'] = '&ntilde;';
	    entities['242'] = '&ograve;';
	    entities['243'] = '&oacute;';
	    entities['244'] = '&ocirc;';
	    entities['245'] = '&otilde;';
	    entities['246'] = '&ouml;';
	    entities['247'] = '&divide;';
	    entities['248'] = '&oslash;';
	    entities['249'] = '&ugrave;';
	    entities['250'] = '&uacute;';
	    entities['251'] = '&ucirc;';
	    entities['252'] = '&uuml;';
	    entities['253'] = '&yacute;';
	    entities['254'] = '&thorn;';
	    entities['255'] = '&yuml;';
    } else {
        throw Error("Table: "+useTable+' not supported');
        return false;
    }
    
    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal)
        histogram[symbol] = entities[decimal];
    }
    
    return histogram;
}

function html_entity_decode( string, quote_style ) {
    // Convert all HTML entities to their applicable characters
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_html_entity_decode/
    // +       version: 901.714
    // +   original by: john (http://www.jd-tech.net)
    // +      input by: ger
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: marc andreu
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: get_html_translation_table
    // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
    // *     returns 1: 'Kevin & van Zonneveld'
    // *     example 2: html_entity_decode('&amp;lt;');
    // *     returns 2: '&lt;'

    var histogram = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (histogram = get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }

    // &amp; must be the last character when decoding!
    delete(histogram['&']);
    histogram['&'] = '&amp;';

    for (symbol in histogram) {
        entity = histogram[symbol];
        tmp_str = tmp_str.split(entity).join(symbol);
    }
    
    return tmp_str;
}

function htmlentities (string, quote_style) {
    // Convert all applicable characters to HTML entities
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_htmlentities/
    // +       version: 812.3017
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: nobbler
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: get_html_translation_table
    // *     example 1: htmlentities('Kevin & van Zonneveld');
    // *     returns 1: 'Kevin &amp; van Zonneveld'

    var histogram = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (histogram = get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }
    
    for (symbol in histogram) {
        entity = histogram[symbol];
        tmp_str = tmp_str.split(symbol).join(entity);
    }
    
    return tmp_str;
}

function addslashes(str) {
	str=str.replace(/\\/g,'\\\\');
	str=str.replace(/\'/g,'\\\'');
	str=str.replace(/\"/g,'\\"');
	str=str.replace(/\0/g,'\\0');
	return str;
}
function stripslashes(str) {
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}
