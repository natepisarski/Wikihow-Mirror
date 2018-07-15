/* most functions taken and tweaked from qc.js */
var qc_vote = 0; 
var qc_skip = 0;
var qc_id   = 0;
var pqt_id  = -1;
var MAX_NUM_ANON_CHECKED = 50;
var throttler;

$("document").ready(function() {
	getNewTip();
	readyButtons();
	WH.ArticleDisplayWidget.init();
	
	throttler = new WH.AnonThrottle({
		toolName: 'tips_checker_anon_edits',
		maxEdits: MAX_NUM_ANON_CHECKED
	});
});

function getNewTip() {
	loadingStuff(true);
	$.get('/Special:QG',
		{ fetchInnards: true,
		  qc_type: 'NewTip',
		  by_username: ''
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

function loadResult(result) {
	//wipe 'em
	qc_vote = 0; 
	qc_skip = 0;
	qc_id   = 0;
	pqt_id  = -1;
	
	//are we done for now?
	if (result['done']) {
		$('#tg_toolbar').hide();
		$('#tg_result').html(result['msg']);
		loadingStuff(false);
		return;
	}
	
	//anon user throttled?
	if (throttler.limitReached()) {
		showLimitReached();
		return;
	}
	
	tip = result['html'] ? result['html']['tip_html'] : '';
	
	$('#tg_article_title').html(mw.message('howto', result['title_unformatted']).text());
	$('#tg_tip').html(tip);
	WH.ArticleDisplayWidget.updateArticleId(result['html']['article_id']);
	qc_id = result['qc_id'];
	pqt_id = result['pqt_id'];
	loadingStuff(false);
	
	//increase throttle count
	throttler.recordEdit(1);
	//trigger all that article stuff
	mw.mobileFrontend.emit('page-loaded');

	if (result['html']) setLogging(qc_id,result['html']);
}

function readyButtons() {
	$('#tg_yes').click(function() {
		qcVote(true);
		return false;
	});
	$('#tg_unsure').click(function() {
		qcSkip();
		return false;
	});
	$('#tg_no').click(function() {
		qcVote(false);
		return false;
	});
}

function submitResponse() {
	loadingStuff(true);

	$.post('/Special:QG',
		{ 
		  postResults: true,
		  qc_vote: qc_vote,
		  qc_skip: qc_skip,
		  qc_type: 'NewTip',
		  by_username: '',
		  event_type: $('body').data('event_type'),
		  qc_id: qc_id,
		  pqt_id: pqt_id
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

function qcVote(vote) {
	//if this is a planted question, even if logged out, we don't want to throw away the answer
	if (throttler.isAnon() && qc_id != -1) {
		qcSkip();
	}
	else {
		(vote) ? (qc_vote = 1) :(qc_vote = 0);
		qc_skip = 0;  
		submitResponse();
	}
}

function qcSkip() {
	qc_skip = 1; 
	submitResponse();
}

function loadingStuff(bDisable) {
	if (bDisable) {
		WH.ArticleDisplayWidget.onBeginArticleChange();
		$('#buttonBlocker').css('z-index','1');
		$('#qc_box li').html('Loading a new tip...');
		$('#tg_result').html('');
		$('#tg_waiting').show();
	}
	else {
		$('#tg_waiting').hide();
		$('#buttonBlocker').css('z-index','-1');
	}
}

function showLimitReached() {
	$('#tg_header').hide();
	$('#tg_tip').hide();
	$('#tg_result').html('');
	$('#tg_toolbar').hide();
	$('#tg_waiting').hide();

	var isMobileDomain = window.location.hostname.match(/\bm\./) != null;
	if (isMobileDomain) {
		var href = $('#tg_limit_reached').find('a').attr('href');
		href = href + "&useformat=mobile&returntoquery=useformat%3Dmobile";
		$('#tg_limit_reached').find('a').attr('href', href);
	}

	$('#tg_limit_reached').show();

}

//add for private logging
function setLogging(qc_id,data) {
	$('body').data({
		event_type: 'tips_guardian',
		assoc_id: (qc_id == -1) ? $("#pqt_id").val() : qc_id,
		article_id: data['article_id'],
		label: (qc_id==-1) ? 'plant' : ''
	});
	//just the tip
	$('#tg_yes, #tg_no, #tg_unsure').data('new_tip',data['tip']);
}
