$(document).ready(function() {
	var sqlAce;
	var qbfields = $.map(rawfields, function (val) {
		return {
			id: val.field,
			text: val.name,
			label: val.name,
			type: val.ftype,
		};
	});

	function buildQueryTool() {
		buildSqlFields();
		buildSqlWhere();
		setupLanguagesUrls();
		setupAce();
		setupDownloads();
		setupVault();
		setupStoreVaultQueries();
		setupSparkles();
		setupToastr();
		handleQueryLocation();
		hackSqlWhereLocation();
		setupSqlHackEvents();
		$('.loading').hide();
		$('.tqt').show();
	}

	function buildSqlFields() {
		$('#newselect').on('click', function() {
			var n = $('#selectcontainers select').length;
			appendSqlField(n);
		});

		$('#selectcontainers').sortable({ placeholder: 'rule-placeholder' });
	}

	function appendSqlField(n) {
		var select_id = 'sqlselect_' + n;
		var tmpl = $('#sqlselect_template li').clone();
		tmpl.attr('id', select_id + '_li');
		tmpl.find('select').attr('id', select_id).select2({
			data: qbfields,
			width: '320px'
		}).on('change', function() {
			var label = $(this).parents('li').find('input');
			var option_text = $(this).find('option:selected').text();
			if (option_text != $(label).val() &&
					$(label).val() == $(label).data('initial-val')) {

				$(label).val(option_text).data('initial-val', option_text);
			}
		});
		var initial_val = tmpl.find('option:selected').text();
		tmpl.find('input').val(initial_val).data('initial-val', initial_val);
		tmpl.find('.deletebutton').on('click', function() {
			$(this).parents('li').remove();
		});

		$('#selectcontainers').append(tmpl);

		return select_id;
	}

	function buildSqlWhere() {
		$('.sqlwhere').queryBuilder({
			select_placeholder: 'Select a field...',
			filters: qbfields,
			allow_empty: true,
			rules: [],
			icons: {
				add_group: 'fa fa-plus-square',
				add_rule: 'fa fa-plus-circle',
				remove_group: 'fa fa-minus-square',
				remove_rule: 'fa fa-minus-circle',
				error: 'fa fa-exclamation-triangle'
			},
			plugins: {
				sortable: {
					icon: 'fa fa-sort'
				},
				'not-group': {
					icon_unchecked: 'fa fa-square-o',
					icon_checked: 'fa fa-check-square-o',
				},
				select2: {
					'width': 'resolve'
				}
			}
		});
	}

	function setupSqlHackEvents() {
		$('.sqlwhere').on('afterAddGroup.queryBuilder', function() {
			hackSqlWhereLocation();
		});
	}

	function hackSqlWhereLocation() {
		/**
		* DIRE WARNING: MASSIVE, MASSIVE HACK
		*
		* I understand why this should be done, for usability reasons. It seems
		* so obvious. However, if we ever upgrade the querybuilder library this is
		* highly likely to break things.
		* Upgrade at your own risk!
		* Modify at your own risk!
		**/
		// BEGIN HACK
		var buttons_copy = $('.sqlwhere dt.rules-group-header div.group-actions').not('.hack-applied').first().clone();
		buttons_copy.removeClass('pull-right').addClass('hack-applied');
		$('.sqlwhere dt.rules-group-header div.group-actions').not('.hack-applied').remove();
		var new_buttons_group = $('<dt class="rules-group-header"></dt>');
		var new_buttons_dd = $('<dd class="rules-group-body-hack hack-applied"></dd>').append(buttons_copy);
		new_buttons_group.after(new_buttons_dd);
		$('.sqlwhere dd.rules-group-body').not('.hack-applied').append(new_buttons_group).addClass('hack-applied');
		// END HACK
	}

	function setupSparkles() {
		// Make the load button amazing
		$('.tqt-type').sparkle({
			color: 'rainbow',
			count: 160,
			speed: 4,
			direction: 'down',
			overlap: 10
		}).sparkle({
			color: ['#ff0080","#ff0080","#0000FF'],
			count: 100,
			speed: 4,
			direction: 'down',
			overlap: 10
		});
		$('.tqt-type').off('mouseover.sparkle').off('mouseout.sparkle').off('focus.sparkle').off('blur.sparkle');
		$('#vaultselect').on('change', function() {
			// We can't just trigger on the select since it's hidden
			$('.tqt-type').trigger('start.sparkle');
			setTimeout(function() { $('.tqt-type').trigger('stop.sparkle'); }, 500);
		});
	}

	function getSQL() {
		var sql_string;
		if ($('.tqt-query-builder-sql').is(':visible')) {
			sql_string = sqlAce.getValue();
		} else {
			var fields = $.map($('#selectcontainers li'), function (el) {
				return $(el).find('select').val() + ' as "' + $(el).find('input').val() + '"';
			});

			if (fields.length === 0) {
				fields.push('*');
			}
			var predicate = $('.sqlwhere').queryBuilder('getSQL', false);

			sql_string = 'SELECT ' + fields.join(', ') + ' FROM titus';
			if (predicate.sql !== '') {
				sql_string += ' WHERE ' + predicate.sql;
			}
		}
			return sql_string;
	}

	function setupLanguagesUrls() {
		$('#page-filter').select2({ width: '20%' }).on('change', function() {
			$('#urls').toggle($(this).val() == 'urls');
		});
	}

	function setupAce() {
		var acefields = [];
		$.each(qbfields, function (idx, val) {
			acefields.push(val.id);
			acefields.push(val.label);
		});
		sqlAce = ace.edit('tqt-query-builder-ace');
		var langTools = ace.require('ace/ext/language_tools');
		sqlAce.getSession().setMode('ace/mode/mysql');
		langTools.addCompleter({
			getCompletions: function(editor, session, pos, prefix, callback) {
				callback(null, acefields.map(function(word) {
					return {
						caption: word,
						value: word,
						meta: 'static',
					};
				}));
			}
		});
		sqlAce.setOptions({
			enableLiveAutocompletion: true,
		});

		$('#getsql').on('click', function() {
			if ($('.tqt-query-builder-sql').is(':visible')) {
				try {
					switchToGuiBuilder();
				} catch (err) {
					toastr.error('This query can\'t be loaded into the GUI builder. But you can still use it!');
					return false;
				}
			} else {
				switchToSqlBuilder();
			}
		});
	}

	function resetBuilder() {
		$('#selectcontainers li').remove();
		$('.sqlwhere').queryBuilder('reset');
		$('.sqlwhere').queryBuilder('setRules', { rules: [] });
	}

	function setBuilderFromSQL(sql) {
		var sql_parts = sql.split(/from\s+titus/i);
		// We really need to hack up some SQL parsing problems.
		// 1) the SQL parser library cannot handle quotes in the SELECT portion of a query
		// 2) we can't handle double quotes in the WHERE portion of a query
		// 3) We get pretty upset with things like 'AND(', so insert a space.
		// 4) The old titus builder had a knack for generating "not(foo like '%bar%')" rather than "foo NOT LIKE '%bar%'". This
		//		is of course legal SQL, but it trips up the SQL parser (it inserts a ton of extra parens) which then trips up
		//		SQL builder if we try to reload it (it tries to flatten AND groups and they're just really stupid about it).
		//		Actually, the SQL builder will happily generate a "( NOT ( foo LIKE '%bar%' ) )" statement that then will never
		//		translate back into the builder because it trips up the parser so badly. I may need to fix that since it's kinda
		//		easy to trigger but right now I'm ignoring it.
		sql_parts[0] = sql_parts[0].replace(/'|"/g, '');
		if (sql_parts[1]) {
			sql_parts[1] = sql_parts[1].replace(/"/g, '\'').replace(/(and|not|or)\(/ig, '$1 (').replace(/not\s?\((([^()]+)like([^()]+))\)/ig, '$2 NOT LIKE $3');
		}
		sql = sql_parts.join('FROM titus');

		var p = SQLParser.parse(sql);
		if (p.where && p.where.conditions) {
			fixSQLTree(p.where.conditions);
		}

		$.each(p.fields, function(idx, val) {
			if (!val.star) {
				var id = appendSqlField(idx);
				var valtext = val.field.values.join('.');
				$('#' + id).val(valtext).trigger('change');

				if (val.name) {
					var name = val.name.values.join('.');
					var el = $('#' + id).parents('li').find('input');
					el.val(name);
				} else {
					$('#' + id).parents('li').find('input').val(valtext).data('initial-val', valtext);
				}
			}
		});

		if (p.where) {
			$('.sqlwhere').queryBuilder('setRulesFromSQL', p.toString());
		}
	}

	function fixSQLTree(tree) {
		if (tree.hasOwnProperty('left')) {
			fixSQLTree(tree.left);
		}
		if (tree.hasOwnProperty('right')) {
			fixSQLTree(tree.right);
		}

		$.each(qbfields, function (idx, val) {
			if (tree.hasOwnProperty('value') && tree.value == val.text) {
				tree.value = val.id;
				tree.nested = true;
				tree.values.unshift('titus');
			}
		});
	}

	function setupDownloads() {
		$('.fetch').click(function(){
			var sql = getSQL().replace(/\n/g, ' ').split(/\s+/).join(' '); //normalize whitespace

			if (!sql.length && (!sql.length && (!$('#urls').val().length && $('#filter_urls').is(':checked')))) {
				var answer = confirm('WARNING: You have not given me any conditions to filter this report. Queries like this will slow down Titus for everyone. \n\n Click OK if you wish to proceed.');
				if (!answer) {
					return false;
				}
			}

			var pagefilter = $('#page-filter').val();
			var urls = $('.urls').val();
			if (pagefilter == 'urls' && urls.length === 0) {
				toastr.error('You\'ve selected to filter by URLs but haven\'t entered any!');
				$('.urls').effect('bounce');
				return false;
			}

			var data = {
				'action': 'query',
				'sql' : sql,
				'urls': urls,
				'page-filter': pagefilter,
				'ti_exclude' : $('#ti_exclude').is(':checked') ? 1 : 0
			};
			$.download('/' + wgPageName, data);

			return false;
		});
	}

	function setupVault() {
		$('#vaultselect').select2({ width: '100%' });
		$('#vaultselect').on('change', function() {
			if ($(this).val() === 'new') {
				$('input[name="qv-name"]').val('').attr('placeholder', 'Query name');
				$('input[name="qv-desc"]').val('').attr('placeholder', 'Query description');
				$('.tqt-save-vault').show();
				$('.tqt-save-your-vault').hide();
				$('.tqt-update-vault').hide();
				switchToGuiBuilder('SELECT * FROM TITUS');
				window.history.replaceState({}, document.title, ('/' + wgPageName));
			} else {
				$.post('/' + wgPageName, {
					action: 'get_by_id',
					id: $(this).val(),
				}, function(queryInfo) {
					try {
						switchToGuiBuilder(queryInfo.query);
					} catch (err) {
						toastr.error('This query can\'t be loaded into the GUI builder. But you can still use it!');
						switchToSqlBuilder(queryInfo.query);
					}

					if (queryInfo.description) {
						$('input[name="qv-desc"]').val(queryInfo.description).attr('placeholder', '');
					} else {
						$('input[name="qv-desc"]').val('').attr('placeholder', 'Query description');
					}
					$('input[name="qv-name"]').val(queryInfo.name).attr('placeholder', '');

					$('.tqt-save-vault').hide();
					if (mw.user.getName() !== $('#vaultselect').find('option:selected').parents('optgroup').attr('label')) {
						$('.tqt-update-vault').hide();
						$('.tqt-save-your-vault').show();
						$('.tqt-save-your-vault').show();
					} else {
						$('.tqt-update-vault').show();
						$('.tqt-save-your-vault').hide();
						$('.tqt-save-vault').hide();
					}

					window.history.replaceState({}, document.title, ('/' + wgPageName + '?q=' + queryInfo.id));
				}, 'json').fail(function () {
					toastr.error('An unknown error has occurred.');
				});
			}
		});
	}

	function setupStoreVaultQueries() {
		$('button.tqt-vault-post').on('click', function() {
			var action = $.trim($(this).text());
			var button = $(this);

			var options = {};
			if (action === 'Save' || action === 'Copy' || action === 'Update') {
				options.action = 'store';
				options.name = $.trim($('#qv-name').val());
				options.description = $.trim($('#qv-desc').val());
				options.query = $.trim(getSQL());
				if (!options.name.length) {
					toastr.error('Query name not provided');
					return false;
				}

				if (!options.query.length) {
					toastr.error('Error: no query to store');
					return false;
				}
			} else if (action === 'Delete') {
				options.action = 'delete';
			}

			if (action === 'Update' || action === 'Delete') {
				options.id = $('#vaultselect').val();
			}

			$(this).text('★★★ Working ★★★');

			$.post('/' + wgPageName, options, function (result) {
				if (result.success) {
					toastr.success('Success! Redirecting...');
					setTimeout(function () {
						if (result.id) {
							window.location = '/' + wgPageName + '?q=' + result.id;
						} else {
							window.location = '/' + wgPageName;
						}
					}, 1000);
				} else {
					var errors = [];
					$.each(result.errors, function(e) {
						errors.push(e);
					});
					toastr.error('Query failed. Errors: ' + errors.join(', '));
				}
			}, 'json').fail(function () {
				toastr.error('An unknown error occurred');
			}).always(function() {
				button.text(action);
			});

			return false;
		});
	}

	function getParams() {
		var params = {};
		$.each(window.location.search.substring(1).split('&'), function (idx, val) {
			var parts = val.split('=');
			if (parts.length == 2) {
				params[parts[0]] = parts[1];
			}
		});

		return params;
	}

	function handleQueryLocation() {
		var params = getParams();
		if (params.q) {
			if ($('#vaultselect option[value=' + params.q + ']').length > 0) {
				$('#vaultselect').val(params.q).trigger('change');
			}
		}
	}

	function switchToGuiBuilder(sql) {
		if (sql === undefined) {
			sql = getSQL();
		}
		resetBuilder();
		setBuilderFromSQL(sql);
		hackSqlWhereLocation(); // I'm a little too annoyed with this right now, but it needs to go here.
		$('#getsql').html('<i class=\'fa fa-code\'></i>&nbsp;SQL');
		$('.tqt-query-builder-gui').show();
		$('.tqt-query-builder-sql').hide();
	}

	function switchToSqlBuilder(sql) {
		if (sql === undefined) {
			sql = getSQL();
		}
		$('#getsql').html('<i class=\'fa fa-tasks\'>&nbsp;Builder');
		sqlAce.setValue(sqlFormatter.format(sql), -1);
		$('.tqt-query-builder-gui').hide();
		$('.tqt-query-builder-sql').show();
	}

	function setupToastr() {
		toastr.options = {
			closeButton: true,
			debug: false,
			positionClass: 'toast-bottom-center',
			showDuration: 300,
			hideDuration: 1000,
			timeOut: 2000,
			showEasing: 'swing',
			hideEasing: 'linear',
			showMethod: 'fadeIn',
			hideMethod: 'fadeOut'
		};
	}

	buildQueryTool();
});
