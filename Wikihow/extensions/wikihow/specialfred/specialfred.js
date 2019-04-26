(function() {

	"use strict";
	window.WH = window.WH || {};
	window.WH.SpecialFred = {
        getNow : Date.now || function() { return new Date().getTime(); },
        startTime : null,
        lastVote : 0,
        lastPageId : null,
        articleVisible: false,
		tool: '/Special:Fred',
		langCookie: 'spfred_lang',
		showNumCookie: 'spfred_shownum',

		createRow: function(line) {
			//console.log("line", line);
			var res = jQuery('<tr/>', {
				class: 'data-line',
			});
			for (var key in line) {
				var column = jQuery('<td/>', {
					class: key,
				});
				column.addClass('data-element');
				column.html(line[key]);
				res.append(column);
			}
			return res;
		},
		showData: function(data) {
			//console.log("got data", data);
			$('#data').empty();
			for (var i = 0; i < data.length; i++ ) {
				var line = this.createRow(data[i]);
				$('#data').append(line);
			}
			$('.data-line:first').addClass("first-line");
		},

		escapeHtml: function (htmlString) {
			return $('<textarea/>').html(htmlString).text();
		},

		showMessage: function(msg) {
			$('#stva_message').removeClass('stva_error').html(msg);
		},

		showError: function(err) {
			$('#stva_message').addClass('stva_error').html(err);
		},

		showCloseButton: function() {
			$('#stva_edit_done').show().click(function() {
				$.modal.close();
				window.location.reload();
			});
		},

		processing: function(is_processing) {
			if (is_processing) {
				$('#stva_message').html('');
				$('#stva_edit_submit').fadeOut();
			}
			else {
				$('#stva_edit_submit').fadeIn();
			}
		}, 

        getData: function() {
			var sortBy = 'default';
			var lang = $('#lang').val();
			$.cookie(this.langCookie, lang);

			var showNum = $('#shownum').val();
			$.cookie(this.showNumCookie, showNum);

			var payload = {
					action: 'get_data',
					sort: sortBy,
					shownum: showNum,
					lang: lang
				};
			$.get(
				this.tool,
				payload,
				$.proxy(function(data) {
					this.showData(data);
				},this),
				'json'
			);
		},

        initEventHandlers: function() {
			$(document).on('click', '#remove_last_week', function(event) {
				var lang = $('#lang').val();
                var payload = {
                    action: 'removelastweek',
					lang: lang
				};
				$.post(this.tool, payload).done(function() {
					console.log("remove last week done");
					// refresh data
					WH.SpecialFred.getData();
				});
			});

			$(document).on('click', '#setallreviewed', function(event) {
				var lang = $('#lang').val();
                var payload = {
                    action: 'setallreviewed',
					lang: lang
				};
				$.post(this.tool, payload).done(function() {
					console.log("set all reviewed done");
					// refresh data
					WH.SpecialFred.getData();
				});
			});

			$('#lang').change( function() {
				WH.SpecialFred.getData();
			});
			$('#shownum').change( function() {
				WH.SpecialFred.getData();
			});
        },

		init: function() {
			WH.xss.addToken();

            this.startTime = this.getNow();
            var lang = $.cookie(this.langCookie);
			if (lang) {
				$('#lang').val(lang);
			}
            var showNum = $.cookie(this.showNumCookie);
			if (showNum) {
				$('#showNum').val(showNum);
			}
            this.initEventHandlers();
			this.getData();
		},

        save: function (payload, callback) {
            $.post(this.tool, payload).done($.proxy(this, callback, payload));
        },
	};
	$(document).ready(function() {
		WH.SpecialFred.init();
	});
})();
