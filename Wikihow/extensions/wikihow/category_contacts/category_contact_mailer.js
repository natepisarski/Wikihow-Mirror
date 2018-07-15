(function () {
	window.WH = window.WH || {};
	window.WH.CategoryContactMailer = {
		
		URL: '/Special:CategoryContactMailer',
		validate: true,
		
		init: function () {			
			$('#ccm_test_btn').click(function() {
				WH.CategoryContactMailer.testIt();
				return false;
			});
			
			$('#ccm_send_btn').click(function() {
				WH.CategoryContactMailer.sendEm();
				return false;
			});
			
			$('#ccm_source').blur(function() {
				WH.CategoryContactMailer.checkNum();
			});
			
			$('#ccm_num_contacted').change(function() {
				if ($(this).val() == 'any') {
					$('#ccm_ctd_range_num').fadeOut();
					$('#ccm_ctd_slider').slideUp();
				}
				else {
					$('#ccm_ctd_range_num').fadeIn();
					$('#ccm_ctd_slider').slideDown();
				}
				WH.CategoryContactMailer.checkNum();
			});
			
			this.activateSlider();
						
			//required for the autocomplete.js to work
			jQuery.fn.extend({
				propAttr: $.fn.prop || $.fn.attr
			});
		},
		
		testIt: function () {
			var email = $('#ccm_test_addresses').val();
			
			//for testing email has to be valid AND an @wikihow.com address
			if (!mw.util.validateEmail( email ) || email.indexOf('@wikihow.com') == -1) {
				$('#ccm_results').slideUp().html(mw.message('ccm_bad_email').text()).removeClass().addClass('cc_bad_result').slideDown();
				return;
			}
			
			var the_rest = '?testIt='+email+'&sub='+$('#ccm_subject').val()+'&mwm='+$('#ccm_mwm_link').val();
			
			$.get(this.URL+the_rest, function(data) {
				$('#ccm_results').html(data).removeClass().addClass('cc_good_result').slideDown();
			});
		},
		
		sendEm: function () {
			var invalid = this.validateForm();
			if (invalid) {
				$('#ccm_results').slideUp().html(invalid).removeClass().addClass('cc_bad_result').slideDown();
				return;
			}
			
			var num_users =  parseInt($('#num_users').text());
			var max = parseInt($('#ccm_max_num').val());
			var num = num_users > max ? max : num_users;

			var ctd = $('#ccm_num_contacted').val();
			
			if (ctd != 'any') {
				ctd = $('#ccm_ctd_range_num').val();
				ctd = ctd.replace(mw.message('ccm_range_delim').text(), '-');
			}
			
			if (!window.confirm(mw.message('ccm_send_confirm',num).text())) {
				return;
			}
			
			var the_rest = '?sendEm=1&cat='+$('#ccm_category').val()+'&sub='+$('#ccm_subject').val()+
									'&mwm='+$('#ccm_mwm_link').val()+'&max='+num+'&src='+$('#ccm_source').val()+'&ctd='+ctd;
			
			$.get(this.URL+the_rest, function(data) {
				$('#ccm_results').html(data).removeClass().addClass('cc_good_result').slideDown();
			});
		},
		
		validateForm: function () {
			if ($('#ccm_category').val() == '') return mw.message('ccm_err_cat').text();
			if ($('#ccm_subject').val() == '') return mw.message('ccm_err_sub').text();
			if ($('#ccm_mwm_link').val().indexOf('http://www.wikihow.com/MediaWiki') == -1) return mw.message('ccm_err_mwm').text();
			if (isNaN($('#ccm_max_num').val()) || parseInt($('#ccm_max_num').val()) < 1) return mw.message('ccm_err_max').text();
			return '';
		},
		
		checkNum: function () {
			//grab our factors
			var cat = ($('#ccm_category').val()) ? $('#ccm_category').val() : 'no_cat';
			var src = $('#ccm_source').val();
			var ctd = $('#ccm_num_contacted').val();
			
			if (ctd != 'any') {
				ctd = $('#ccm_ctd_range_num').val();
				ctd = ctd.replace(mw.message('ccm_range_delim').text(), '-');
			}
			
			var the_rest = '?getmax='+cat+'&src='+src+'&ctd='+ctd;

			if (WH.CategoryContactMailer.validate) {
				the_rest += '$validate=1';
				WH.CategoryContactMailer.validate = false;
			}
			
			$.get(this.URL+the_rest, function(data) {
				var msg = mw.message('ccm_max_msg', data).text();
				$('#ccm_total_max').html(msg);
			});
		},
		
		activateSlider: function() {
			//first get the max
			$.get(this.URL+'?slidermax=1', function(data) {
				slider_max = (data > 5) ? data : 5;
				
				$('#ccm_ctd_slider').slider({
					range: true,
					min: 0,
					max: slider_max,
					values: [0, 2],
					slide: function(e,ui) {
						$('#ccm_ctd_range_num').val( ui.values[ 0 ] + mw.message('ccm_range_delim').text() + ui.values[ 1 ] );
					},
					change: function() {
						WH.CategoryContactMailer.checkNum();
					}
				});
				$('#ccm_ctd_range_num').val( $('#ccm_ctd_slider').slider( "values", 0 ) + mw.message('ccm_range_delim').text() + $('#ccm_ctd_slider').slider( "values", 1 ) );
			});
		}
				
	};
	
	$("#ccm_category").autocomplete({
		source: function( request, response ) {
			$.ajax({
				url: "/Special:CatSearch",
				dataType: "json",
				data: {
					q: request.term
				},
				success: function( data ) {
					if (!data.results.length) {
						data.results.push({label: sorryLabel, value: sorryLabel});
					}
					response( $.map( data.results, function( item ) {
						return {
							label: item.label,
							value: item.url
						}
					}));
				}
			});
		},
		minLength: 3,
		select: function( event, ui ) {
			$("#ccm_category")
				.removeClass("ui-autocomplete-loading")
				.val(ui.item.value);
			WH.CategoryContactMailer.checkNum();
			return false;
		},
		focus: function(event, ui) { 
			$('#ccm_category').val(ui.item.label); 
			return false;
		},
	});
			
	$(document).ready(function() {
		WH.CategoryContactMailer.init();
	});
		
}());