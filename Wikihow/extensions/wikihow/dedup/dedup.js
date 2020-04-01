(function() {
	$(document).ready(function() {
		$("#dedup_form").on("submit", function(){
			if($("#internalDupTool:checked").length > 0) {
				var data = $("#dedup_form").serialize();
				$.post($("#dedup_form").attr("action"), data).done(function(){
					$("#deduptool_message").show();
				});
				return false;
			}
		});
	});
})();
