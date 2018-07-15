$(function() {
	var ciUrl = '/Special:CategoryInterests';
	var catUrl = '/Special:Categorizer';
	var catSearchUrl = '/Special:CatSearch';
	var relatedSearch = false;
	var breadcrumbs = [];
	var sorryLabel = mw.message('cat_sorry_label');
	var catTree = undefined;
	var LEADERBOARD_REFRESH = 10 * 60;

	function init() {
		initToolTitle();
		$(".firstHeading").html($("#cat_title").html());
		$("#cat_title").hide();

		// No valid articles or JSON object. Turn off all the buttons that do anything
		if (typeof(JSON) == 'undefined' || $('#cat_aid').html() == -1) {
			$('#cat_ui, #cat_list_header').hide();	
		}
		// build the category tree
		if (typeof(JSON) == 'object') {
			catTree = JSON.parse($('#cat_tree').text());
		}

		// Test for old IEs
		var validIE = true;
		var clientPC = navigator.userAgent.toLowerCase(); // Get client info
		if (/msie (\d+\.\d+);/.test(clientPC)) { //test for MSIE x.x;
			 validIE  = 8 <= (new Number(RegExp.$1)); // capture x.x portion and store as a number
		}
		if (!validIE) {
			 $('#bodycontents').html('Error: You have an outdated browser. Please upgrade to the latest version.');
		}
		updateButtonStates();
	}

	function getCatTxt(cat) {
		return $(cat).find('.cat_txt:first').text();
	}

	$('.cat_breadcrumb').die().live('click', function(e) {
		e.preventDefault();
		var cat = $(this).text();
		var pos = $.inArray(cat, breadcrumbs) + 1;
		breadcrumbs.splice(pos, breadcrumbs.length - pos);
		updateBreadcrumbs(cat);
	});

	$('.cat_breadcrumb_add').die().live('click', function(e) {
		e.preventDefault();
		var lastCrumb = breadcrumbs.length - 1;
		var category = breadcrumbs[lastCrumb];
		addCategory(category);
	});

	$('#cat_search_button').die().live('click', function(e) {
		e.preventDefault();
		$('#cat_search').autocomplete( "search" , $('#cat_search').val());
	});

	$('#cat_search').die().live('keypress', function(e) {
		/* ENTER PRESSED*/
		if (e.keyCode == 13) {
			$('#cat_search').autocomplete( "search" , $('#cat_search').val());
		}
	});

	function updateBreadcrumbs(category) {
		$('#cat_breadcrumbs_outer').fadeIn('fast');
		var ul = $('#cat_breadcrumbs');
		ul.html('');
		$(breadcrumbs).each(function(index, item) {
			var li = $('<li/>');
			ul.append(li);
			if (index == breadcrumbs.length - 1) {
				li.append($('<span class="cat_breadcrumb" href="#breadcrumbs"/>').text(item));
				//li.append($('<a class="cat_breadcrumb_add" href="#breadcrumbs"/>').text('Add'));
			} else {
				li.append($('<a class="cat_breadcrumb" href="#breadcrumbs"/>').text(item));
			}
			if (index > 0) {
				li.prepend(' &raquo; '); 
			}
		});
		updateSubCategories(category);
	}

	function updateSubCategories(category) {
		$('.cat_subcats_outer').fadeOut('fast', function() {
			var tree = catTree;
			$('.cat_breadcrumb').each(function(k, v) {
				tree = tree[$(v).text()];		
			});

			var ul = $('#cat_subcats_list');
			ul.html('');
			if (tree instanceof Object) {
				for (var cat in tree) {
					if (category.indexOf(cat) == -1) {
						var li = $('<li/>');
						li.append($('<a class="cat_subcat" href="#breadcrumbs" />').text(cat));
						ul.append(li);
					}
				}
			} else {
				var li = $('<li/>');
				li.text('No subcategories.');
				ul.append(li);
			}
			$('.cat_subcats_outer').fadeIn('fast');
			$('.cat_subcats_outer').css('display', 'inline-block');
		});
	}

	function addCategory(category) {
		if(!validate(category)) {
			return;
		}

		if(isValid(category)) {
			$("#cat_none").css("display", "none");
			var closeSpan = $("<span/>").text('x').addClass("cat_close");
			var catTxt = $("<span/>").text(category).addClass("cat_txt");

			$("<span/>").append(catTxt).append(closeSpan).addClass("cat_category ui-widget-content ui-corner-all cat_nodisplay")
				.prependTo("#cat_list").animate({opacity: 1}, 'slow');
			updateButtonStates();
		}
	}
	
	function updateButtonStates() {
		var numCats = $('#cat_list .cat_category').length;
		if (numCats) {
			$("#cat_save, #cat_save_editpage").removeClass('disabled').addClass('cat_button_save');
		} else {
			$("#cat_save, #cat_save_editpage").removeClass('cat_button_save').addClass('disabled');
		}
	}
	function isDup(category) {
		var isDup = false;
		$("#cat_list").children().each(function(i, cat) {
			// Remove the 'x' char from the category string
			if (getCatTxt(cat) == category) {
				isDup = true;
				return false;
			}
		});
		return isDup;
	}

	function isValid(id) {
		return id != sorryLabel;
	}

	function validate(category) {
		var cat_list = $("#cat_list");
		if (isDup(category)) {
			cat_list.children().each(function(i, cat) {
				if (getCatTxt(cat) == category) {
					$(cat).addClass('cat_active');
					setTimeout(function() {$(cat).removeClass('cat_active');}, 1500);
				}
			});
			return false;
		}
		
		if(getSelectedCategories().length >= 2) {
			$('#cat_notify').slideDown('fast').delay(2000).slideUp('fast');
			return false;
		}
		return true;
	}


	$('#cat_introlink').die().live('click', function(e) {
		e.preventDefault();
		if($('#cat_article_intro').is(':visible')){
                $('#cat_article_intro').slideUp('fast');
                $('#cat_more').addClass('off');
                $('#cat_more').removeClass('on');
            }
            else{
                $('#cat_article_intro').slideDown('fast');
                $('#cat_more').addClass('on');
                $('#cat_more').removeClass('off');
            }
	});

	$('#cat_cancel').die().live('click', function (e) {
		e.preventDefault();
		$('#dialog-box').dialog('close');
	});

	$('#cat_skip').die().live('click', function (e) {
		e.preventDefault();
		var aid = $('#cat_aid').text();
		$('#cat_article').html("");
		$.get(catUrl + '?a=skip&id=' + aid, function(result) {
			$('#cat_head_outer').replaceWith(result['head']);
			$(".firstHeading").html($("#cat_title").html());
			$("#cat_title").hide();
			$('#cat_search_outer').fadeIn('fast');
			$("#cat_article").html(result['article']);
		}, 'json');
		$('#cat_head').html($('#cat_spinner').html());
		updateButtonStates();
		$('.cat_subcats_outer, #cat_breadcrumbs_outer, #cat_search_outer').fadeOut('fast');
		$('#cat_search').val('');
	});

	$('#cat_save_editpage').off().live('click', function(e) {
		e.preventDefault();

		if ($(this).hasClass('disabled')) {
			return;
		}

		var categories = [];
		// Clear out the current values of hidden fields
		$('input[name^="topcategory"], input[name^="category"]').val('');
		// Update the edit page hidden fields with the selected categories
		$($('#cat_list .cat_category').get().reverse()).each(function(i, cat) {
			$('input[name=topcategory' + i + ']').val(getCatTxt(cat));
			$('input[name=category' + i + ']').val(getCatTxt(cat));
			categories.push(getCatTxt(cat));
		});
		$('#catdiv').text(categories.join(', '));
		$('#wpSummary1').val('categorization');
		$('#dialog-box').dialog('close');
	});

	$('#cat_save').die().live('click', function (e) {
		e.preventDefault();
		saveCategories(this);
		incrementStats();
	});

	function saveCategories(context) {
		if ($(context).hasClass('disabled')) {
			return;
		}

		$('.cat_subcats_outer, #cat_breadcrumbs_outer, #cat_search_outer').fadeOut('fast');
		$('#cat_search').val('');
		var aid = $('#cat_aid').text();
		var categories = getSelectedCategories();

		$('#cat_head').html($('#cat_spinner').html());
		$('#cat_article').html("");
		updateButtonStates();

		$.get(catUrl, {a: 'complete', id: aid, cats: categories}, function(result) {
			$('#cat_head_outer').replaceWith(result['head']);
			$(".firstHeading").html($("#cat_title").html());
			$("#cat_title").hide();
			$('#cat_search_outer').fadeIn('fast');
			$("#cat_article").html(result['article']);
		}, 'json');
	}

	function getSelectedCategories() {
		var categories = [];
		$('#cat_list .cat_category').each(function(i, cat) {
			categories.push(getCatTxt(cat));
		});
		return categories;
	}

	$(".cat_close").die().live('click', function(e) {
		var catDiv = $(this).parent();
		catDiv.fadeOut('fast', function() {
			catDiv.remove();
			if ($("#cat_list").children().size() == 1) {
				$('#cat_none').css('display', 'inline-block');
				updateButtonStates();
			}
		});
	});

	$(".cat_subcat").die().live('click', function(e) {
		e.preventDefault();
		breadcrumbs.push($(this).text());
		updateBreadcrumbs($(this).text());
	});

	$("#cat_search").autocomplete({
		source: function( request, response ) {
			$.ajax({
				url: catSearchUrl,
				dataType: "json",
				data: {
					q: request.term
				},
				success: function( data ) {
					if (!data.results.length) {
						data.results.push({label: sorryLabel, value: sorryLabel});
					}
					response($.map( data.results, function( item ) {
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
			$("#cat_search").removeClass("ui-autocomplete-loading");
			var category = ui.item.label;
			if(!isValid(category)) {
				return;
			}

			$.getJSON(ciUrl + '?a=hier&cat=' + encodeURIComponent(category), function(result) {
				$.each(result, function(k, v) {
					result[k] = decodeURIComponent(v);
				});
				breadcrumbs = result;
				updateBreadcrumbs(category);
			});
			return false;
		},
		focus: function(event, ui) { 
			$('#cat_search').val(ui.item.label); 
			return false;
		}
	});

	init();

	//stat stuff
	updateStandingsTable = function() {
		var url = '/Special:Standings/CategorizationStandingsGroup';
		$.get(url, function (data) {
			$('#iia_standings_table').html(data['html']);
		}, 'json');
		$("#stup").html(LEADERBOARD_REFRESH / 60);
		window.setTimeout(updateStandingsTable, 1000 * LEADERBOARD_REFRESH);
	}

	window.setTimeout(updateWidgetTimer, 60*1000);
	window.setTimeout(updateStandingsTable, 100);

	function updateWidgetTimer() {
		WH.updateTimer('stup');
		window.setTimeout(updateWidgetTimer, 60*1000);
	}

	function incrementStats() {
		var statboxes = '#iia_stats_today_articles_categorized,#iia_stats_week_articles_categorized,#iia_stats_all_articles_categorized,#iia_stats_group';
		$(statboxes).each(function(index, elem) {
				$(this).fadeOut(function () {
					var cur = parseInt($(this).html());
					$(this).html(cur + 1);
					$(this).fadeIn();
				});
		});
	}


});
