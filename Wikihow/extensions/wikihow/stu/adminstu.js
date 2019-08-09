(function($) {

function doServerAction(action) {
	var dataType = $('input:radio[name=data-type]:checked').val();
	var url = '/Special:Stu/views.csv?action=' + action + '&data-type=' + dataType;
	if ('summary' == dataType) {
		var form = $('#admin-form').serializeArray();
		$('#pages-result').html('loading ...');
		var finished = false;
		$.post(url,
			form,
			function(data) {
				finished = true;
				if (!data) {
					$('#pages-result').html('Received no response');
				} else if (typeof data['err'] == 'string') {
					$('#pages-result').html('Received error:<br>' + data['err']);
				} else {
					$('#pages-result').html(data['result']);
					$('#pages-list').focus();
				}
			},
			'json')
			.complete(function (xhr) {
				if (!finished) {
					$('#pages-result').html('Server call is taking too long. Wait for an email.<br><br>debug info, just in case: ' + xhr.responseText);
				}
			});
	} else { // csv
		var form = 'pages-list=' + encodeURIComponent($('#pages-list').val());
		$.download(url, form);
	}
}

function init() {
	$('#pages-resetbt, #pages-resetmb, #pages-reset, #pages-fetch')
		.prop('disabled', false)
		.click(function () {
			var action = $(this).attr('id').replace(/^pages-/, '');
			var answer = true;
			if ('reset' == action.substring(0,5)) {
				var count = $('#pages-list').val().split(/\\n/).length;
				var domain = 'www';
				if ('resetmb'==action) domain='mobile';
				else if ('reset'==action) domain='all domains';
				answer = confirm('Are you sure you want to reset data for approx. ' + count + ' URL(s) on ' + domain + '?');
			}
			if (answer) {
				doServerAction(action);
			}
			return false;
		});
	/*
	$('#pages-allcheck')
		.click(function() {
			if ($(this).prop('checked')){
				$('#pages-reset').prop('disabled',false);
			}else{
				$('#pages-reset').prop('disabled',true);
			}
		});
	$('#pages-domains')
		.change(function(){
			if ($(this).attr('value')=='all'){
				$('#pages-reset').prop('disabled',true);
				$('#pages-allcheck').prop('disabled',false);
				$('#pages-check').css('color','');
			}else{
				$('#pages-reset').prop('disabled',false);
				$('#pages-allcheck').prop('disabled',true).prop('checked',false);
				$('#pages-check').css('color','#ccc');
			}
			$('#pages-result').html('');
		});
	*/

	$('#pages-list')
		.focus();
}

init();

})(jQuery);
