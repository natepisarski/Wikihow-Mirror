var whNewLoadFunc = function() {
	var url = "/extensions/wikihow/common/jquery.simplemodal.1.4.4.min.js";
	$.getScript(url, function() {
		$.get("/Special:ArticleCreator?ac_created_dialog=1", function(data) {
			$.modal(data, {
				zIndex: 100000007,
				maxWidth: 400,
				minWidth: 400,
				minHeight: 600,
				overlayCss: { "background-color": "#000" },
				escClose: false,
				overlayClose: false
			});
			$.getScript("/extensions/wikihow/articlecreator/ac_modal.js");
		});
	});
};
$(window).load(whNewLoadFunc);