( function ( mw, $ ) {
	window.WH = window.WH || {};

	WH.adminexpertdoc = (function() {
		var toolUrl = '/Special:AdminExpertDoc';
		var disabled = false;
		var lastResult = null;

		spinner = $.createSpinner( {
			size: 'small',
			 type: 'inline'
		} );

		function displayResult(result) {
			lastResult = result; 

			if (!result || !result['data']) {
				return;
			}


			var ed_file = $('<div id="ed_file_head" class="ed_file"></div>');
			ed_file.append('<div class="ed_result_item ed_result_head ed_file_title">Doc Title</div>');
			ed_file.append('<div class="ed_result_item ed_result_head ed_link_title">Link</div>');
			//ed_file.append('<div class="ed_result_item ed_result_head">ID</div>');
			ed_file.append('<div class="ed_result_item ed_result_head ed_status">status</div>');
			$('#ed_results').append(ed_file);
			$('#ed_results').append('<div style="clear:both"></div>');

			result['data'].forEach(function(entry) {
				ed_file = $('<div class="ed_file"></div>');

				var comment = entry['description'];

				var ed_title = $('<div class="ed_file_title ed_result_item"></div>');
				ed_title.text(entry['title']);
				ed_file.append(ed_title);

				var ed_link = $('<a target="_blank" class="ed_link ed_result_item"></a>');
				ed_link.text(entry['alternateLink']);
				ed_link.attr('href', entry['alternateLink']);
				ed_file.append(ed_link);

				//var ed_file_id = $('<div class="ed_file_id ed_result_item"></div>');
				//ed_file_id.text(entry['id']);
				//ed_file.append(ed_file_id);

				if (entry['error']) {
					var ed_status = $('<div class="ed_status ed_result_item"></div>');
					ed_status.text(entry['error']);
					ed_file.append(ed_status);
				} else if (entry['status']) {
					var ed_status = $('<div class="ed_status ed_result_item"></div>');
					ed_status.text(entry['status']);
					ed_file.append(ed_status);
				} else {
					var ed_status = $('<div class="ed_status ed_result_item"></div>');
					$('.ed_result_head.ed_status').text('Name');
					ed_status.text(entry['description']);
					ed_file.append(ed_status);
				}

				$('#ed_results').append(ed_file);
				$('#ed_results').append('<div style="clear:both"></div>');
			});
		}

		function disableInputs() {
			disabled = true;
			$('#ed_results').html('');
			$('.ed_button').css("cursor", "default");
			$('.ed_button').css("background-color", "#83a168");

			$('#ed_results').before(spinner);
		}

		function enableInputs() {
			disabled = false;
			spinner.remove();
			$('.ed_button').css("cursor", "pointer");
			$('.ed_button').css("background-color", "#97ba78");
		}

		function handleClick(action) {
			if (disabled == true) {
				return;
			}

			disableInputs();

			var name = $('#ed_name').val();
			var articles = $('#ed_articles').val().split('\n');
			var images = $('#images_checkbox').is(':checked')
			var data = { action: action,
						 articles: articles,
						 images: images,
						 name: name };


			$.post(toolUrl, data, function (result) {
				debugResult(result);
				enableInputs();
				displayResult(result);
			}, "json").fail( function(xhr, textStatus, errorThrown) {
				//$('#ed_results').html("<p>the import failed</p>");
				$('#ed_results').append(xhr.responseText);
				$('#ed_results').wrap("<pre></pre>");
				enableInputs();
			});
			return false;
		}

		function setupClickHandling() {
			$('#ed_advanced_checkbox').on('change', 'input[type=checkbox]', function(e) {
				//console.log(this.name+' '+this.value+' '+this.checked);
				if (this.checked) {
					$('.ed_button_advanced').show();
				} else {
					$('.ed_button_advanced').hide();
				}
			});

			$('#ed_csv').on('click', function (e) {
				e.preventDefault();
				if (!lastResult || !lastResult['data']) {
					return;
				}

				var data = [["title", "link", "name"]];

				lastResult['data'].forEach(function(entry) {
					var link = entry['alternateLink'];

					var comment = entry['description'];
					if (!comment) {
						comment = ' ';
					}

					data.push([entry['title'], link, comment]);
				});

				var csvContent = "data:text/csv;charset=utf-8,";
				data.forEach(function(infoArray, index){
					var dataString = infoArray.join(",");
					csvContent += index < data.length ? dataString+ "\n" : dataString;
				});
				var encodedUri = encodeURI(csvContent);
				window.open(encodedUri);
			});

			$('.edbs').on('click', function (e) {
				e.preventDefault();
				var id = $(this).attr('id');
				handleClick(id);
			});
		}


		function debugResult(result) {
			// adds debugging log data to the debug console if exists
			if (WH.consoleDebug) {
				WH.consoleDebug(result['debug']);
			}
		}
		return {
			init : function() {
				setupClickHandling();
			},
		};
	}());

	$( function() {
		WH.adminexpertdoc.init();
	});
}( mediaWiki, jQuery ) );

