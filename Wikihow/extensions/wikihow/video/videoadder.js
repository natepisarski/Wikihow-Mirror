( function($, mw) {

var updateInterval = 10;

// This function was added by Reuben for testing/debugging live
function whdbg(str, obj) {
	if (typeof console != 'undefined' && typeof console.log == 'function') {
		console.log('wh:', str, obj);
	}
}

function loadNext() {
	var url = "/Special:VideoAdder/getnext";
	var cat = $("#va_cat").val();
	var page =  $("#va_page_id").val();
	var vid = $("#va_vid_id").val();
	var src = $("#va_src").val();
	var skip = $("#va_skip").val();
	var title = $("#va_page_title").val();
	var url = $("#va_page_url").val();
	$("#va_guts").html("<center><img src='/extensions/wikihow/rotate.gif'/></center>");
	$("#va_article").html("");
	
	$.get("/Special:VideoAdder/getnext",
		{
			va_cat: cat,
			va_page_id: page,
			va_vid_id: vid,
			va_src: src,
			va_skip: skip
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
	/*$("#va_guts").load("/Special:VideoAdder/getnext",
		{
			va_cat: cat,
			va_page_id: page,
			va_vid_id: vid, 
			va_src: src, 
			va_skip: skip
		},
		setLinks
	);*/
	if (skip == 0 && $.cookie("wiki_sharedVANoDialog") != "yes") {
		article = "<a href='" + url + "' target='new'>How to " + title + "</a>";
		congrats_msg = mw.message('va_congrats', article);
		$("#dialog-box").html( congrats_msg + " <br/><br/><input type='checkbox' id='dontshow' style='margin-right:5px;'> " + mw.message('va_check') + " <a onclick='WH.VideoAdder.closeDialog();' class='button white_button_100 ' style='float:right;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'>OK</a>");
		$("#dialog-box").dialog( {
			modal: true,
			title: 'Congratulations',
			show: 'slide',
			closeOnEscape: true,
			jposition: 'center',
			height: 200,
			width: 400,
			closeText: 'x'
		});
	}
}

function loadResult(results) {
	$("#va_guts").html(results['guts']);
	$("#va_article").html(results['article']);
	$(".tool_options").html(results['options']);
	setLinks();
}

function setLinks() {
	$('#va_yes').click(function(e){e.preventDefault()});

	// wait 30 seconds before showing the buttons
	var delayms = 30000;
	// For debugging, Anna and Reuben have shorter delays
	var userName = mw.config.get('wgUserName');
	if (userName == 'Reuben' || userName == 'Reuben24' || userName == 'Anna') {
		delayms = 1000;
	}

	$('#va_notice').delay(delayms).slideUp(function() {
		$('#va_yes')
			.removeClass('disabled')
			.click(function(){
				submitForm(true);
				return false;
			});
	});
	
	$('#va_no').click(function(){
		submitForm(false);
		return false;
	});

	$('#va_introlink').click(function(){
		if($('#va_articleintro').is(':visible')){
			$('#va_articleintro').hide();
			$('#va_more').addClass('off');
			$('#va_more').removeClass('on');
		}
		else{
			$('#va_articleintro').show();
			$('#va_more').addClass('on');
			$('#va_more').removeClass('off');
		}
		return false;
	});

	// move the article title to the top
	articleLink = $("#va_title a");
	articleLink.remove();
	$(".firstHeading").html(articleLink);
}

function incStats(id) {
	$("#" + id).fadeOut(400,
		function() {
			count = parseInt($("#" + id).html());
			$("#" + id).html(count + 1);
			$("#" + id).fadeIn();
		} );
}

function submitForm(accept) {
	if (accept) {
		$("#va_skip").val(0);
	} else {
		$("#va_skip").val(1);
	}
	incStats("iia_stats_today_videos_reviewed");
	incStats("iia_stats_week_videos_reviewed");
	incStats("iia_stats_all_videos_reviewed");
	loadNext();
	return false;
}

function skip() {
	$("#va_skip").val(2);
	loadNext();
	return false;
}

function updateReviewersTable() {
	var url = '/Special:VideoAdder?fetchReviewersTable=true';

	$.get(url, function (data) {
		$('#top_va').html(data);
	});
}

function updateWidgetTimer() {
	WH.updateTimer('stup');
	window.setTimeout(updateWidgetTimer, 60*1000);
}

function addOptions() {
    $('.firstHeading').before('<span class="tool_options_link">(<a href="#">Change Options</a>)</span>');
    $('.firstHeading').after('<div class="tool_options"></div>');

    $('.tool_options_link').click(function(){
        if ($('.tool_options').css('display') == 'none') {
            //show it!
            $('.tool_options').slideDown();
        }
        else {
            //hide it!
            $('.tool_options').slideUp();
        }
		return false;
    });
}

function chooseCat() {
	window.location.href = '/Special:VideoAdder?cat='+ encodeURIComponent($("#va_category").val());
}

function closeDialog() {
	if ($("#dontshow").is(':checked')) {
		$.cookie("wiki_sharedVANoDialog", "yes", {expires: 31});
	}
	//loadNext();
	$("#dialog-box").dialog("close");
}

$(document).ready(function() {
	initToolTitle();
	addOptions();
	loadNext();

	setInterval(updateReviewersTable, updateInterval*60*1000);
	window.setTimeout(updateWidgetTimer, 60*1000);
});

// External methods
window.WH.VideoAdder = {
	skip : skip,
	chooseCat : chooseCat,
	closeDialog : closeDialog
};


}(jQuery, mediaWiki) );
