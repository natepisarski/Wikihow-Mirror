jQuery.extend(WH, (function($) {
	function APIAppAdmin() {
		var toolURL = "/Special:APIAppAdmin";
		this.init = function() {
			console.log("init APIAppAdmin");
		}
		$('#apiapp_submit').click(function(e) {
			e.preventDefault();
			var input = $('#apiapp_input').val();
			var action = "default";
			$.post(toolURL, {
				input:input,
				action:action
				},
				function (result) {
					debugResult(result);
				},
				'json'
			);
		});
		function debugResult(result) {
			console.log("debug: ");
			for (i in result['debug']) {
				console.log(result['debug'][i]);
			}
		}
	}
	$(document).ready(function() {
		var apiAppAdmin = new APIAppAdmin();
		apiAppAdmin.init();
	});
})(jQuery));
