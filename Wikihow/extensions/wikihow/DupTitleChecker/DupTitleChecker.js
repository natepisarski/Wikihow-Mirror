( function ( mw, $ ) {
	window.WH.loadfile = {
		init: function () {
				$("#dedup_form").on("submit", function(){
					var data = $("#dedup_form").serialize();
					$.post($("#dedup_form").attr("action"), data).done(function(){
						$("#deduptool_message").show();
					});
					return false;
			 });
		}
	};
	$( function() {
		 WH.loadfile.init();
	 });
}( mediaWiki, jQuery ) );
