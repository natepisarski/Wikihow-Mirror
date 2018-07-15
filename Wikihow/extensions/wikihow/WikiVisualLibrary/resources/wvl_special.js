(function ($) {
	'use strict';

	var toolURL = '/Special:WikiVisualLibrary';
	var loadingPhrases = [
		'Twiddling thumbs...', 'Fetching pictures...', 'Crunching numbers...', 'Scribbling doodles...',
		'Firing up databases...', 'Assigning librarians...', 'Time for a coffee break...', 'Hold on tight...',
		'Breaking Titus...', 'Pretending to work...', 'Invalidating cache...', 'Don\'t hold your breath...',
		'&#9834; <i>Elevator music</i> &#9834;', 'Putting servers to work...', 'Exceeding S3 quotas...',
		'Finding funny images...'
	];
	var lastSearchRequest = {};
	var acknowledgedSlowQuery = false;

	$('select.wvl-dropdown').change(onDropDownChange);
	$('input.wvl-input,input.wvl-datepicker').on('input', onInputChange);
	$('input.wvl-datepicker').change(onInputChange);
	$('.wvl-selector-button').click(onSelectorClick);
	$('button#wvl-fetch').click(fetch);
	$('.wvl-input-container>.wvl-input').keypress(submitOnEnter);
	$('#wvl-navbar-toggle-container').click(toggleSearchBar);

	function onDropDownChange(e) {
		var target = $(e.target);

		checkElementActive(target);

		return false;
	}

	function onInputChange(e) {
		var target = $(e.target);

		checkElementActive(target);

		return false;
	}

	function submitOnEnter(e) {
		if (e.which == 13) { // enter was pressed
			$(this).blur();
			$('button#wvl-fetch').focus().click();

			return false;
		}

		// Don't return false here, or propagation of *all* keypress events is stopped
	}

	function checkElementActive(target) {
		var val = target.val();

		if (val) {
			target.closest('.wvl-nav-item').removeClass('inactive').addClass('active');
		} else {
			target.closest('.wvl-nav-item').removeClass('active').addClass('inactive');
		}

		return false;
	}

	function onSelectorClick(e) {
		var target = $(e.target);
		if (target.hasClass('active')) {
			if (target.find('.wvl-sort-order').length) {
				var activeOrder = target.find('.active');
				var inactiveOrder = target.find('.inactive');
				activeOrder.removeClass('active').addClass('inactive');
				inactiveOrder.removeClass('inactive').addClass('active');
			}
		} else {
			var row = target.parent();

			row.find('.wvl-selector-button').removeClass('active').addClass('inactive');
			$(this).removeClass('inactive').addClass('active');
		}

		return false;
	}

	function onPagerClick(e) {
		var target = $(e.target);
		var page = 0;

		if (target.hasClass('wvl-pager-fast-back')) {
			page = 1;
		} else if (target.hasClass('wvl-pager-back')) {
			page = Math.max(1, target.parent().find('.wvl-pager-current').data('page') - 1);
		} else if (target.hasClass('wvl-pager-page')) {
			page = target.data('page');
		} else if (target.hasClass('wvl-pager-forward')) {
			page = Math.min(
				target.parent().find('.wvl-pager-fast-forward').data('page'),
				target.parent().find('.wvl-pager-current').data('page') + 1
			);
		} else if (target.hasClass('wvl-pager-fast-forward')) {
			page = target.data('page');
		}

		fetchPage(page);

		return false;
	}

	function onResultActionClick(e) {
		var target = $(e.target);

		var wrapper = target.parents('.wvl-result-actions-wrapper');

		wrapper.find('.wvl-result-actions').removeClass('active').addClass('inactive');

		var selector = undefined;
		var type = undefined;

		if (target.hasClass('wvl-result-action-copy-wikitext')) {
			selector = '.wvl-result-wikitext';
			type = 'wikitext';
		} else {
			selector = '.wvl-result-url';
			type = 'url';
		}

		var field = wrapper.find(selector);

		copyTextField(field);
		field.removeClass('inactive')
			.addClass('active')
			.focus()
			.select()
			.blur(showResultActionButtons);

		showCopyToast(wrapper);

		return false;
	}

	function copyTextField(e) {
		var tmp = $('<input>');
		$('body').append(tmp);
		tmp.val(e.text()).select();
		document.execCommand('copy');
		tmp.remove();

		return false;
	}

	function showResultActionButtons(e) {
		var target = $(e.target);
		var wrapper = target.parents('.wvl-result-actions-wrapper');

		target.unbind('blur');
		wrapper.find('.wvl-result-copy-field').removeClass('active').addClass('inactive');
		wrapper.find('.wvl-result-actions').removeClass('inactive').addClass('active');

		return false;
	}

	function getSelectedButtonInfo(e) {
		var sel = e.find('.wvl-selector-button.active');

		if (!sel.length) {
			return false;
		}

		var val = $(sel).val();

		if (!val) {
			return false;
		}

		var data = {};
		data.value = $.trim(val);

		$.each($(sel).find('.wvl-sb-extras'), function () {
			var type = $(this).data('type');

			data[type] = $.trim($(this).find('.active').data('value'));
		});

		return data;
	}

	function showCopyToast(wrapper) {
		wrapper.find('.wvl-copied-toast').show().delay(500).fadeOut('slow');
	}

	function getSearchParameters() {
		var data = {};

		var creator = $('#wvl-creator-list').val();
		var topcat = $('#wvl-topcat-list').val();
		var keyword = $.trim($('#wvl-title-input').val());
		var dateLower = $.trim($('#wvl-date-from').val());
		var dateUpper = $.trim($('#wvl-date-to').val());

		// Temporarily disabled
		// var sortBy = getSelectedButtonInfo($('#wvl-sort-selector'));
		// var pageless = getSelectedButtonInfo($('#wvl-pageless-selector'));
		// var assetType = getSelectedButtonInfo($('#wvl-type-selector'));
		// var perPage = getSelectedButtonInfo($('#wvl-per-page-selector'));
		var sortBy = false;
		var pageless = false;
		var assetType = false;
		var perPage = false;

		if (creator) data.ce = creator;
		if (topcat) data.topcat = topcat;
		if (keyword) data.keyword = keyword;
		if (dateLower) data.dateLower = dateLower;
		if (dateUpper) data.dateUpper = dateUpper;

		if (sortBy) {
			data.sortby = sortBy.value;
			if (sortBy.order) data.sortorder = sortBy.order;
			if (sortBy.value == 'random') data.randseed = 1 + Math.floor(Math.random() * Math.pow(2, 31));
		}
		if (pageless) data.pageless = pageless.value;
		if (assetType) data.assettype = assetType.value;
		if (perPage) data.perpage = perPage.value;

		return data;
	}

	function fetch() {
		if ($(this).hasClass('inactive')) {
			return false;
		}

		var data = getSearchParameters();
		data.action = 'give_images_plz';

		lastSearchRequest = data;

		doRequest(data);

		return false;
	}

	function fetchPage(page) {
		var data = lastSearchRequest;
		data.action = 'give_images_plz';
		data.page = page;

		doRequest(data);
	}

	function doRequest(data) {
		waiting();

		if (!acknowledgedSlowQuery && !(data.ce || data.topcat || data.keyword)) {
			if (!confirm('No filters set. This query will probably be hard on the database. Are you sure you want this?')) {
				doneWaiting();
				return false;
			}
			acknowledgedSlowQuery = true;
		}

		var url = buildURL(data);
		history.pushState(data, 'wikiVisual Search Results', url);

		$.post(
			toolURL,
			data,
			display,
			'json'
		).always(doneWaiting);

		return false;
	}

	function buildURL(data) {
		return '/Special:WikiVisualLibrary?' + serialize(data);
	}

	function waiting() {
		var phrase = loadingPhrases[Math.floor(Math.random()*loadingPhrases.length)];
		$('#wvl-fetch')
			.removeClass('active')
			.addClass('inactive')
			.html(phrase);
		$('.wvl-pager-waiting').html(phrase).show();
		$('.wvl-pager').hide();
	}

	function doneWaiting() {
		setTimeout(function () {
			$('#wvl-fetch').removeClass('inactive').addClass('active').html('Fetch');
		}, 500);
	}

	function display(result) {
		$('.wvl-pager-waiting').hide();
		$('.wvl-pager').show();
		collapseSearchBar();
		$('#wvl-navbar-toggle-container').removeClass('inactive');
		$('#wvl-results-container').empty().html(
			Mustache.render(
				unescape($('#wvl-results-template').html()),
				result
			)
		);
		$('.wvl-pager>a').click(onPagerClick);

		$('.wvl-result-action-btn').click(onResultActionClick);
	}

	function toggleSearchBar() {
		if ($('#wvl-navbar-container').hasClass('active')) {
			collapseSearchBar();
		} else {
			expandSearchBar();
		}
	}

	function collapseSearchBar() {
		$('#wvl-navbar-container,#wvl-navbar-collapse').removeClass('active').addClass('inactive');
		$('#wvl-navbar-expand').removeClass('inactive').addClass('active');
	}

	function expandSearchBar() {
		$('#wvl-navbar-container,#wvl-navbar-collapse').removeClass('inactive').addClass('active');
		$('#wvl-navbar-expand').removeClass('active').addClass('inactive');
	}

	function serialize(obj, prefix) {
		var str = [];

		for (var p in obj) {
			if (obj.hasOwnProperty(p)) {
				var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
				str.push(typeof v == "object" ?
					serialize(v, k) :
					encodeURIComponent(k) + "=" + encodeURIComponent(v));
			}
		}

		return str.join("&");
	}

	function parseQuery(qstr) {
		var query = {};
		var a = qstr.substr(1).split('&');
		for (var i = 0; i < a.length; i++) {
			var b = a[i].split('=');
			query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
		}
		return query;
	}

	window.onpopstate = function () {
		if (history.state) {
			lastSearchRequest = history.state;
			doRequest(lastSearchRequest);
		} else {
			$('#wvl-results-container').empty();
			expandSearchBar();
		}
	}

	$(document).ready(function () {
		$('.wvl-dropdown,.wvl-input,.wvl-datepicker').each(function () {
			checkElementActive($(this));
		});

		$('.wvl-datepicker').datepicker();

		var titleLink = $('<a/>', {
			href: '/Special:WikiVisualLibrary',
			text: $('#article>.wh_block>.firstHeading').text()
		});

		$('#article>.wh_block>.firstHeading').html(titleLink);

		if (document.location.search.length) {
			lastSearchRequest = parseQuery(document.location.search);
			doRequest(lastSearchRequest);
		} else if (history.state) {
			lastSearchRequest = history.state;
			doRequest(lastSearchRequest);
		}
	});
})(jQuery);
