( function($, mw) {

var qc_vote = 0; 
var qc_skip = 0;
var qc_id   = 0;
var QC_STANDINGS_TABLE_REFRESH = 600; 

$(document).ready(function() {
	initToolTitle();
	getNextQC();
});

function qgGetOption(optName) {
	var cookie = $.cookie(optName);

	if (typeof cookie == 'undefined') {
		return "";
	}

	return cookie;
}

function getNextQC() {
	// grab options

	$.get('/Special:QG',
		{ fetchInnards: true,
		  qc_type: qgGetOption('qcrule_choices'),
		  by_username: qgGetOption('qg_byusername')
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

// keep hidden input list of QC choices
function updateChoices() {
	var choices = [];
	$("#qg_options input:checked").each(function() {
		choices.push($(this).attr('id'));
	});
	$.cookie('qcrule_choices',choices.join(), { expires: 7 });
}

function QG_byUserName(name) {
	$.cookie('qg_byusername',$.trim(name));
}

function loadResult(result) {
	// clear stuff out
	$('#qccontents').remove();
	$('#qg_tabs').remove();
	$('#qg_submenu').remove();

	$('body').data({
		event_type: 'quality_guardian',
		assoc_id:  (result.qc_id == -1) ? result.pqt_id : result.qc_id,
		label: (result.qc_id==-1) ? 'plant' : ''
	});

	// add in stuff
	$(".firstHeading").html(result['title']);

	$(".firstHeading").before(result['qctabs']);
	$(".firstHeading").after(result['choices']);
	
	if (result['done']) {
		$("#bodycontents").before("<div id='qccontents' class='tool'>"+result['msg']+"</div>");
	}
	else {
		$("#bodycontents").before("<div id='qccontents' class='tool'>"+result['buttons']+result['html']+"</div>");
		// Fire event to initialize wikivideo
		$(document).trigger('rcdataloaded');
	}
	
	qc_id = result['qc_id'];

	$("#question").html(result['question']);
	$("#question").after($(".qc_by"));
	$("#quickeditlink").html(result['quickedit']);

	//are we patrolling by a user?
	if (qgGetOption('qg_byusername') !== '') {
		openSubMenu('byuser');
	}
	
	//change options tab
    $('#qgtab_options').click(function(){
		openSubMenu('options');
		return false;
    });
	
	//by user tab
    $('#qgtab_byuser').click(function(){
		openSubMenu('byuser');
		return false;
    });
	
	//yes button
	$('#qc_yes').click( function() {
		if (!$(this).hasClass('clickfail')) {
			qcVote(true);
		}
		return false;
	});
	
	//no button
	$('#qc_no').click( function() {
		if (!$(this).hasClass('clickfail_2')) {
			qcVote(false);
		}
		return false;
	});	
	
	//skip
	$('#qc_skip').click( function() {
		if (!$(this).hasClass('clickfail')) {
			qcSkip();
		}
		return false;
	});
	
	//tooltip for changed by
	$('#qc_changedby a.tooltip').hover(
		function() {
			WH.getToolTip(this,true);
		},
		function() {
			WH.getToolTip(this,false);
		}
	);
	
	var e = $('#numqcusers');
	if (e.html() != "0") {
		e = $("#mw-diff-ntitle2 #mw-diff-oinfo");
		if (e.html() && e.html().indexOf("and others") < 0) {
			$( "#mw-diff-ntitle2 #mw-diff-oinfo #mw-diff-ndaysago" ).before( "<b>and others</b>." );
		}
	}
}

$(document).bind('rcdataloaded', function () {
	WH.showEmbedVideos();
});

function submitResponse() {
	disableButtons();
	
	$.post('/Special:QG',
		{ 
		  qc_vote: qc_vote,
		  qc_skip: qc_skip,
		  qc_type: qgGetOption('qcrule_choices'),
		  by_username: qgGetOption('qg_byusername'),
		  qc_id: qc_id,
		  event_type: $('body').data('event_type'),
		  pqt_id: $("#pqt_id").val()
		},
		function (result) {
			if (!qc_skip) {
				console.log('voteblock');
				getVoteBlock();
			}
			loadResult(result);
		},
		'json'
	);
}

function disableButtons() {
	$('#qc_yes, #qc_skip_div a').addClass('clickfail');
	$('#qc_no').addClass('clickfail_2');
	$('#qc_skip_arrow').css('background-position','-165px -13px');
	$('#qc_yes, #qc_no').removeAttr('onmouseover');
	$('#qc_yes, #qc_no').removeAttr('onmouseout');
}

// show/hide checkboxes
function displayQCOptions(menuName) {

	$.get('/Special:QG',
		{ getOptions: true,
		  menuName: menuName,
		  choices: qgGetOption('qcrule_choices'),
		  username: qgGetOption('qg_byusername'),
		},
		function (result) {
			$('#qg_options').html(result);
			
			if (menuName == 'options') {
				//options
				$('input:checkbox').click( function() {
					updateChoices();
				});
				$('#qcrules_submit').click( function() {
					openSubMenu(menuName); //turn off
					getNextQC();
					return false;
				});
			}
			else {
				//by user
				$('#qg_byuser_go').click( function() {
					QG_byUserName($('#qg_byuser_input').val());
					getNextQC();
					return false;
				});
				$('#qg_byuser_off').click( function() {
					QG_byUserName('');
					openSubMenu(menuName); //turn off
					getNextQC();
					return false;
				});
			}
		}
	);
}

function openSubMenu(menuName){
	var menu = $('#qg_submenu');
	var choice = $('#qgtab_' + menuName);

	if (choice.hasClass('on')) {
		//turn it off
		menu.hide();
		choice.removeClass('on');
	}
	else {
		//engage!
		$(".tableft").removeClass("on"); //clear all, then add		
		choice.addClass("on");
		displayQCOptions(menuName);
		menu.show();
	}
}

function qcVote(vote) {
	qc_vote = (vote ? 1 : 0);
	qc_skip = 0; 
	incCounters(); 
	submitResponse();
}

function qcSkip() {
	qc_skip = 1; 
	submitResponse();
}

function getVoteBlock() {
	var vote_block = '';
	
	$.get('/Special:QG', { 
		getVoteBlock: true,
		qc_id: qc_id,
		},
		function (result) {
			$('#qc_voteblock,#qc_voteblock_top,#qc_voteblock_bottom').remove();

			if (result != "") {
			
				vote_block = "<div id='qc_voteblock'>" + result + "</div>";

				$('#top_links').after(vote_block);

				//animate in
				$('#qc_voteblock').animate({
					"height": "toggle",
					"opacity": "toggle"
					}, { duration: 800 });

				//tooltip for changed by
				$('.qc_avatar a.tooltip').hover(
					function() {
						WH.getToolTip(this,true);
					},
					function() {
						WH.getToolTip(this,false);
					}
				);
			}
		}
	);	

}

updateStandingsTable = function() {
    var url = '/Special:Standings/QCStandingsGroup';
    jQuery.get(url, function (data) {
        jQuery('#iia_standings_table').html(data['html']);
    },
	'json'
	);
	$("#stup").html(QC_STANDINGS_TABLE_REFRESH / 60);
	//reset timer
	window.setTimeout(updateStandingsTable, 1000 * QC_STANDINGS_TABLE_REFRESH);
}

window.setTimeout(updateWidgetTimer, 60*1000);
window.setTimeout(updateStandingsTable, 1000 * QC_STANDINGS_TABLE_REFRESH);

function updateWidgetTimer() {
    WH.updateTimer('stup');
    window.setTimeout(updateWidgetTimer, 60*1000);
}

function incCounters() {
	$("#iia_stats_week_qc, #iia_stats_today_qc, #iia_stats_all_qc").each(function (index, elem) {
			$(this).fadeOut(function () {
				val = $(this).html().replace(/,/g,"");
				val = parseInt(val) + 1;
				$(this).html(val);
				$(this).fadeIn(); 
			});
		}
	); 
}

}(jQuery, mediaWiki) );
