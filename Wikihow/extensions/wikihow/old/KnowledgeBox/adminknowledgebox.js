var adminKBToolURL = '/Special:AdminKnowledgeBox';

(function($) {

    var edittoggle = function(row) {
        $(row).find('.kbitemstatic').each(function () {
            $(this).toggle();
        });
        $(row).find('.kbitemediting').each(function () {
            $(this).toggle();
        });
    };

    var updaterowcount = function() {
        $('#kbtablecount').html($('.kbrow').length);
        $('#kbactivecount').html($('.kbactive').length);
        $('#kbinactivecount').html($('.kbinactive').length);
    }

    var hidetooltip = function(tooltip) {
        tooltip.fadeOut(100);
    };

    var showtooltip = function(tooltip, text) {
        $('.kbtooltip').each(function () {
            hidetooltip($(this));
        });

        tooltip
            .html(text)
            .css('top', 6 + 'px')
            .css('left', 55 + 'px')
            .fadeIn(150);
    };

    $(document).on('click', '.kbedit,.kbcancel', function (e) {
        e.preventDefault();

        var row = $(this).parents('.kbrow');

        edittoggle(row);

        return false;
    });

    $(document).on('click', '.kbcancel', function (e) {
        e.preventDefault();

        hidetooltip($(this).siblings('.kbtooltip'));

        return false;
    });

    $(document).on('click', '.kbedit', function (e) {
        e.preventDefault();

        $('.kbtooltip').each(function () {
            hidetooltip($(this));
        });

        return false;
    });

    $(document).on('click', '.kbtooltip', function (e) {
        e.preventDefault();

        hidetooltip($(this));

        return false;
    });

    var getFieldValues = function (row) {
        return {
            newid: $.trim(
                $(row).find('input[name]')
                      .filter('[name="kbnewaid"],[name="kbeditaid"]')
                      .val()),
            newtopic: $.trim(
                $(row).find('input[name]')
                      .filter('[name="kbnewtopic"],[name="kbedittopic"]')
                      .val()),
            newphrase: $.trim(
                $(row).find('input[name]')
                      .filter('[name="kbnewphrase"],[name="kbeditphrase"]')
                      .val())
        };
    };

    var checkFieldErrors = function (tooltip, newid, newtopic, newphrase) {
        if (!(newid && newtopic && newphrase)) {
            showtooltip(tooltip, 'Error! Empty fields not allowed!');
            return true;
        } else if (!$.isNumeric(newid)) {
            showtooltip(tooltip, 'Error! Article ID must be a number!');
            return true;
        } else if (newid > 4294967295) {
            showtooltip(tooltip, 'Error! Article ID cannot exceed 4294967295!');
            return true;
        } else if (newid < 1) {
            showtooltip(tooltip, 'Error! Article ID must be 1 or larger!');
            return true;
        } else {
            return false;
        }
    };

    // TODO: Receive args as an object or array
    var addTableRow = function (aid, title, url, topic, phrase) {
        var newrow = $('.kbdummyrow').clone();

        newrow.attr('kbid', aid);

        newrow.find('.kbrowactive>.kbitemstatic').html('Yes');

        newrow.find('.kbrowaid>.kbitemstatic').html(aid);

        var titlediv = newrow.find('.kbrowtitle[kbtitle="1"]');

        if (!title) {
            titlediv.remove();
        } else {
            newrow.find('.kbrowtitle[kbtitle="0"]').remove();
            titlediv.find('a')
                    .attr('href', url)
                    .html(title);
        }

        newrow.find('.kbrowtopic>.kbitemstatic').html(topic);
        newrow.find('input[name="kbedittopic"]').val(topic);

        newrow.find('.kbrowphrase>.kbitemstatic')
              .html(phrase);
        newrow.find('input[name="kbeditphrase"]')
              .val(phrase);

        newrow.attr('class', 'kbrow');
        newrow.insertBefore('.kbdummyrow');
        newrow.css('display', 'table-row');
        newrow.addClass('kbactive');

        updaterowcount();
    };

    $(document).on('click', '#kbaddrow', function (e) {
        e.preventDefault();

        var tooltip = $(this).siblings('.kbtooltip');
        hidetooltip(tooltip);

        var row = $(this).parents('#kbrow-add');

        var fieldValues = getFieldValues(row);
        var newid = fieldValues.newid;
        var newtopic = fieldValues.newtopic;
        var newphrase = fieldValues.newphrase;

        if (checkFieldErrors(tooltip, newid, newtopic, newphrase)) {
            return false;
        }

        var data = {
            'action': 'addrow',
            'kbAid': newid,
            'kbTopic': newtopic,
            'kbPhrase': newphrase
        };

        $.post(adminKBToolURL, data, function (result)  {
            if (result['error']) {
                showtooltip(tooltip, result['error']);
            } else {
                // TODO: Send as an object or array, include timestamp and other data
                addTableRow(result['aid'], result['title'], result['url'],
                            result['topic'], result['phrase']);

                row.find('input').val('');
            }
        }, 'json');

        // Prevent pop-up when leaving page
        window.onbeforeunload = function () {};

        return false;
    });

    $(document).on('click', '.kbsave', function (e) {
        e.preventDefault();

        var tooltip = $(this).siblings('.kbtooltip');
        hidetooltip(tooltip);

        var row = $(this).parents('.kbrow');

        var id = row.attr('kbid');
        var fieldValues = getFieldValues(row);
        var newtopic = fieldValues.newtopic;
        var newphrase = fieldValues.newphrase;

        if (checkFieldErrors(tooltip, id, newtopic, newphrase)) {
            return false;
        }

        var data = {
            'action': 'editrow',
            'kbAid': id,
            'kbTopic': newtopic,
            'kbPhrase': newphrase
        };

        $.post(adminKBToolURL, data, function (result) {
            if (result['error']) {
                showtooltip(tooltip, result['error']);
            } else {
                var titleitemdiv = row.find('.kbrowtitle>.kbitemstatic');

                if (result['title'] == '') {
                    titleitemdiv.html("<span class='kbgray'>No live article</span>");
                } else {
                    titleitemdiv.html("<a href='"+result['url']+"'>"
                                      + result['title'] + "</a>");
                }

                row.find('.kbrowtopic>.kbitemstatic').html(result['topic']);
                row.find('input[name="kbedittopic"]').val(result['topic']);

                row.find('.kbrowphrase>.kbitemstatic').html(result['phrase']);
                row.find('input[name="kbeditphrase"]').val(result['phrase']);

                row.find('.kbrowmodified>.kbitemstatic').html(result['modified']);

                edittoggle(row);
            }
        }, 'json');

        // Prevent pop-up when leaving page
        window.onbeforeunload = function () {};

        return false;
    });

    $(document).on('click', '.kbdisable', function (e) {
        e.preventDefault();

        var row = $(this).parents('.kbrow');
        var tooltip = $(row).find('.kbtooltip');

        var id = row.attr('kbid');

        if (checkFieldErrors(tooltip, id, true, true)) {
            return false;
        }

        var data = {
            'action': 'disablerow',
            'kbAid': id
        };

        $.post(adminKBToolURL, data, function (result) {
            if (result['error']) {
                showtooltip(tooltip, result['error']);
            } else {
                row.removeClass('kbactive');
                row.addClass('kbinactive');
                row.find('.kbrowactive .kbitemstatic').html('No');
                row.find('.kbrowmodified .kbitemstatic').html(result['modified']);
                row.find('.kbdisable')
                    .removeClass('kbdisable')
                    .addClass('kbenable')
                    .prop('title', 'Enable')
                    .html('&#10004;');
            }
        }, 'json');

        updaterowcount();

        return false;
    });

    $(document).on('click', '.kbenable', function (e) {
        e.preventDefault();

        var row = $(this).parents('.kbrow');
        var tooltip = $(row).find('.kbtooltip');

        var id = row.attr('kbid');

        if (checkFieldErrors(tooltip, id, true, true)) {
            return false;
        }

        var data = {
            'action': 'enablerow',
            'kbAid': id
        };

        $.post(adminKBToolURL, data, function (result) {
            if (result['error']) {
                showtooltip(tooltip, result['error']);
            } else {
                row.removeClass('kbinactive');
                row.addClass('kbactive');
                row.find('.kbrowactive .kbitemstatic').html('Yes');
                row.find('.kbrowmodified .kbitemstatic').html(result['modified']);
                row.find('.kbenable')
                    .removeClass('kbenable')
                    .addClass('kbdisable')
                    .prop('title', 'Disable')
                    .html('X')
            }
        }, 'json');

        updaterowcount();

        return false;
    });

    $(document).on('click', '.kbdisable', function (e) {
        e.preventDefault();

        var row = $(this).parents('.kbrow');
        var tooltip = $(row).find('.kbtooltip');

        var id = row.attr('kbid');

        if (checkFieldErrors(tooltip, id, true, true)) {
            return false;
        }

        var data = {
            'action': 'disablerow',
            'kbAid': id
        };

        $.post(adminKBToolURL, data, function (result) {
            if (result['error']) {
                showtooltip(tooltip, result['error']);
            } else {
                row.removeClass('kbactive');
                row.addClass('kbinactive');
                row.find('.kbrowactive .kbitemstatic').html('No');
            }
        }, 'json');

        return false;
    });

	function handleBulkAddUpdate(add) {
		var action = add ? 'add' : 'update';

		var lines = $('#kbbulk' + action).val().split('\n');
		var validLines = [];
		var badLines = 0;
		var nColumns = add ? 3 : 4;

		// This is where we ensure that the input is valid.
		// Super ugly due to changing and tacking on features constantly.
		// TODO: Refactor into something less ugly.
		$.each(lines, function () {
			var elems = $.map(this.split(','), $.trim);

			if (
				// Make sure we have the right number of columns per row:
				elems.length != nColumns ||
				// None of the columns are empty:
				$.grep(elems, function (e) { return !e; }).length > 0 ||
				// First element is a number:
				!$.isNumeric(elems[0]) ||
				// If we're *updating*, make sure second element is a number:
				(!add && !$.isNumeric(elems[1])) ||
				// First element in a valid range:
				elems[0] > 4294967295 || elems[0] < 0
				// If we're *updating*, make sure second elem also in valid range:
				|| (!add && (elems[1] > 4294967295 || elems[1] < 0)) ||
				$.grep(validLines, function (e) {
					// Make sure we don't already have duplicates of...
					if (add) {
						// Article ID + Topic + Phrase when we're adding
						return e[0] == elems[0] && e[1] == elems[1] && e[2] == elems[2];
					} else {
						// KB ID when we're updating
						return e[0] == elems[0];
					}
				}).length > 0
			) {
				badLines++;
			} else {
				validLines.push(elems);
			}
		});

		console.log('KB ' + action + ' bulk: ' + badLines + ' bad lines');

		var data = {
			'action': action + 'bulk',
			'kbData': JSON.stringify(validLines)
		};

		$.post(adminKBToolURL, data, function (result) {
			if (result['error']) {
				alert(result['error']);
			} else {
				var alertStr = '';

				var type = result['type'];
				var updatedData = result['updatedData'];

				if (type == 'add') {
					alertStr += updatedData.length + ' new rows added\n';
				} else {
					alertStr += updatedData.length + ' rows updated\n';
				}

				if (badLines > 0) {
					alertStr += '\n';
					alertStr += badLines + ' input lines ignored';
				}

				if (updatedData.length > 0) {
					alertStr += '\n';
					alertStr += 'Refresh page to see the new changes!';
				}

				alert(alertStr);
			}
		}, 'json');
	}

    $(document).on('click', '#kbbulkaddbtn', function (e) {
        e.preventDefault();

        handleBulkAddUpdate(true);

        return false;
    });

    $(document).on('click', '#kbbulkupdatebtn', function (e) {
        e.preventDefault();

        handleBulkAddUpdate(false);

        return false;
    });

	function handleBulkSetActive(enable) {
		var action = enable ? 'enable' : 'disable';
		
		var lines = $('#kbbulk' + action).val().split('\n');
        var validLines = [];
        var badLines = 0;

        $.each(lines, function () {
            elem = $.trim(this);
            if (elem.length == 0 ||
                    !$.isNumeric(elem) ||
                    elem[0] > 4294967295 || elem[0] < 1 ||
                    $.grep(validLines, function (e) {
                        return e == elem;
                    }).length > 0) {
                badLines++;
            } else {
                validLines.push(elem);
            }
        });

        console.log('KB ' + action + ' bulk: ' + badLines + ' bad lines');

		var data = {'action': action + 'bulk',
					'kbData': JSON.stringify(validLines)};

        $.post(adminKBToolURL, data, function (result) {
			if (result['error']) {
				alert(result['error']);
			} else {
				var alertStr = '';

				var count = result['updatedCount'];
				var type = result['type'];

				console.log(result);

				alertStr += count + ' rows updated';
				if (badLines > 0) {
					alertStr += '\n';
					alertStr += badLines + ' input lines ignored';
				}
				if (validLines.length != count) {
					alertStr += '\n';
					alertStr += (validLines.length - count)
						+ ' IDs not found, and thus ignored';
				}

				alert(alertStr);

				if (type == 'enable') {
					var oldClass = 'kbinactive';
					var newClass = 'kbactive';
					var active = 'Yes';
					var oldSelector = 'kbenable';
					var newSelector = 'kbdisable';
					var newTitle = 'Disable';
					var symbol = 'X';
				} else {
					var oldClass = 'kbactive';
					var newClass = 'kbinactive';
					var active = 'No';
					var oldSelector = 'kbdisable';
					var newSelector = 'kbenable';
					var newTitle = 'Enable';
					var symbol = '&#10004;';
				}

				$.each(validLines, function () {
					var row = $('.kbrow[kbid="'+this+'"]');
					row.removeClass(oldClass).addClass(newClass);
					row.find('.kbrowactive>.kbitemstatic').html(active);
					row.find('.kbrowmodified>.kbitemstatic').html('N/A');
					row.find('.' + oldSelector)
						.removeClass(oldSelector)
						.addClass(newSelector)
						.prop('title', newTitle)
						.html(symbol);
				});

				updaterowcount();
			}
		}, 'json')
            .fail(function (ts) { console.log(ts.responseText); });
	}

    $(document).on('click', '#kbbulkdisablebtn', function (e) {
        e.preventDefault();
		handleBulkSetActive(false);
        return false;
    });

    $(document).on('click', '#kbbulkenablebtn', function (e) {
        e.preventDefault();
		handleBulkSetActive(true);
        return false;
    });

    $(document).on('click', '#kbdisableall', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to disable all articles on KnowledgeBox?')) {
            return false;
        }

        data = {'action': 'disableall'};

        $.post(adminKBToolURL, data, function (result) {
            if (result['error']) {
                console.log(result['error']);
            } else {
                $('.kbrow').remove();
            }
        }, 'json');

        updaterowcount();

        return false;
    });

    $(document).ready(function() {
        $('.kbdummyrow td').data('data-empty', 'bottom');
		// Wait, why does tablesorter still work with this commented out?
		/*
        $('table.tablesorter#kbadmintable').tablesorter({
            sortList: [[0,1],[1,0]],
            emptyTo: 'bottom', // Doesn't work in this version of tablesorter?
            debug: false,
            textExtraction: function(node) {
                // extract data from markup and return it
                var target = $(node);
                var value = "0";
                var staticElem = target.find('.kbitemstatic');

                if (target.parents('.kbdummyrow').length) {
                    value = "";
                } else if (target.is(
                        '.kbrowactive,.kbrowaid,.kbrowtitle,.kbrowtime,.kbrowtopic,'
                        + '.kbrowmodified,.kbrowphrase,.kbrowsubcount')) {
                    if (staticElem) {
                        value = $.trim(staticElem.html());
                    } else {
                        value = "";
                    }
                } else if (target.hasClass('kbrowaction')) {
                    value = "0";
                } else {
                    value = "";
                }

                return value;
            }
        });
		*/
        updaterowcount();
    });
})(jQuery);
