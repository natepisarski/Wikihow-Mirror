(function($) {
	var controller = '/' + wgPageName;

	$(document).ready(function() {
		$("#tags").chosen({no_results_text: "No results matched. <a href='#' class='add_tag'>add tag</a>"});
		$("table.wap").tablesorter();
		$("#users").chosen({no_results_text: "No results matched."});
		$("#list_filter").chosen({no_results_text: "No results matched.", allow_single_deselect: true});

		$('#list_filter').chosen().change(function() {
			filterList();
		});
	});

	 $(document).on('keyup', '.urls', function(e) {
		var newLines = $(this).val().split("\n").length;
		if (newLines > 3001) {
			alert('System can only process 3,000 urls at a time');
			$('.urls').val('');
		}
	});

	$(document).on('click',
		'#validate_complete_articles,#validate_remove_articles,#validate_assign_user,#validate_tag_articles,#validate_release_articles,#validate_notes_articles',
		function(e) {
		e.preventDefault();
		validate($(this).attr('id'));
	});


	$(document).on('click', '#remove_articles,#release_articles', function(e) {
		e.preventDefault();
		if (hasCheckedAids()) {
			var data = {'a' : $(this).attr('id'), 'aids' : getCheckedAids() };
			setLoading();
			$.post(controller, data, function(res) {
				$('#results').html(res);
			});
		}
	});

	$(document).on('click', '#removeTags', function(e) {
		e.preventDefault();
		var data = {'urls' : $('.urls').val(), 'a' : 'remove_tag_articles', 'tags' : getSelectedTags()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#clearNotes', function(e) {
		e.preventDefault();
		var data = {'urls' : $('.urls').val(), 'a' : 'clear_notes_articles'};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#csvNotes', function(e) {
		e.preventDefault();
		var data = {'csv' : $('#csv').val(), 'a' : 'add_csv_notes_articles'};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#remove_tag_system', function(e) {
		e.preventDefault();
		var data = {'a' : 'remove_tag_system', 'tags' : getSelectedTags()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#deactivate_tag_system', function(e) {
		e.preventDefault();
		var data = {'a' : 'deactivate_tag_system', 'tags' : getSelectedTags()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#activate_tag_system', function(e) {
		e.preventDefault();
		var data = {'a' : 'activate_tag_system', 'tags' : getSelectedTags()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	function setLoading() {
		$('#results').html('<h4>thinking...</h4>');
	}


	function validate(action) {
		var data = {'urls' : $('.urls').val(), 'a' : action};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	}

	$(document).on('click', '.add_tag', function(e) {
		e.preventDefault();
		var tag = $(this).parents('.select_container').find('.search-field input[type=text]').val();
		if (tag.length < 2 || tag.length > 200) {
			alert('Tag names must be between 2 and 200 characters in length');
			return;
		}

		if (tag.match(/[\?\\,\"\'\/\-]/) != null) {
			alert('Tag names cannot contain any of the following characters: \' - ? \\ / " , !');
			return;
		}

		var optVal = escape('-1,' + tag);
		$('.tags').append($("<option></option>").attr("value", optVal).attr("selected", "selected").text(tag));
		$('.tags').trigger('liszt:updated');
	});

	// Get article details if they hit enter in text box
	$(document).on('keyup', '#url', function(e) {
		if(e.keyCode == 13){
			$("#article_details").click();
		}
	});

	$(document).on('click', '#article_details', function (e) {
		var data = {'url' : $('#url').val(), 'a' : 'article_details'};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		}).fail(function(xhr) {
			$('#results').html('<b>Error</b>: ' + xhr.statusText +
				'<br/>' + xhr.responseText);
		});
	});

	$(document).on('click', '#add_user', function (e) {
		var data = {'url' : $('#url').val(), 'a' : 'add_user', 'powerUser' : $('#powerUser').is(':checked')};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#tag_articles', function (e) {
		e.preventDefault();
		if (hasCheckedAids()) {
			var data = {'a' : $(this).attr('id'), 'tags' : getSelectedTags(), 'aids' : getCheckedAids()};
			setLoading();
			$.post(controller, data, function(res) {
				$('#results').html(res);
			});
		}
	});

	$(document).on('click', '#add_notes_articles', function (e) {
		e.preventDefault();
		if (hasCheckedAids()) {
			var data = {'a' : $(this).attr('id'), 'notes' : $('#notes').val(), 'aids' : getCheckedAids()};
			setLoading();
			$.post(controller, data, function(res) {
				$('#results').html(res);
			});
		}
	});

	// Return aids in an associative array grouped by langCode
	function getCheckedAids() {
		var aids = {};
		$.each($('.checked_article:checked'), function(i, val) {
			var aid = $(val).attr('value');
			var langCode = $(val).attr('langcode');
			if (!$.isArray(aids[langCode])) {
				aids[langCode] = [];
			}
			aids[langCode].push(unescape(aid));
		});

		return aids;
	}

	function hasCheckedAids() {
		var result = true;
		if ($('.checked_article:checked:enabled').length == 0) {
			alert('No articles checked.  Please select at least one article');
			result = false;
		}
		return result;
	}

	function getSelectedTags() {
		var tags = [];
		$.each($('#tags option:selected'), function(i, val) {
			val = $(val).attr('value');
			tags.push(unescape(val));
		});

		return tags;
	}

	$(document).on('click', '#complete_articles,#assign_user', function(e) {
		e.preventDefault();
		if (hasCheckedAids()) {
			var data = {'a' : $(this).attr('id'), 'user' : $('#users').val(), 'aids' : getCheckedAids()};
			setLoading();
			$.post(controller, data, function(res) {
				$('#results').html(res);
			});
		}
	});

	function getSelectedUsers() {
		var users = [];
		$.each($('#users option:selected'), function(i, val) {
			val = $(val).attr('value');
			users.push(unescape(val));
		});
		return users;
	}

	$(document).on('click', '#tag_users', function (e) {
		e.preventDefault();
		var data = {'urls' : $('.urls').val(), 'a' : 'tag_users', 'tags' : getSelectedTags(), 'users' : getSelectedUsers()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#remove_users', function (e) {
		e.preventDefault();
		var data = {'a' : 'remove_users', 'users' : getSelectedUsers()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#deactivate_users', function (e) {
		e.preventDefault();
		var data = {'a' : 'deactivate_users', 'users' : getSelectedUsers()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '#v_check_assigned,#v_check_completed,#v_check_unassigned,#v_check_new', function(e) {
		var isChecked = $(this).is(':checked');
		var langCode = $(this).parent().parent().attr('id');
		selector = 'div#' + langCode + ' .' + $(this).attr('id');
		$.each($(selector), function(i, checkbox) {
			$(checkbox).attr('checked', isChecked);
		});
	});

	$(document).on('click', '#assign', function(e) {
		e.preventDefault();
		var data = {'urls': $('.urls').val(), 'a' : 'assign_user', 'users' : $('#user').val()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('click', '.complete', function(e) {
		e.preventDefault();
		var anchor = this;
		var r = confirm("Are you sure you want to mark this this article as done? This cannot be undone.");
		if (r) {
			$.post(controller, {'a' : 'complete_article', 'langCode': $(this).attr('langcode'), 'aid' : $(this).attr('aid')}, function(res) {
				$(anchor).parent().html('marked as done- <a href="' + controller + '">refresh</a> page to see');
			});
		}
	});

	$(document).on('click', '.release', function(e) {
		e.preventDefault();
		var anchor = this;
		var r = confirm("Are you sure you want to remove this article from your list? Removing an article makes it available to other users to reserve.");
		if (r) {
			$.post(controller, {'a' : 'release_article', 'langCode': $(this).attr('langcode'), 'aid' : $(this).attr('aid')}, function(res) {
				$(anchor).parent().html('removed - <a href="' + controller + '">refresh</a> page to see');
			});
		}
	});

	$(document).on('click', '.reserve', function(e) {
		e.preventDefault();
		var statusBox = $(this).parent();

		$(statusBox).html('reserving...');
		$.post(controller, {'a' : 'reserve_article',  'langCode': $(this).attr('langcode'), 'aid' : $(this).attr('aid')}, function(res) {
			if (res.length) {
				alert(res);
			} else {
				$(statusBox).html('article placed onto <a href="' + controller + '">your list</a>');
			}
		}).fail(function(jqXHR, textStatus, textMsg) {
			alert(textStatus + ": " + textMsg);
		});
	});

	$(document).on('click', '#remove_tag_users', function(e) {
		e.preventDefault();
		var data = {'users' : getSelectedUsers(), 'a' : 'remove_tag_users', 'tags' : getSelectedTags()};
		setLoading();
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	function getRowData(link) {
		var cid = $(link).attr('cid');
		var rows = parseInt($(link).attr('numrows'));
		var offset = parseInt($(link).attr('offset'));
		var action = $(link).attr('id');
		var filter = $('#list_filter').val();
		return {'a' : action , 'cid' : cid, 'rows' : rows, 'offset' : offset, 'filter' : filter};
	}

	$(document).on('click', '#tag_list_more_rows,#assigned_list_more_rows', function(e) {
		e.preventDefault();
		var anchor = this;
		if (!$(anchor).hasClass('disabled')) {
			var data = getRowData(this);
			$.post(controller, data, function(res) {
				if (res.length == 0) {
					$(anchor).html('No More Articles').addClass('disabled');
				} else {
					var newOffset = parseInt($(anchor).attr('offset')) + parseInt($(anchor).attr('numrows'));
					$(anchor).attr('offset', newOffset);
				}
				$('table.wap:first').append(res);
				$("table.wap").trigger('update');
			});
		}


	});

	function filterList() {
		$('table.wap:first tbody').html('');
		$('#tag_list_more_rows').attr('offset', 0).removeClass('disabled').click().html('[More Articles]');
	}


	$(document).on('click', '#rpt_excluded_articles,#rpt_untagged_unassigned,#rpt_assigned_articles, #rpt_completed_articles', function(e) {
		e.preventDefault();
		var data = {'a' : $(this).attr('id')};
		$.download(controller, data);
	});

	$(document).on('click', '#rpt_assigned_articles_admin, #rpt_completed_articles_admin', function(e) {
		e.preventDefault();
		var data = {
			'a' : $(this).attr('id'),
			'langcode': $('#langcode').val(),
			'fromDate': $('#fromDate').val() || null,
			'toDate': $('#toDate').val() || null
		};
		$.download(controller, data);
	});


	$(document).on('click', '#rpt_user_articles', function(e) {
		e.preventDefault();
		var data = {'a' : $(this).attr('id'), 'uid' : $(this).attr('uid'), 'uname' : $(this).attr('uname')};
		$.download(controller, data);
	});

	$(document).on('click', '#rpt_tag_articles', function(e) {
		e.preventDefault();
		var data = {'a' : $(this).attr('id'), 'tagid' : $(this).attr('tagid'), 'tagname' : $(this).attr('tagname')};
		$.download(controller, data);
	});

	$(document).on('click', '#rpt_custom', function(e) {
		e.preventDefault();
		var data = {'a' : $(this).attr('id'), 'urls' : $('.urls').val(), 'langcode' : $('#langcode').val() };
		$.download(controller, data);
	});

	$(document).on('click', '#remove_excluded', function(e) {
		e.preventDefault();
		var data = {'a' : $(this).attr('id')};
		$.post(controller, data, function(res) {
			$('#results').html(res);
		});
	});

	$(document).on('change', '#csv_upload_input', function(e)
	{
		// Validation

		if (!$(this).val()) { // Cancelled by user
			return;
		}

		var file = this.files[0];
		if (file.type != 'text/csv') {
			alert("The file has the wrong format. It must be a CSV.")
			return;
		}
		if (file.size > 2097152) {
			alert("The file is too large. Max size is 2 megabytes.")
			return;
		}

		// Upload

		setLoading();
		$.ajax({
			type: 'POST',
			data: new FormData($('#csv_upload_form')[0]),
			cache: false,
			contentType: false,
			processData: false,
		})
		.done(function(data, textStatus, jqXHR) {
			$('#results').html(data);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			var data = JSON.parse(jqXHR.responseText);
			$('#results').html('<br><b>Errors:</b><br><br>' + data.error);
		})
		.always(function() {
			$('#csv_upload_input').val('');
		});
	});

	$(document).on('click', '#complete_articles_from_csv', function(e)
	{
		e.preventDefault();
		if (!hasCheckedAids()) {
			return;
		}

		// Collect checked articles

		var data = {};
		$.each($('.checked_article:checked:enabled'), function(idx, val) {
			var aid = $(val).data('aid');
			var uid = $(val).data('uid');
			data[uid] = data[uid] || [];
			data[uid].push(unescape(aid));
		});

		// POST

		setLoading();
		$.post(controller, { a: 'complete_articles_from_csv', data: data })
		.done(function(data, textStatus, jqXHR) {
			$('#results').html(data);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			var data = JSON.parse(jqXHR.responseText);
			$('#results').html('<br>Error: <b>' + data.error + '</b>');
		});;
	});

}(jQuery));
