(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.ajaxInfo = {

		init : function() {
			$("body").on("click","a.ajax-info",showInfo);
		}
	};

	function showInfo(event) {
		event.preventDefault();
		$("#info-modal").modal("show").find(".modal-body").load($(event.currentTarget).attr("href"));
	}

}());
