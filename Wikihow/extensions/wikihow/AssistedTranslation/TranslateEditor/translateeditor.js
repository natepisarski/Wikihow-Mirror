( function($, mw) {

var llText="";

function checkForLL() {
	var txt = $("#wpTextbox1").val();
	var re = /\[\[[a-zA-Z][a-zA-Z]:[^\]]+\]\]/;
	var m = txt.match(re);
	if (m == null) {
		return(true);
	}
	else {
		alert("Interwiki links are no longer allowed");
		return(false);
	}
}

function checkForCite(sources_name, references_name) {
	var sources = sources_name;
	var txt = $("#wpTextbox1").val();
	if (txt.match(/<ref>/)) {
		var sourcesMatch =txt.match(new RegExp("== *" + sources + " *=="));
		var refs = references_name;
		var refsMatch =txt.match(new RegExp("== *" + refs + " *=="));
		if (!txt.match(/\{\{reflist\}\}/) || !(sourcesMatch || refsMatch)) {
			alert("ERROR: Article contains '<ref>', so there must be a '" + sources + "' or '" + refs + "' section with template {{reflist}} before saving or previewing");
			return(false);
		}
		else if (!txt.match(/<\/ref>/)) {
			alert("ERROR: Article must contain closing '</ref>' for ref tag");
		}
	}
	return(true);
}

function checkForStepSpaces() {
	var txt = $("#wpTextbox1").val();
	var m = txt.match("#[^=]+\n[^=#]*\n#([^\n]+)");
	if (m){
		alert("ERROR: Extra newline before the following step:\n\n " + m[1] + "\n\nYou must correct this before saving or previewing.");
		return(false);
	}
	var m = txt.match("[\n^] +#([^\n]+)");
	if (m) {
		alert("ERROR: Extra space before the following step:\n\n #" + m[1] + "\n\nYou must correct this before saving or previewing.");
		return(false);
	}
	return(true);
}

function checkForSteps(steps_name) {
	var steps = steps_name;
	var txt = $("#wpTextbox1").val();
	if (!txt.match(new RegExp("== *" + steps + " *==[^=]"))) {
		alert("ERROR: There must a be a '" + steps + "' section. You must add this section before saving or previewing.");
		return(false);
	}
	return(true);
}

/*
function removeLinks(revision) {
	links = revision.match(/\[\[[^\]]+\]\]/gi);
	for (var n in links) {
		var m;
		if (m = links[n].match(/\[\[([^|]+)\|([^\]]+)\]\]/)) {
			if (!m[1].match(/^ *image/i) && !m[1].match(/:/)) {
				revision = revision.replace(links[n],m[2]);
			}
		}
	}
	return(revision);
}
*/

function fixCite(revision, sources_name, references_name) {
	var sources = sources_name;
	var refs = references_name;
	if (revision.match(/<ref>/)) {
		if (!revision.match(/\{\{reflist\}\}/)) {
			if (revision.match("== "  + sources + " ==")) {
				revision = revision.replace(new RegExp("== *" + sources + " *=="), "== " + sources + " ==\n\{\{reflist\}\}");
			} else if(revision.match("== "  + refs + " ==")) {
				revision = revision.replace(new RegExp("== *" + refs + " *=="), "== " + refs + " ==\n\{\{reflist\}\}");
			} else {
				revision += revision + "== " + refs + " ==\n\{\{reflist\}\}";
			}
		}
	}
	return(revision);
}

function fixSteps(revision) {
	var rLen;
	do {
		rLen = revision.length;

		revision = revision.replace("\n\n#","\n#");
	} while (revision.length != rLen);

	return(revision);
}

function init() {
	var sources_name = WH.translateEditorData['sources_name'];
	var references_name = WH.translateEditorData['references_name'];
	var steps_name = WH.translateEditorData['steps_name'];
	var translations = WH.translateEditorData['translations'];
	var remove_templates = WH.translateEditorData['remove_templates'];
	var remove_sections = WH.translateEditorData['remove_sections'];

	// Hack to remove un-needed stuff from editor
	if (typeof WH.translateEditorCheckForLL != 'undefined' && WH.translateEditorCheckForLL) {
		$("#othereditlink").hide();
		$(".editpage_links").hide();
		$("#tabs").hide();
		$('#bodycontents #editform').contents().filter(function(){
		return this.nodeType === 3;
			}).remove();
		$("#wpSave").click(function(){
			return(checkForLL() && checkForCite(sources_name, references_name) && checkForStepSpaces() && checkForSteps(steps_name));
		});
		$("#wpPreview").click(function(){
			return(checkForLL() && checkForCite(sources_name, references_name) && checkForStepSpaces() && checkForSteps(steps_name));
		});
	}

	$(".mw-newarticletext").hide();

	if (typeof WH.translateEditorTranslateURL != 'undefined' && WH.translateEditorTranslateURL) {
		$("#editform").hide();

		$('#translate_url').keypress(function (e) {
			if (e.which == 13) {
				$('#translate').click();
				return false;
			}
		});

		$("#translate").click(function() {
			var url=$("#translate_url").val();
			$("#translate_url").attr("disabled","disabled");
			$("#translate").attr("disabled","disabled");
			var re = /https?:\/\/www.wikihow.com\/(.+)/;
			var m = url.match(re);
			if (m) {
				var article = m[1];
				$.ajax({'url':"/Special:TranslateEditor",'data':{'target':article,'action':"getarticle",'toTarget': wgTitle} ,'success':function(data) {
					var jData;
					try {
						jData = JSON.parse(data);
					}
					catch(err) {
						alert("Unable to fetch article. Please make sure you are logged in to wikiHow, and your internet connection is working properly.");
						return;
					}
					if (jData.success) {
						var revision = jData.text;
						// revision = removeLinks(revision); // Disabled a per LH #1910
						for (var n in translations) {
							revision = revision.replace(new RegExp(translations[n].from,"gi"), translations[n].to);
						}
						for (var n in remove_templates) {
							revision = revision.replace(new RegExp(remove_templates[n],"gi"),"");
						}
						var sectionNames = revision.match(new RegExp("\n==[^=]+==","gi"));
						sections = revision.split(new RegExp("\n==[^=]+=="));
						revision = "";
						var first = true;
						for (var n in sections) {
							var isGood = true;
							for (var m in remove_sections) {
								if (!first && sectionNames[n-1].match(new RegExp("\n== *" + remove_sections[m] + " *==","i"))) {
									isGood = false;
								}
							}
							if (isGood) {
								if (!first) {
									revision += sectionNames[n - 1];
								}
								revision += sections[n];
							}
							else {
								if (sections[n].match(/__PARTS__/)) {
									revision += "\n__PARTS__";
								}
								match = sections[n].match(/\[\[[a-zA-Z][a-zA-Z]\:[^\[]+\]\]/g);
								for (var m in match) {
									revision += "\n" + match[m];
								}
							}
							first = false;
						}
						revision = fixSteps(revision);
						revision = fixCite(revision, sources_name, references_name);
						//add in the summary if it exists
						revision += "\n\n" + $("#intl_summary").text();
						$("#wpTextbox1").val(revision);
						//add a field to show this is a translation
						$("#wpTextbox1").after("<input type='hidden' value='translation' name='wpTranslation' />");
						$("#editform").show();
					}
					else {
						if(jData.warning) {
							$("#translation_warning").html(jData.warning);
						} else {
							alert(jData.error);
						}
						$("#translate_url").removeAttr("disabled");
						$("#translate").removeAttr("disabled");
					}
				}, error:function() {
					alert("Unable to fetch article. Please ensure your internet connection is working properly.");
				}
				});
			}
			else {
				$("#translate_url").removeAttr("disabled");
				$("#translate").removeAttr("disabled");
				alert("You must enter a url starting with http://www.wikihow.com or https://www.wikihow.com to translate");
			}
		});
	} // end if (WH.translateEditorTranslateURL)

} // end function init()

init();

} )(jQuery, mediaWiki);
