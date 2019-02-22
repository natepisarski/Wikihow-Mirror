(function($) {

var BASE_URL  = '/Special:AdminTags';

//remove a url from the list
$('body').on('click', 'a.remove_link', function() {
    var rmvid = $(this).attr('id');
    $(this).hide();
    $.post(BASE_URL,
        { 'action': 'remove-line',
          'config-key': $('#config-key').val(),
          'id': rmvid },
        function(data) {
            if (data['error'] != '') {
                alert('Error: ' + data['error']);
            }
            $('#url-list').html(data['result']);
        },
        'json');
    return false;
});

$(document).ready( function() {
	$('#config-key')
		.change( function () { // when user changes selection in dropdown
			var configKey = $('#config-key').val();
			var dispStyle = $('#display-style').val();
			$('#edit-existing').show();
			if (configKey) {
				$('.change-result').html('loading ...');
				$.ajax({
					url: BASE_URL,
					type: 'POST',
					dataType: 'json',
					timeout: 50000,
					data:
					{ 'action': 'load-config',
					  'config-key': configKey,
					  'style': dispStyle},
					success: function (data) {
						$('.change-result').html('');
						if (data && typeof data['restriction'] != 'undefined' && data['restriction']) {
							$('#edit-restriction').html(data['restriction']);
						} else {
							$('#edit-restriction').html('');
						}

						if (dispStyle == 'url') {
							$('#url-list').html(data['result']);
							$('#config-val').val('');
						} else {
							$('#config-val')
								.val(data['result'])
								.focus();
						}

						$('#config-save').prop('disabled', '');
						$('#config-delete').prop('disabled', '');

						if (data && typeof data['article-list'] != 'undefined' && data['article-list']) {
							$("#article-list-notice").show();
							var prob = '';
							if (data['prob'] && parseInt(data['prob'], 10) > 0) {
								prob = data['prob'];
							}
							$("#change-prob").val(prob);
							$(".display-prob").show();
						} else {
							$("#article-list-notice").hide();
							$(".display-prob").hide();
						}
					}
				});
			} else {
				$('#config-val').val('');
			}

			return false;
		} );

	$('#config-save')
		.click( function () { // When an existing message is saved again
			var dispStyle = $('#display-style').val();
			$('.change-result').html('saving ...');
			$.post(BASE_URL,
				{ 'action': 'save-config',
				  'config-key': $('#config-key').val(),
				  'config-val': $('#config-val').val(),
				  'prob': $('#change-prob').val(),
				  'style': dispStyle },
				function(data) {
					$('.change-result').html(data['result']);
					if (dispStyle == 'url') {
						$('#url-list').html(data['val']);
						$('#config-val').val('');
					} else {
						$('#config-val')
							.val(data['val'])
							.focus();
					}
				},
				'json');
			return false;
		} );

	$('#config-delete')
		.click( function() { // when a message is deleted
			var configKey = $('#config-key').val();
			var c = confirm("You have chosen to permanently delete the tag '" + configKey + "'. If this tag is still used in production code, it could cause problems. Do you want to continue?");
			if (!c) return;

			$('.change-result').html('deleting ...');
			$.post(BASE_URL,
				{ 'action': 'delete-config',
				  'config-key': configKey },
				function(data) {
					$('.change-result').html(data['result']);
					$('#config-val').val('');
					$('#config-save').prop('disabled', 'disabled');
					$('#config-delete').prop('disabled', 'disabled');
				},
				'json');
			return false;
		} );

	$('#config-create')
		.click( function() { // When a new message is saved
			var newKey = $('#new-key').val();
			if (newKey.trim().length == 0) {
				alert('You must specify a key');
				$('#new-key').focus();
				return;
			} else if (newKey.length > 64) {
				alert('Key must be fewer than 64 characters: ' + newKey);
				return;
			}
			var prob = parseInt( $('#new-prob').val(), 10 );
			if ( isNaN(prob) || prob == 0 ) {
				prob = "";
			} else if (prob < 0 || prob > 99) {
				alert("Illegal probability entered: " + prob + ". Must be between 0 and 99 inclusive.");
				return;
			} else if (prob < 50) {
				var c = confirm("You selected a probability of < 50%. It is more efficient to have the A variant showing in the majority case. Do you want to continue?");
				if (!c) return;
			}

			$('.add-result').html('saving ...');
			$.post(BASE_URL,
				{ 'action': 'create-config',
				  'new-key': newKey,
				  'is-article-list': $('#is-article-list').is(':checked'),
				  'new-prob': $('#new-prob').val(),
				  'config-val-new': $('#config-val-new').val() },
				function(data) {
					$(".add-result").css("padding", "10px");
					if (!data) {
						$(".add-result").html("Error: did not receive properly formed response from the server.");
						return;
					}
					if (data['error']) {
						var errStr = data['error'].replace(new RegExp("\n", 'g'), "<br>");
						$(".add-result").html("Error:<br>" + errStr);
						return;
					}
					if (data && data['result']) {
						$('.add-result').html(data['result']);
						$('#reload-page').show();
						return;
					}
					$(".add-result").html("Error: result from server was not understood.");
				},
				'json');
			return false;
		} );

	$('#reload-page')
		.click( function() {
			location.href = location.href;
			return false;
		} );

	$('#config-val')
		.keydown( function () {
			$('#config-save').prop('disabled', '');
		} );

	$('#config-val-new')
		.keydown( function () {
			$('#config-create').prop('disabled', '');
		} );

	$('#create-new-link')
		.click( function() {
			$('#add-new').show();
			$('#new-key').focus();
			return false;
		} );

	$('#config-create-cancel')
		.click( function() {
			$('#add-new').hide();
			return false;
		} );

	$('#article-explain')
		.click( function() {
			$('#dialog-box').dialog({
				width: 600
			});
			return false;
		} );

	$('#is-article-list')
		.change( function() {
			//if ( $(this).is(':checked') ) {
			$('.display-prob').animate({height: 'toggle'});
		} );

	$('.csh-view')
		.click( function() {
			var csh_id = $(this).data('cshid');
			$.post(BASE_URL,
				{ 'action': 'csh-history',
				  'cshid': csh_id },
				function(data) {

					$('.csh-key').html(data['csh_key']);
					$('.csh-summary').html(data['csh_summary']);
					$('.csh-changes').html(data['csh_changes']);

					$('#csh-details-dialog-box').dialog({
						width: 800
					});
				},
				'json');
			return false;
		} );
});

})(jQuery);
