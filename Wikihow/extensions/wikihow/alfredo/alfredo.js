(function($) {

$(document).on( 'click', "#btn", function() {
	var langs = "";
	var urls = $("#urls").val();
	$('.lang_checkbox').each(function(e) {
		if (this.checked) {
			if (langs != "") {
				langs = langs + ',';	
			}
			langs = langs + this.id;
		}
	});
	$.download('/' + wgPageName, {'langs':langs,'urls':urls});
} );

})(jQuery);
