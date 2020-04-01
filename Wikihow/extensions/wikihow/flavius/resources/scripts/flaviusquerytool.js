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
		setupDatesUsers();
		setupAce();
		setupDownloads();
		setupToastr();
		hackSqlWhereLocation();
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
		var buttons_copy = $('.sqlwhere dt.rules-group-header div.group-actions').clone();
		buttons_copy.removeClass('pull-right');
		$('.sqlwhere dt.rules-group-header div.group-actions').remove();
		$('.sqlwhere dd.rules-group-body').after('<dt class="rules-group-header"></dt><dd class="rules-group-body-hack"></dd>');
		$('.sqlwhere dd.rules-group-body-hack').append(buttons_copy);
		// END HACK
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

			sql_string = 'SELECT ' + fields.join(', ') + ' FROM flavius_summary';
			if (predicate.sql !== '') {
				sql_string += ' WHERE ' + predicate.sql;
			}
		}
			return sql_string;
	}

	function setupDatesUsers() {
		$('#user-filter').select2({ width: '20%' }).on('change', function() {
			$('#userlist').toggle($(this).val() == 'these');
		}).trigger('change');
		$('#days').select2({ width: '20%' });
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
		var sql_parts = sql.split(/from\s+flavius_summary/i);
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
		sql = sql_parts.join('FROM flavius_summary');

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
				tree.values.unshift('flavius_summary');
			}
		});
	}

	function setupDownloads() {
		$('.fetch').click(function(){
			var sql = getSQL().replace(/\n/g, ' ').split(/\s+/).join(' '); //normalize whitespace

			var userfilter = $('#user-filter').val();
			var users = $('.userlist').val();
			if (userfilter == 'these' && users.length === 0) {
				toastr.error('You\'ve selected to filter by users but haven\'t entered any!');
				$('.userlist').effect('bounce');
				return false;
			}

			var data = {
				'action': 'query',
				'sql' : sql,
				'users': users,
				'usersType': userfilter,
				'days': $('#days').val()
			};
			$.download('/' + wgPageName, data);

			return false;
		});
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
