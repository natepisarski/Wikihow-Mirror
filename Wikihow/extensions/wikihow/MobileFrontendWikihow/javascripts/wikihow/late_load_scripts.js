(function($) {
	window.WH = window.WH || {};
	window.WH.lateLoadScript = {

		//make search placeholders disappear when the user clicks in
		//(instead of the standard behavior where it stays when the input is empty)
		placeholderToggle: function() {
			var placeholderVal = $('#search_footer .search_box').attr('placeholder');

			$('#search_footer .search_box').focus(function () {
			  $(this).attr('placeholder', '');
			});

			$('#search_footer .search_box').blur(function () {
			  $(this).attr('placeholder', placeholderVal);
			});
		},

		initPage: function() {
			$('#article_rating_mobile').show();
			$('#footer_random_button').show();
			this.placeholderToggle();
		}
	};

	WH.lateLoadScript.initPage();
})(jQuery);
