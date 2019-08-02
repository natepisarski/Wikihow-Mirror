(function($, mw) {

$('.user_widget').hide();
$('.de_user a').mouseover( function() {
	var username = this.title;
	$.ajax( {
		url: '/Special:UserStaffWidget',
		type: 'GET',
		data: {'user_name':username},
		success: function(data) {
			$('#sidebar .user_widget').html(data).show();
		}
	} );
} );
$('.user_widget').css('position','fixed');
$('.user_widget').css('top','80px');
$('.user_widget').css('z-index','50');

})(jQuery, mediaWiki);
