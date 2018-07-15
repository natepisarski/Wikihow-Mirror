(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminExpertNameChange = {
		tool: '/Special:AdminExpertNameChange',
		init: function() {
			$(document).on("submit", "#enc_form", function(e){
				e.preventDefault();
				var oldId = $("#enc_oldname").val();
				var newName = $("#enc_newname").val();
				if(oldId > 0 && newName != "") {
					$.post(WH.AdminExpertNameChange.tool, $(this).serialize(), function (result) {
						$("#enc_form").hide();
						$("#enc_change_new").html($("#enc_newname").val());
						$("#enc_changel_old").html($("#enc_oldname option:selected").text());
						$("#enc_result").show();
					}, "json");
				}
			});
		},
	};
	$(document).ready(function() {
		WH.AdminExpertNameChange.init();
	});
})();
