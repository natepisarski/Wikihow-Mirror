(function($) {
	window.WH = window.WH || {};
	window.WH.CatSearchUI = {

		ci_url: '/Special:CategoryInterests',
		sorryLabel: "Sorry, nothing found. Try another search.",

		addInterest: function(message, id) {
			if (this.isDup(id)) {
				$("#categories").children().each(function(i, cat) {
					if ($(cat).children('div:first').html() == id) {
						$(cat).addClass('csui_active');
						setTimeout(function() {$(cat).removeClass('csui_active');}, 1500);
					}
				});
				return;
			}

			if(this.isValid(id)) {
				$.get(this.ci_url, {a: 'add', cat: id}, function(data) {
					$("#csui_none").addClass("csui_hidden");
					$("#csui_interests_label").removeClass("csui_hidden");
					var urlDiv = $("<div/>").text(id).addClass("csui_hidden");
					var closeSpan = $("<span/>").html("x").addClass("csui_close");
					$( "<div/>" ).text(message).append(closeSpan).append(urlDiv).addClass("csui_category ui-widget-content ui-corner-all csui_nodisplay").prependTo( "#categories" ).slideDown('fast');
					$('#csui_interests').val('');
				});
			}
		},
	
		isDup: function(id) {
			var isDup = false;
			$("#categories").children().each(function(i, cat) {
				if ($(cat).children('div:first').html() == id) {
					isDup = true;
					return false;
				}
			});

			return isDup;
		},

		isValid: function(id) {
			return id != this.sorryLabel;
		},
	
		initAC: function() {
			//required for the autocomplete.js to work
			jQuery.fn.extend({
				propAttr: $.fn.prop || $.fn.attr
			});

			$('#csui_interests').autocomplete({
				source: function( request, response ) {
					$.ajax({
						url: "/Special:CatSearch",
						dataType: "json",
						data: {
							q: request.term
						},
						success: function( data ) {
							if (!data.results.length) {
								data.results.push({label: this.sorryLabel, value: this.sorryLabel});
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
					$("#csui_interests").removeClass("ui-autocomplete-loading");
					WH.CatSearchUI.addInterest(ui.item.label, ui.item.value);
					return false;
				},
				focus: function(event, ui) { 
					$('#csui_interests').val(ui.item.label); 
					return false;
				},
			});
		},

		init: function() {
			$('#csui_close_popup').live('click', function (e) {
				$('#editfinder_interests').slideUp(function() { 
					if ($('#ef_num_cats').val() != $(".csui_category").size()) {
						window.location.reload();
					}
				});
			});

			$(".csui_close").live('click', function(e) {
				var interestDiv = $(this).parent();
				var interest = $(this).parent().children('div:first').text();
				$.get(WH.CatSearchUI.ci_url, {a: 'remove', cat: interest}, function(data) {
					$(interestDiv).slideUp('fast', function() {
						$(this).remove();

						if ($("#categories").children().size() == 1) {
							$('#csui_none').removeClass('csui_hidden');
							$('#csui_interests_label').addClass('csui_hidden');
						}
					});
				});
			});

			$(".csui_suggestion").live('click', function(e) {
				var id = $(this).children('div:first').text();
				var label = id.replace(/-/g, " ");
				WH.CatSearchUI.addInterest(label, id);
			});
		}
	};
		
	WH.CatSearchUI.init();
	
}(jQuery));