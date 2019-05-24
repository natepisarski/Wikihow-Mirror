( function($, mw) {

var nfd_vote = 0;
var nfd_skip = 0;
var nfd_id   = 0;
var NFD_STANDINGS_TABLE_REFRESH = 600;
var show_delete_confirmation = true;

function initToolTitle() {
	$(".firstHeading").before("<h5>" + $(".firstHeading").html() + "</h5>")
}

function addOptions() {
    $('.firstHeading').before('<span class="tool_options_link">(<a href="#">Change Options</a>)</span>');
    $('.firstHeading').after('<div class="tool_options"></div>');

    $('.tool_options_link').click(function(){
        if ($('.tool_options').css('display') == 'none') {
            //show it!
            $('.tool_options').slideDown();
        } else {
            //hide it!
            $('.tool_options').slideUp();
        }
		return false;
    });
}

// asks the backend for a new article
//to patrol and loads it in the page
function getNextNFD() {
	$.get('/Special:NFDGuardian',
		{fetchInnards: true,
		  nfd_type: $.cookie('nfdrule_choices'),
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

//keeps track via cookie which nfd category
//the user wants to see
function updateChoices() {
	var choices = [];
	$("#nfd_reasons select option:selected").each(function() {
		choices.push($(this).text());
	});
	$.cookie('nfdrule_choices',choices.join(), { expires: 7 });
}

function loadResult(result) {
	//clear stuff out
	$('#nfdcontents').remove();
	//$('#nfd_reasons_link').remove();
	$('#nfd_reasons').remove();
	
	//put in new html
	$(".firstHeading").html(result['title']);
	//$(".firstHeading").before(result['nfd_reasons_link']);
	//$(".firstHeading").after(result['nfd_reasons']);
	$(".tool_options").html(result['nfd_reasons']);
	show_delete_confirmation = result['nfd_discussion_count'] > 15;

	$('#nfd_reasons select').change( function() {
		updateChoices();
	});
	$('#nfdrules_submit').click( function(e) {
		e.preventDefault();
		$('#nfd_options').slideUp();
		getNextNFD();
	});
	
	if (result['done']) {
		$("#bodycontents").before("<div id='nfdcontents' class='tool'>"+result['msg']+"</div>");
	}
	else {
		$("#bodycontents").before("<div id='nfdcontents' class='tool'>"+result['html']+"</div>");
	}

	$('.nfd_options_link').click( function(e) {
		e.preventDefault();
		displayNFDOptions();
	});

	//make all links in the info section open in a new window
	$('#nfd_article_info a').attr('target', '_blank');

	$("#nfdcontent").show();
	$(".waiting").hide();
	
	nfd_id	= result['nfd_id'];
	nfd_page = result['nfd_page'];
	$('body').data({
		article_id: nfd_id
	});

	$("#tab_article").click(function(e){
		e.preventDefault();
		getArticle();
	});

	$("#nfd_save").click(function(e){
		e.preventDefault();
		getEditor();
		return false;
	});

	$("#tab_edit").click(function(e){
		e.preventDefault();
		getEditor();
		return false;
	});

	$("#tab_discuss").click(function(e){
		e.preventDefault();
		getDiscussion();
	});

	$(".discuss_link").click(function(e){
		e.preventDefault();
		getDiscussion();
	});

	$("#tab_history").click(function(e){
		e.preventDefault();
		getHistory('/Special:NFDGuardian?history=true&articleId=' + nfd_page);
	});
	
	$("#tab_helpful").click(function(e){
		e.preventDefault();
		getHelpfulness(nfd_page);
	});

	//keep button
	$('#nfd_keep').click( function(e) {
		e.preventDefault();
		nfdVote(false);
	});
	
	//delete button
	$('#nfd_delete').click( function(e) {
		e.preventDefault();
		if (show_delete_confirmation) {
			$('#nfd_delete_confirm').dialog({
			   width: 450,
			   modal: true,
			   title: 'NFD Guardian Confirmation',
			   show: 'slide',
			   closeText: 'x',
			   closeOnEscape: true,
				position: 'center'
			});
		} else {
			nfdVote(true);
		}

	});

	$('#delete_confirmation_discussion').click(function(e){
		e.preventDefault();
		$('#nfd_delete_confirm').dialog('close');
		getDiscussion();
	});
	
	//skip
	$('#nfd_skip').click( function(e) {
		e.preventDefault();
		nfdSkip();
	});

	$('#nfd_article_info a.tooltip').hover(
		function() {
			WH.getToolTip(this,true);
		},
		function() {
			WH.getToolTip(this,false);
		}
	);
}

function submitResponse() {
	$(".nfd_tabs_content").hide();
	$(".waiting").show();
	$.post('/Special:NFDGuardian',
		{ 
		  nfd_vote: nfd_vote,
		  nfd_skip: nfd_skip,
		  nfd_type: $.cookie('nfdrule_choices'),
		  nfd_id: nfd_id
		},
		function (result) {
			if (!nfd_skip) {
				getVoteBlock();
			}
			loadResult(result);
		},
		'json'
	);
}

function nfdVote(vote) {
	nfd_vote = (vote ? 1 : 0);
	nfd_skip = 0;
	incCounters(); 
	submitResponse();
}

function nfdSkip() {
	nfd_skip = 1;
	submitResponse();
}

function updateStandingsTable() {
    var url = '/Special:Standings/nfdStandingsGroup';
    $.get(url,
		function (data) {
			$('#iia_standings_table').html(data['html']);
		},
		'json'
	);
	$("#stup").html(NFD_STANDINGS_TABLE_REFRESH / 60);
	//reset timer
	window.setTimeout(updateStandingsTable, 1000 * NFD_STANDINGS_TABLE_REFRESH);
}

function updateWidgetTimer() {
    WH.updateTimer('stup');
    window.setTimeout(updateWidgetTimer, 60*1000);
}

function getEditor() {
	$('.nfd_tabs_content').hide();
	$('.waiting').show();
	$.get('/Special:NFDGuardian', {
		edit: true,
		articleId: nfd_page,
		nfd_id:nfd_id,
		},
		function (result) {
			$('#article_tabs a').removeClass('on');
			$('#tab_edit').addClass('on');
			$('.nfd_tabs_content').hide();
			$('#articleEdit').show();
			$('.waiting').hide();
			document.getElementById('articleEdit').innerHTML = result;
			WH.Editor.restoreToolbarButtons();
			$('#wpSummary').val("Edit from NFD Guardian");
			$('#wpPreview').click(function() {
				nfd_preview = true;
			});
			//Publish button
			$('#wpSave').click(function() {
				nfd_preview = false;
				WH.usageLogs.log({
					event_action: 'publish',
				});
			});
			$('#editform').submit(function(e) {
				e.preventDefault();
				if(nfd_preview){
					var editform = $('#wpTextbox1').val();
					var url = '/index.php?action=submit&wpPreview=true&live=true';

					$.ajax({
						url: url,
						type: 'POST',
						data: 'wpTextbox1='+editform,
						success: function(data) {

							var XMLObject = data;
							var previewElement = $(data).find('preview').first();

							// Inject preview 
							var previewContainer = $('#articleBody');
							if ( previewContainer && previewElement ) {
								previewContainer.html(previewElement.first().text());
								previewContainer.slideDown('slow');
							}
						}
					});
				}
				else{
					displayConfirmation(nfd_page);
				}
			});
			
		}
	);
}

function displayConfirmation() {
	var url = '/Special:NFDGuardian?confirmation=1&articleId='+nfd_page;

	$('#dialog-box').load(url, function() {
		$('#dialog-box').dialog({
		   width: 450,
		   modal: true,
		   title: 'NFD Guardian Confirmation',
			closeText: 'x',
			closeOnEscape: true,
			position: 'center'
		});
	});
}

function closeConfirmation(bRemoveTemplate) {

	//close modal window
	$('#dialog-box').dialog('close');

	$(".waiting").show();
	$("#articleEdit").hide();
	$(window).scrollTop(0);
	$.post('/Special:NFDGuardian', {
		submitEditForm: true,
		url: $('#editform').attr('action'),
		wpTextbox1: $("#editform #wpTextbox1").val(),
		wpSummary: $("#editform #wpSummary").val(),
		//data: $('#editform').serialize(),
		removeTemplate: bRemoveTemplate,
		nfd_type: $.cookie('nfdrule_choices'),
		nfd_id: nfd_id,
		articleId: nfd_page
		},
		function (result) {
			if(bRemoveTemplate)
				getVoteBlock();
			loadResult(result);
		},
		'json'
	);

	if (bRemoveTemplate){
		incCounters();
	}
}

function getArticle() {
	$('.nfd_tabs_content').hide();
	$('.waiting').show();
	$.get('/Special:NFDGuardian', {
		article: true,
		articleId: nfd_page,
		},
		function (result) {
			$('#article_tabs a').removeClass('on');
			$('#tab_article').addClass('on');
			$('.nfd_tabs_content').hide();
			$('#articleBody').html(result);
			$('#articleBody').show();
			$('.waiting').hide();
		}
	);
}

function getDiscussion() {
	show_delete_confirmation = false;
	if ($("#articleDiscussion").is(':empty')) {
		$('.nfd_tabs_content').hide();
		$('.waiting').show();
		$.get('/Special:NFDGuardian', {
			discussion: true,
			articleId: nfd_page,
			},
			function (result) {
				$('#article_tabs a').removeClass('on');
				$('#tab_discuss').addClass('on');
				$('.nfd_tabs_content').hide();
				$('#articleDiscussion').html(result);
				$('#articleDiscussion').show();
				$('.waiting').hide();
			}
		);
	} else {
		$('#article_tabs a').removeClass('on');
		$('#tab_discuss').addClass('on');
		$('.nfd_tabs_content').hide();
		$('#articleDiscussion').show();
	}
}

function getHistory(url) {
	$('.nfd_tabs_content').hide();
	$('.waiting').show();
	$.get(url,
		function(result) {
			$('#article_tabs a').removeClass('on');
			$('#tab_history').addClass('on');
			$('.nfd_tabs_content').hide();
			$('#articleHistory').html("<div id='bodycontents' class='minor_section bc_history'>" + result + "</div>");
			$('#articleHistory').show();
			$('.waiting').hide();
			//make all the links in the history table open in a new window
			$('#pagehistory a').attr('target', '_blank');

			$('#articleHistory a').not('#pagehistory a').click(function(e){
				e.preventDefault();
				getHistory($(this).attr("href"));
			});
			$('#mw-history-compare').submit(function(e) {
				e.preventDefault();
				//just a preview?

				$('#articleHistory').hide();
				$('.waiting').show();

				//get diff and show in main page
				$.get('/Special:NFDGuardian', {
					diff: $("#pagehistory input[name!='oldid']:checked").val(),
					articleId: nfd_page,
					oldid: $("#pagehistory input[name='oldid']:checked").val()
					},
					function (result) {
						$('#article_tabs a').removeClass('on');
						$('#tab_article').addClass('on');
						$('.nfd_tabs_content').hide();
						$('#articleBody').html(result);
						$('#articleBody').show();
						$('.waiting').hide();
					}
				);
			});
		}
	);
}

function getHelpfulness(articleId) {
	// set a global that is used by the page helpfulness Javascript
	wgPageHelpfulnessArticleId = articleId;
	$('.nfd_tabs_content').hide();
	$('.waiting').show();
	$.get('/Special:NFDGuardian?helpful=true&articleId=' + articleId,
		function (result) {
			$('#article_tabs a').removeClass('on');
			$('#tab_helpful').addClass('on');
			$('.nfd_tabs_content').hide();
			$('#articleHelpfulness').show();
			$('#page_helpfulness_box').html(result);
			$('.waiting').hide();
		}
	);
}

function incCounters() {
	$("#iia_stats_week_nfd, #iia_stats_today_nfd, #iia_stats_all_nfd, #iia_stats_group").each(function (index, elem) {
			$(this).fadeOut(function () {
				var statValues_string = $(this).html();

				// Removing commas since parseInt doesn't handle them inherently
				var statValues_withoutCommas = statValues_string.replace(/,/g,"");
				var val = parseInt(statValues_withoutCommas, 10) + 1;

				// Adding thousand-separator commas to improve readability
				val = val.toLocaleString();
				$(this).html(val);
				$(this).fadeIn(); 
			});
		}
	); 
}

function getVoteBlock() {
	var vote_block = '';

	$.get('/Special:NFDGuardian', {
		getVoteBlock: true,
		nfd_id: nfd_id,
		},
		function (result) {
			$('#nfd_voteblock').remove();

			vote_block = "<div id='nfd_voteblock' class='sidebox'>" + result + "</div>";

			$('#top_links').after(vote_block);

			//animate in
			$('#nfd_voteblock').animate({
				"height": "toggle",
				"opacity": "toggle"
				}, {duration: 800});

			//tooltip for changed by
			$('.nfd_avatar a.tooltip').hover(
				function() {
					WH.getToolTip(this,true);
				},
				function() {
					WH.getToolTip(this,false);
				}
			);
		}
	);

}

// show/hide checkboxes
function displayNFDOptions() {

	if ($('#nfd_reasons').css('display') == 'none') {
		//show it!
		$('#nfd_reasons').slideDown();
	}
	else {
		//hide it!
		$('#nfd_reasons').slideUp();
	}
}

// Used exclusively by NFD guardian; moved from wikihowbits.js
function button_click(obj) {
	if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
		return false;
	}
	jobj = $(obj);

	background = jobj.css('background-position');
	if(background == undefined || background == null)
		background_x_position = jobj.css('background-position-x');
	else
		background_x_position = background.split(" ")[0];

	//article tabs
	if (obj.id.indexOf("tab_") >= 0) {
		obj.style.color = "#514239";
		obj.style.backgroundPosition = background_x_position + " -111px";
	}

	if (obj.id == "play_pause_button") {
		if (jobj.hasClass("play")) {
			obj.style.backgroundPosition = "0 -130px";
		}
		else {
			obj.style.backgroundPosition = "0 -52px";
		}
	}


	if (jobj.hasClass("search_button")) {
		obj.style.backgroundPosition = "0 -29px";
	}
}

$("document").ready(function() {
	window.setTimeout(updateWidgetTimer, 60*1000);
	window.setTimeout(updateStandingsTable, 1000 * NFD_STANDINGS_TABLE_REFRESH);

	$('#article_shell').on( 'click', '.nfdg_tab',
		function() {
			button_click(this);
		} );

	$('body').on( 'click', '.nfdg_confirm_action',
		function() {
			var action = $(this).data('event_action');
			// No button == keep template, Yes == remove
			var removeTemplate = (action == 'template_remove');
			closeConfirmation(removeTemplate);
			return false;
		} );

	initToolTitle();
	addOptions();
	getNextNFD();
	$('#nfd_delete_confirm .no').click(function(e){
		e.preventDefault();
		$('#nfd_delete_confirm').dialog('close');
	});
	$('#nfd_delete_confirm .yes').click(function(e){
		e.preventDefault();
		$('#nfd_delete_confirm').dialog('close');
		nfdVote(true);
	});
	$('body').data({
		event_type: 'nfd_guardian',
	});
});

}(jQuery, mediaWiki) );
