(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.BibleCitation = {

		citationData: {},

		init: function() {
			this.addHandlers();
		},

		addHandlers: function() {
			$('#bible_citation_widget form').submit($.proxy(function() {
				this.makeCitation();
				return false;
			},this));

			$('#bible_citation_create').click($.proxy(function() {
				this.clearForm();
				this.showForm();
				WH.maEvent('bible_citation_new_citation');
				return false;
			},this));

			$('#bible_citation_copy').click($.proxy(function() {
				this.copyToClipboard();
				WH.maEvent('bible_citation_copy_citation');
				return false;
			},this));

			$('#bible_citation_widget').on('focus', '.error', function() {
				$(this).removeClass('error');
				$('#bible_citation_notification').html('').hide();
			})

			var action = WH.isMobileDomain ? 'click' : 'mouseover mouseout';
			$('#bible_citation_widget').on(action, '.question_mark', $.proxy(function() {
				this.toggleEditionTip();
			},this));
		},

		makeCitation: function() {
			var values = $('#bible_citation_widget form').serializeArray();

			$(values).each($.proxy(function(i, field){
			  this.citationData[field.name] = this.escapeHtml(field.value);
			},this));

			if (!this.validCitation()) return;

			WH.maEvent('bible_citation_submit', { 'format': this.citationData['format'] });

			var vars = {
				'MLA8': this.citationData['format'] == 'MLA 8',
				'APA': this.citationData['format'] == 'APA',
				'entire': this.citationData['section'] == 'Entire source',
				'optionals': this.formatOptionals()
			};
			vars = $.extend(vars, this.citationData);

			var html = this.escapeHtml(Mustache.render(unescape($('#bible_citation').html()), vars));
			if (html) {
				var result_msg = mw.message('bible_citation_complete',vars.format).text();
				$('#bible_citation_widget form').hide();
				$('#bible_citation_message').html(result_msg);
				$('#bible_citation_string').html(html);
				$('#bible_citation_result').show();

				if (WH.isMobileDomain) {
					var positionTop = $('#bible_citation_widget').position().top - 50;
					$('html, body').animate({scrollTop: positionTop}, 300);
				}
			}
		},

		validCitation: function() {
			//reset to revalidate
			$('#bible_citation_notification').html('').hide();
			$('#bible_citation_widget').removeClass('error');

			var bad_inputs = [];

			if (this.citationData['title'] == '') bad_inputs.push('title');
			if (this.citationData['publisher'] == '') bad_inputs.push('publisher');
			if (this.citationData['year'] == '' ||
				this.citationData['year'].length != 4 ||
				isNaN(this.citationData['year'])) bad_inputs.push('year');
			if (!this.formatEdition()) bad_inputs.push('edition');
			if (this.citationData['format'] == 'APA' && this.citationData['city'] == '') bad_inputs.push('city');

			if (bad_inputs.length) {
				var err_msg = mw.message('bible_citation_error').text();
				$('#bible_citation_notification').html(err_msg).show();

				bad_inputs.forEach(function(bad_input) {
					$('#bible_citation_notification').addClass('error');
					$('#bcw_'+bad_input).addClass('error').val('');

					if (bad_input == 'edition') $('#bible_citation_widget .question_mark').show();
				});
			}

			if (bad_inputs.length) {
				document.activeElement.blur();
				WH.maEvent('bible_citation_error', { 'errors': bad_inputs.toString() });
			}

			return bad_inputs.length == 0;
		},

		//return false if bad
		formatEdition: function() {
			//optional, so it can be empty
			if (this.citationData['edition'] != '') {

				//always strip the "edition"
				this.citationData['edition'] = this.citationData['edition'].replace(/\sedition/i, '');

				if (!this.citationData['edition'].match(/^\d+(?:s|t|r|n)/i)) {
					//gotta be number w/ ordinal suffix (e.g. 1st or 2nd)
					return false;
				}
			}

			return true; //all good
		},

		showForm: function() {
			$('#bible_citation_result').hide();
			$('#bible_citation_widget form').show();
			$('#bible_citation_message').html('');
			$('#bible_citation_string').html('');
			$('#bible_citation_notification').html('').hide();
		},

		clearForm: function() {
			$('#bible_citation_widget form :input')
			  .val('')
			  .prop('checked', false)
			  .prop('selected', false);
		},

		formatOptionals: function() {
			var optionals = '';
			var edition = this.citationData['edition'];
			var volume = this.citationData['volume'];
			var format = this.citationData['format'];

			var vol_label = format == 'MLA 8' && edition && volume ? 'vol. ' : 'Vol. ';

			if (edition && volume) {
				optionals = edition+' ed., '+vol_label+volume;
			}
			else if (edition && !volume) {
				optionals = edition+' ed.';
			}
			else if (!edition && volume) {
				optionals = vol_label + volume;
			}

			return optionals;
		},

		escapeHtml: function (htmlString) {
			var div = document.createElement("div");
			div.innerHTML = htmlString;
			return div.textContent || div.innerText || "";
		},

		copyToClipboard: function() {
			var citation = document.getElementById('bible_citation_string');
			citation.focus();
			document.execCommand("selectAll");
			document.execCommand("copy");

		  this.showCopiedMessage();
		},

		showCopiedMessage: function() {
			var copied_msg = mw.message('bible_citation_copied').text();
			$('#bible_citation_notification')
				.removeClass('error')
				.html(copied_msg)
				.show()
				.delay(3000).slideUp();
		},

		toggleEditionTip: function() {
			var dialog = $('#bcw_edition_tip');
			$(dialog).is(':visible') ? $(dialog).hide() : $(dialog.show());
		}
	}

	WH.BibleCitation.init();
})(jQuery, mw);