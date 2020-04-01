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
		orderByCookie: 'spfred_orderby',
		orderByDirectionCookie: 'spfred_orderbydirection',

		createRow: function(line) {
			// update the heading which has the current sort
			var direction = $('#orderbydirection').data('direction');
			var orderby = $('#orderby').text()

			var res = jQuery('<tr/>', {
				class: 'data-line',
			});

			var edit = jQuery('<td/>', {
				class: 'edit data-element',
			});
			edit.html('<a href="#" class="edit-line">Edit</a>');
			res.append(edit);

			for (var key in line) {
				var column = jQuery('<td/>', {
					class: key,
				});
				column.addClass('data-element');
				column.attr('data-name', key);
				if (key == orderby) {
					column.addClass('orderby-selected');
					column.addClass(direction);
					column.attr('data-order', direction);
				}
				column.html(line[key]);

				res.append(column);
			}
			return res;
		},

		deleteRow: function(target) {
			var pageId =  $(target).parent().parent().find('.article_id').text();
			var lang = $('#lang').val();
			var payload = {
				action: 'deleterow',
				lang: lang,
				pageid: pageId,
			};
			$.post(this.tool, payload).done(function() {
				// refresh data
				WH.SpecialFred.getData();
			});
		},

		showData: function(data) {

			$('#data').empty();
			for (var i = 0; i < data.length; i++ ) { var line = this.createRow(data[i]);
				$('#data').append(line);
			}
			$('.data-line:first').addClass("first-line");

			$( ".edit-line" ).contextmenu(function(e) {
				e.preventDefault();
				var answer = confirm("Delete this row?");
				if (answer == false) {
					return;
				}
				// delete row
				WH.SpecialFred.deleteRow(e.target);
			});

		},

        getData: function() {
			var lang = $('#lang').val();
			$.cookie(this.langCookie, lang);

			var showNum = $('#shownum').val();
			$.cookie(this.showNumCookie, showNum);

			var orderBy = $('#orderby').text();
			$.cookie(this.orderByCookie, orderBy);

			var orderByDirection = $('#orderbydirection').data('direction');
			$.cookie(this.orderByDirectionCookie, orderByDirection);

			var payload = {
					action: 'get_data',
					shownum: showNum,
					orderby: orderBy,
					orderbydirection: orderByDirection,
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
			$(document).on('click', '.edit-line', function(event) {
				event.preventDefault();
				event.stopPropagation();

				var line = event.target.parentElement.parentElement;
				var fields = ['status', 'reviewed', 'error', 'photo_cnt', 'vid_cnt']; 

				if ($(this).hasClass('save')) {
					var saveData = {};
					// save results
					var x;
					for (x in fields) {
						var field = fields[x];
						var className = '.' + field;
						//var input = $(line).find(className).find('input');
						var value = $(line).find(className).find('input').val();
						if (value) {
							saveData[field] = value;
						}
					}

					if (Object.keys(saveData).length) {
						// TODO add save confirmation

						var lang = $('#lang').val();
						var pageId = $(line).find('.article_id').text();
						var payload = {
							action: 'saveline',
							lang: lang,
							pageid: pageId,
							savedata: saveData
						};
						$.post(this.tool, payload).done(function() {
							// refresh data
							WH.SpecialFred.getData();
						});
					}


					// refresh
					//WH.SpecialFred.getData();
					return;
				}


				var x;
				for (x in fields) {
					var field = fields[x];
					field = '.'+field;
					var defaultValue = $(line).find(field).text();
					$(line).find(field).html('<input type="text"></input>');
					if (defaultValue) {
						$(line).find(field).find('input').attr('placeholder', defaultValue);
					}
				}
				$(this).addClass("save");
				$(this).text("Save");

			});


			$(document).on('click', '.first-line', function(event) {
				// change the sort
				var orderBy = event.target.getAttribute('data-name');
				if(!orderBy) {
					return;
				}
				$('#orderby').text(orderBy);

				var oldDirection = event.target.getAttribute('data-order');


				// default sort
				var direction = 'desc';

				if (oldDirection == 'desc') {
					direction = 'asc';
				} 
				var t = direction + "ending";
				$('#orderbydirection').text(t);
				$('#orderbydirection').data('direction', direction);
				WH.SpecialFred.getData();
			});


			$(document).on('click', '#remove_last_week', function(event) {
				event.preventDefault();
				var lang = $('#lang').val();
                var payload = {
                    action: 'removelastweek',
					lang: lang
				};
				$.post(this.tool, payload).done(function() {
					// refresh data
					WH.SpecialFred.getData();
				});
			});

			$(document).on('click', '#setallreviewed', function(event) {
				event.preventDefault();
				var answer = confirm("Mark all items as reviewed?");
				if (answer == false) {
					return;
				}
				var lang = $('#lang').val();
                var payload = {
                    action: 'setallreviewed',
					lang: lang
				};
				$.post(this.tool, payload).done(function() {
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
				$('#shownum').val(showNum);
			}

            var orderBy = $.cookie(this.orderByCookie);
			if (orderBy) {
				$('#orderby').text(orderBy);
			}
			$('#orderby').show();

            var orderByDirection = $.cookie(this.orderByDirectionCookie);
			if (orderByDirection) {
				var t = orderByDirection + "ending";
				$('#orderbydirection').text(t);
				$('#orderbydirection').data('direction', orderByDirection);
			}
			$('#orderbydirection').show();
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
