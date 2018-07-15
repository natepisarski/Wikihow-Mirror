<style type="text/css">
button {
	font-size: 16px;
	padding: 0.5em;
}
#new-prob {
	font-size: 16px;
	padding: 0.5em;
}

p {
	padding: 5px;
}

h3 {
	padding: 5px;
	color: #666;
}

#url-list table { width: 100%; }
#url-list td {
	background-color: #EEE;
	padding: 5px;
}
#url-list td.x { text-align: center; }

#cs-edit, #cs-add {
    box-sizing: border-box;
}

#new-key {
	font-size: 16px;
	padding: 5px;
}

.admin-result {
	background-color: #eee;
}

#reload-page {
	display: none;
}

#article-list-notice {
	display:none;
	font-style: italic;
}

#edit-restriction {
	font-weight: bold;
}
</style>



<? if ($style == 'url'): ?>
	<h2>URL Config Editor</h2>
<? endif; ?>

<div id="#cs-edit">
	<form method='post' action='/Special:<?= $specialPage ?>'>
	<span>Select the tag or config data you want to edit.</span><br>
	<br>
	<select id='config-key'>
		<option value=''>--</option>
	<? if ($bURL): ?>
		<option value="wikihow-watermark-article-list">wikihow-watermark-article-list</option>
		<option value="wikiphoto-article-exclude-list">wikiphoto-article-exclude-list</option>
		<option value="wikihow-methodtoc-test-01">wikihow-methodtoc-test-01</option>
		<option value="wikihow-methodtoc-test-02">wikihow-methodtoc-test-02</option>
		<option value="wikihow-nointro-test">wikihow-nointro-test</option>
	<? else: ?>
		<? foreach ($configs as $config): ?>
			<option value='<?= $config ?>'><?= $config ?></option>
		<? endforeach; ?>
	<? endif; ?>
	</select><br>
	</form>
</div>

<div id="edit-existing" style="display:none">
	<br>
	<? if ($bURL) echo '<b>Add new:</b>'; ?>
	<div>
		<p id="edit-restriction"></p>
		<textarea id='config-val' type='text' rows='20' cols='70'></textarea>
		<p id="article-list-notice">This is an <b>article list</b>. It can be a list of article IDs, article names, or URLs. You will be warned if an
			article could not be found. Article lists are more efficient than non-article list config messages because they are parsed when they are
			saved by this console.</p>
		<p class="display-prob">A/B probability (optional): <input id="change-prob" name="change-prob" type="number"/><br></p>
	</div>
	<div>
		<button id='config-save' disabled='disabled'>save</button> &nbsp; &nbsp; &nbsp; &nbsp;
		<button id='config-delete' disabled='disabled'>delete</button><br>
		<br>
	</div>
	<div class='admin-result change-result'></div>
	<div id='url-list'></div>
	<input type='hidden' id='display-style' value='<?=$style?>' />
</div>

<div id="cs-add">
	<br>
	Or, create a new tag. <a id="create-new-link" href="#">Add one now</a>.<br>
</div>

<div id="add-new" style="display:none">
	<br>
	<p>New tag name: <input id="new-key" name="new-key" type="text" size="30" /><br></p>
	<p><label><input id="is-article-list" name="is-article-list" type="checkbox" checked="checked" /> Is this an article list?</label>
		(<a id="article-explain" href="#">explain</a>)<br></p>
	<p class="display-prob">A/B probability (optional): <input id="new-prob" name="new-prob" type="number"/><br></p>
	<br>
	<div>
		<textarea id='config-val-new' type='text' rows='20' cols='70'></textarea>
	</div>
	<button id='config-create' disabled='disabled'>save</button> &nbsp; &nbsp; &nbsp; &nbsp;<a id="config-create-cancel" href="#">cancel</a><br>
	<br>
	<div class='admin-result add-result'></div>
	<div>
		<a id="reload-page" href="#">refresh page</a><br>
	</div>
</div>

<div id="dialog-box" title="ROSKOMNADZOR informs">
	<h3>Article lists</h3>
	<p>Messages that are a list of wikiHow articles should always be saved as an <b>article list</b>. Article lists are treated
	more efficiently than basic lines of text that must be parsed with every server request. Article lists are more efficient because
	the list name is treated as a <b>tag</b> that loaded with all other articles tags once per request.</p>
	<h3>Examples of article lists</h3>
	<ul>
		<li>Article IDs, one per line, describing which articles to exclude from a particular feature.</li>
		<li>Article URLs, one per line, that are part of a new test feature. If this feature is
		  part of an A/B test, you can set the probability that the A variant is displayed.</li>
	</ul>
	<br>
	<h3>Examples of things that shouldn't be article lists</h3>
	<ul>
		<li>A list of usernames who have access to a fancy new feature.</li>
		<li>Categories from which a new tool shouldn't fetch articles.</li>
		<li>Nordic gods not significant enough to have their own page on Wikipedia.</li>
		<li>Collections of lesser known facts about the pets of Bebeth and Scott.</li>
	</ul>
	<br>
	<h3>Avoid non-article lists</h3>
	<p>Note that this latter category is treated less efficiently by the system, so care should be
	  taken that messages like these are not used in the normal flow of serving average requests on
	  wikiHow. These message are inefficient because they must be requested serially from memcache or database, and they are
	  usually parsed with every request. Consider using a more efficient format such as: keeping an
	  array of values in php code, using a Mediawiki message if the data is small, or using a new table if the
	  data is large.</p>
</div>



<script>
//remove a url from the list
$('body').on('click', 'a.remove_link', function() {
	var rmvid = $(this).attr('id');
	$(this).hide();
	$.post('/Special:<?= $specialPage ?>',
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

(function($) {
	$(document).ready( function() {
		$('#config-key')
			.change( function () { // when user changes selection in dropdown
				var configKey = $('#config-key').val();
				var dispStyle = $('#display-style').val();
				$('#edit-existing').show();
				if (configKey) {
					$('.change-result').html('loading ...');
					$.ajax({
						url: '/Special:<?= $specialPage ?>',
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
				$.post('/Special:<?= $specialPage ?>',
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
				$.post('/Special:<?= $specialPage ?>',
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
				$.post('/Special:<?= $specialPage ?>',
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
	});
})(jQuery);
</script>
