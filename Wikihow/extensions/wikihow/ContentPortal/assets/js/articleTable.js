(function () {
	"use strict";
	window.WH = window.WH || {};

	WH.articleTable = {

		init: function () {
			this.listen();
			this.setupSearch();
			new Clipboard('.copy_doc_link');
		},

		setupSearch: function () {
			var titles = new window.Bloodhound({
				datumTokenizer: window.Bloodhound.tokenizers.whitespace,
				queryTokenizer: window.Bloodhound.tokenizers.whitespace,
				// prefetch: '',
				remote: {
					url: WH.Routes.articles_suggest,
					wildcard: 'QUERY'
				}
			});

			$('#full-search').click(_.bind(function (event) {
				this.search(event, $('input[name=title_search]').val());
			}, this));

			$('#suggest .input-lg').typeahead(null, {
				name: 'titles',
				source: titles,
				limit: 30,
				templates: {
					empty: [
						'<div class="alert alert-danger">',
							'Unable to find any articles that match the current query.',
						'</div>'
					].join('\n'),
				}
			}).bind('typeahead:select', $.proxy(this, 'search'));
		},

		search: function (event, title) {
			WH.cfApp.showLoading();

			$.ajax({
				method: 'get',
				url: WH.Routes.articles_search,
				data: {title_search: title}
			}).done(function (response) {
				WH.cfApp.hideLoading();
				$('#articles').html(response);
			});
		},

		listen: function () {
			$('body').on('click', 'a.assign-user', assignUser);
			$('[data-toggle="tooltip"]').tooltip({
				container: 'body'
			});
			$('body').on('mouseover', 'tr', showQuickLinks);
			$('body').on('click', 'a.copy_doc_link', copyLink);
		},

		updateTr: function (response) {
			var $refresh = $(response).fadeIn(300),
				$existing = $("#" + $refresh.attr('id')).removeClass('info');

			$existing.replaceWith($refresh);

			if ($refresh.data('stale')) {
				$refresh.addClass('info');
				$('#article-stale-modal').modal('show');
			}
		}
	};

	function assignUser (event) {
		event.preventDefault();
		var $btn = $(event.currentTarget),
			$row = $btn.closest('tr');

		$row.css('opacity', 0.2);

		$.ajax({
			method: 'post',
			url: WH.Routes.articles_assign,
			data: $btn.data()
		}).always(WH.articleTable.updateTr);
	}

	function showQuickLinks(event) {
		var $tr = $(event.currentTarget),
			$quickLinks = $tr.find('.hover');

		$quickLinks.width($tr.outerWidth() / 2);
	}

	function copyLink(event) {
		event.preventDefault();
	}

}());
