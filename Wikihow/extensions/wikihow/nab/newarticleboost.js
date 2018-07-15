if (typeof console == "undefined" || typeof console.log == "undefined") {
	var console = { log: function() {} };
};

window.WH = window.WH || {};
window.WH.nab = window.WH.nab || {};

window.WH.nab.preview = false;
window.WH.nab.needToConfirm = true;

window.WH.nab.editClick = function(url) {
	var strResult;
	window.WH.nab.editUrl = url;
	$("#quickedit_contents")
		.html("<b>Loading...</b>")
		.attr("onDblClick", "");
	$("#article_contents").hide();
	$(".editButton").hide();

	// make sure this can"t be clicked twice
	if ($("#quickedit_contents").find("textarea").length > 0) {
		return false;
	}

	// document.write() call happens in the REST call below, so
	// we hack to override this
	document.write = function() {};

	$.get(url, function (data) {
		$("#quickedit_contents").html(data);
		$(".templatesUsed").parent().remove();
		$("#wpPreview")
			.unbind("click")
			.click( function() {
				window.WH.nab.preview = true;
			});
		$("#wpSave")
			.unbind("click")
			.click( function() {
				window.WH.nab.preview = false;
			});
		$("#mw-editform-cancel").on("click", function(){
			if(confirm("Are you sure you want to cancel? You will lose any changes.")) {
				$("#quickedit_contents").hide();
				$("#article_contents").show();
			}
			return false;
		});
		document.editform.setAttribute("onsubmit", "return window.WH.nab.submitEditForm();");
		document.editform.wpTextbox1.focus();
		$("#wpSummary").val(mw.message("nap_autosummary").text());
		window.onbeforeunload = window.WH.nab.confirmExit;
	});

	return false;
}

window.WH.nab.submitEditForm = function() {
	var parameters = "";
	for (var i=0; i < document.editform.elements.length; i++) {
		var element = document.editform.elements[i];
		if (parameters != "") {
			parameters += "&";
		}

		if ( (element.name == "wpPreview" && window.WH.nab.preview) || (element.name == "wpSave" && !window.WH.nab.preview)) {
			parameters += element.name + "=" + encodeURIComponent(element.value);
		} else if (element.name != "wpDiff" && element.name != "wpPreview" && element.name != "wpSave" && element.name.substring(0,7) != "wpDraft")  {
			if (element.type == "checkbox") {
				if (element.checked) {
					parameters += element.name + "=1";
				}
			} else {
				parameters += element.name + "=" + encodeURIComponent(element.value);
			}
		}
	}

	$.post(window.WH.nab.editUrl + "&action=submit", parameters,
		function (data) {
			if (window.WH.nab.preview) {
				$("#quickedit_contents")
					.html(data)
					.attr("style", "");
				var previewButton = document.getElementById("wpPreview");
				previewButton.setAttribute("onclick", "window.WH.nab.preview=true;");
				var saveButton = document.getElementById("wpSave");
				saveButton.setAttribute("onclick", "window.WH.nab.preview=false;");
				document.editform.setAttribute("onsubmit", "return window.WH.nab.submitEditForm();");
				document.editform.wpTextbox1.focus();
			} else {
				$("#article_contents")
					.html(data)
					.attr("style", "");
				window.WH.nab.getDiffLink();
				$("#quickedit_contents").hide();
			}

		});
	window.onbeforeunload = null;

	return false; // block sending the forum
}

window.WH.nab.submitNabForm = function() {
	window.WH.nab.disableButtons();

	var form = $("#nap_form");
	$.ajax({
		type: form.attr("method"),
		url: form.attr("action"),
		data: form.serialize(),
		dataType: "json",
		error: function(jqXHR, textStatus, errorThrown) {
			alert(JSON.parse(jqXHR.responseText).message);
		}
	});

	window.WH.nab.loadNextArticle();

	return false;
}

