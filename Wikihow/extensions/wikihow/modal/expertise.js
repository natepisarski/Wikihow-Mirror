(function () {
	window.WH = window.WH || {};
	window.WH.CategoryExpertise = {
		
		cats: [],
		sorryLabel: mw.message('expertise_sorry').text(),
		
		popModal: function () {
			var url = "/extensions/wikihow/common/jquery.simplemodal.1.4.4.min.js";
			$.getScript(url, function() {
				$.get("/Special:BuildWikihowModal?modal=expertise", function(data) {
					$.modal(data, { 
						zIndex: 100000007,
						maxWidth: 400,
						minWidth: 400,
						overlayCss: { "background-color": "#000" }
					});
					WH.CategoryExpertise.prep();
					WH.CategoryExpertise.updateSugCats();
					
					//add dashboard tour cookie
					mw.loader.using('ext.guidedTour.lib', function() { mw.guidedTour.setTourCookie('dashboard'); });
					
					WH.maEvent("cat_dialog_step1_show", { category: 'cat_dialog' }, false);
				});
			});
		},
		
		prep: function () {
			//required for the autocomplete.js to work
			jQuery.fn.extend({
				propAttr: $.fn.prop || $.fn.attr
			});
			
			$(document).on('click', '.list_x', function() {
				$(this).parent().remove();
			});
			
			$(document).on('click', '#expertise_sug_cats a', function() {
				WH.CategoryExpertise.addToList($(this).text(),$(this).attr('id'));
				return false;
			});
			
			$('#wh_modal_close').click(function() {
				gatTrack('profile_interest_prompt', 'close');
				WH.maEvent("cat_dialog_step1_x", { category: 'cat_dialog' }, false);
				$.modal.close();
			});
			
			$('#wh_modal_btn_skip').click(function() {
				gatTrack('profile_interest_prompt', 'skip');
				WH.maEvent("cat_dialog_step1_skip", { category: 'cat_dialog' }, false);
				$.modal.close();
			});
			
			$('#wh_modal_btn_prompt').click(function() {
				gatTrack('profile_interest_prompt', 'done', WH.CategoryExpertise.cats.length);
				WH.maEvent("cat_dialog_step1_done", { category: 'cat_dialog', num_cats: WH.CategoryExpertise.cats.length }, false);
				
				if (WH.CategoryExpertise.cats.length) {
					$.post('/Special:CategoryExpertise', { 'cats' : WH.CategoryExpertise.cats.toString(), 'email' : $('#expertise_anon_email').val() } )
					.done(function() {
						WH.CategoryExpertise.popFollowUp(WH.CategoryExpertise.cats[0]); //just grab the first one
					});
				}
				else {
					$.modal.close();
				}
			});
			
			$("#expertise_cat").autocomplete({
				source: function( request, response ) {
					$.ajax({
						url: "/Special:CatSearch",
						dataType: "json",
						data: {
							q: request.term
						},
						success: function( data ) {
							if (!data.results.length) {
								data.results.push({label: WH.CategoryExpertise.sorryLabel, value: WH.CategoryExpertise.sorryLabel});
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
					$("#expertise_cat")
						.removeClass("ui-autocomplete-loading")
						.val('');
					WH.CategoryExpertise.addToList(ui.item.label,ui.item.value);
					return false;
				},
				focus: function(event, ui) { 
					$('#expertise_cat').val(ui.item.label); 
					return false;
				},
			});
		},
		
		addToList: function (cat,catdash) {
			if (cat == WH.CategoryExpertise.sorryLabel) return;
			
			this.cats.push(catdash);
			var newcat = '<div class="list_cat"><div class="list_x">x</div>'+cat+'</div>';
		
			//new or adding?
			if ($('#wh_modal_eg_list').length) {
				$('#expertise_cat').attr('placeholder','');
				$('#wh_modal_eg_hdr').html(mw.message('expertise_interests_hdr').text());
				$('#wh_modal_eg_list').remove();
				$('#wh_modal_interests').html(newcat);
				
				//add email box for anons
				if (!mw.config.get('wgUserId')) $('#expertise_anon').show();
			}
			else {
				$('#wh_modal_interests').append(newcat);
			}
			
			this.updateSugCats();
		},
		
		//get suggested categories based on what the user has already chosen
		updateSugCats: function() {
			var cat = this.cats.length == 0 ? '' : this.cats.slice(-1).pop();
			
			$.get('/Special:CategoryInterests?a=suggnew&cat='+cat, function(data) {
				sugcats = WH.CategoryExpertise.formatSugCats(data['suggestions']);
				if (sugcats) {
					$('#expertise_sug_cats').html(sugcats);
				}
				else {
					$('.expertise_suggest').hide();
				}
			},'json');
		}, 
		
		//take an array of categories and format them for display
		formatSugCats: function(sugcats) {
			var cat_string = '';
			var count = 0;
			
			$(sugcats).each(function(i, cat) {
				//add dashes for spaces
				cat = cat.replace(/ /g,'-');
				
				//skip if it's already listed
				if ($.inArray(cat, WH.CategoryExpertise.cats) >= 0) return;
				
				//add a comma when it's needed
				if (count > 0) cat_string += ', ';
				
				//add the link
				cat_string += '<a href="#" id="'+cat+'">'+cat.replace(/-/g,' ')+'</a>';
				
				//up the count
				count++;
				
				//3? we're done...
				if (count == 3) return false;
			});
			
			return cat_string;
		},
		
		//expertise_2 modal dialog
		popFollowUp: function(cat) {
			mw.loader.using('ext.wikihow.expertise_modal_2', function() {
				$.modal.close();
				WH.CategoryExpertise2.popModal(encodeURIComponent(cat));
			});
		}
		
	};
	
	$(document).ready(function() {
		WH.CategoryExpertise.popModal();
	});
		
}());