window.WH.nab.loadNextArticle = function() {
	var nextUrl = $("#nextNabUrl").val();
	var nextTitle = $("#nextNabTitle").val();
	$("#nab-article-container").load(nextUrl + "&noSkin=1", function(response, status, xhr) {
		if (status == "error") {
			if (nextUrl === "" && nextTitle === "") {
				alert("It looks like we're out of articles!");
			} else {
				alert("Couldn't load the next article (" + nextUrl + ")");
			}
			return;
		}
		window.scrollTo(0, 0);
		window.history && window.history.pushState({}, "", nextUrl);
		document.title = nextTitle;
		window.WH.nab.enableButtons();
	});
}

window.WH.nab.merge = function(title) {
	if( !$(this).hasClass("clickfail") ) {
		$("#nap_demote").val("nap_demote");
		$("#template3_merge").val("on");
		$("#param3_param1").val(title.replace(/&#39;/, "'"));
		window.WH.nab.submitNabForm();
		window.WH.nab.disableButtons();
	}
}

window.WH.nab.confirmExit = function() {
	if (window.WH.nab.needToConfirm) {
		return mw.message("all-changes-lost").text();
	}
	return "";
}

window.WH.nab.getDiffLink = function() {
	var target = document.nap_form.target.value;
	var url = "/api.php?action=query&prop=revisions&titles=" + encodeURIComponent(target) + "&rvlimit=20&rvprop=timestamp|user|comment|ids&format=json";
	var pageid = document.nap_form.page.value;
	$.get(url,
		function (data) {
			var first = data.query.pages[pageid].revisions[0].revid;
			var last = null;
			for (i = 1; i < data.query.pages[pageid].revisions.length; i++) {
				var rev = data.query.pages[pageid].revisions[i];
				if (rev.user != wgUserName) {
					last = rev.revid;
					break;
				}
			}
			$("#article_contents").append("<center><b><a href='/index.php?title=" +  encodeURIComponent(target) + "&diff=" + first + "&oldid=" + last +"' target='_blank'>Link to Diff</a></center></b>");
		},
		"json");
}

window.WH.nab.disableButtons = function() {
	$(".arrow_box .button, #nap_duplicates .button").addClass("clickfail wait");
}

window.WH.nab.enableButtons = function() {
	$(".arrow_box .button, #nap_duplicates .button").removeClass("clickfail wait");
}

$(document).ready(function() {

	if(document.nap_form) {
		$('body').data({
			event_type: 'nab',
			article_id: document.nap_form.page.value
		});
	}

	if (mw.config.get("isArticlePage")) {

		$(window).on("popstate", function(e) {
			if (e.originalEvent.state) {
				window.location.reload();
			}
		});

		window.history && window.history.replaceState({}, "", window.location.href);

		var container = $("#nab-article-container");
		container.on("click", "#nap_skip_btn", function(){
			if( !$(this).hasClass("clickfail") ) {
				$("#nap_skip").val("nap_skip");
				window.WH.nab.submitNabForm();
			}
		});

		container.on("change", "#nap_delete", function(){
			// They've clicked the delete button and selected a reason
			if( !$(this).hasClass("clickfail") ) {
				WH.usageLogs.log({
					event_action: 'nfd',
					nfd_type: $('#nap_delete option:selected').val()
				});
				$("#template1_nfd").val("on");
				$("#nap_demote").val("nap_demote");
				window.WH.nab.submitNabForm();
			}
		});

		container.on("click", "#nap_promote", function(){
			if( !$(this).hasClass("clickfail") ) {
				$("#nap_submit").val("nap_submit");
				window.WH.nab.submitNabForm();
			}
		});

		container.on("click", "#nap_star", function(){
			if( !$(this).hasClass("clickfail") ) {
				$("#nap_submit").val("nap_submit");
				$("#cb_risingstar").val("on");
				window.WH.nab.submitNabForm();
			}
		});

		container.on("click", "#nap_demote_btn", function(){
			if( !$(this).hasClass("clickfail") ) {
				$("#nap_demote").val("nap_demote");
				window.WH.nab.submitNabForm();
			}
		});

		$(window).scroll(function() {
			$("#nap_header").css("top",$("#header").height());

			if($(window).scrollTop() <= 0) {
				$("#nap_header").css("top", "99px");
			}
		});
	} else {
		$("#nap_low").on("change", function(){
			window.location = $("#low_url").val();
		});
	}

});
