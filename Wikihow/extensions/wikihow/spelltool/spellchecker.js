( function($, mw) {

var toolURL = "/Special:Spellchecker";
var articleId = 0;
var wordArray;
var wordArrayIdx = 0;
var currentWord = "";
var currentWordIdx = 0;
var exclusionArray;
var quickEditUrl;
var misspell = "misspell";
var SC_STANDINGS_TABLE_REFRESH = 600;
var retries = 0;
var numChecked = 0;
var MAX_RETRIES = 5;
var MAX_NUM_ANON_CHECKED = 50;
var throttler = null;
var plantId = -1;
var signupinlink = '/Special:UserLogin?returnto=Special:Spellchecker';
var trackingCategory = WH.isMobileDomain ? 'm-spellchecker' : 'd-spellchecker';

// Client-side fix for words separated by <br> tags
$.expr[":"].containsIgnoreBreaks = $.expr.createPseudo(function(arg) {
	return function( elem ) {
		fixedHtml = $(elem).html().replace(/(<br>)+/g, " ");
		fixedHtml = fixQuote(fixedHtml);
		return fixedHtml.indexOf(arg) >= 0;
	};
});

$(document).ready(function() {
	// Test for old IEs
	validIE = true;
	isIE = false;
	var clientPC = navigator.userAgent.toLowerCase(); // Get client info
	if (/msie (\d+\.\d+);/.test(clientPC)) { //test for MSIE x.x;
		 validIE  = 8 <= (new Number(RegExp.$1)); // capture x.x portion and store as a number
		 isIE = true;
	}

	if (!validIE) {
		$("#spch-snippet").html('Error: You have an outdated browser. Please upgrade to the latest version.');
		disableTopButtons();

		if (WH.isMobileDomain) {
			$('.mt_button_bar').hide();
			WH.ArticleDisplayWidget.gone();
		}

		$('.spch-waiting').hide();
		return;
	}

	throttler = new WH.AnonThrottle({
		toolName: 'spellchecker',
		maxEdits: MAX_NUM_ANON_CHECKED
	})

	// Change auto summary
	gAutoSummaryText = mw.message('spch-qe-summary').text();

	if (mw.config.get('wgUserId') > 0)  {
		$('#spch-qe').css('visibility', 'visible');
	}

	$("#bodycontents .article_inner").removeClass("article_inner");
	articleName = mw.util.getParamValue('a');

	if (WH.isMobileDomain) {
		WH.ArticleDisplayWidget.init({showSpinner: false});
	}

	getNextSpellchecker(articleName);

	$(document).on('click', '#spch-skip', function (e) {
		e.preventDefault();

		if (!jQuery(this).hasClass('clickfail')) {
			var label = mw.user.isAnon() ? 'anon-notsure' : 'notsure';
			WH.ga.sendEvent(trackingCategory, label);

			var sentence = loadNextWord();
			if (sentence.length == 0) {
				$("#spch-preview").hide();
				$(".spch-waiting").show();
				submitArticle();
			}
		}
	});

	$(document).on('click', '#spch-no', function (e) {
		e.preventDefault();

		if (!jQuery(this).hasClass('clickfail')) {
			var label = mw.user.isAnon() ? 'anon-no' : 'no';
			WH.ga.sendEvent(trackingCategory, label);

			wordArray[currentWordIdx]['correction'] = currentWord;

			var sentence = loadNextWord();
			if (sentence.length == 0) {
				$("#spch-preview").hide();
				$(".spch-waiting").show();
				submitArticle();
			}
		}
	});

	function enterEditMode() {
		toggleTopButtons();
		toggleEditButtons();
		$('.misspell').addClass('editable').prop('contenteditable', 'true');
		placeCaretAtEnd($('.misspell').get(0));
	}

	$(document).on('click', '#spch-yes', function(e) {
		e.preventDefault();
		$('.tip_x').click();
		if (!jQuery(this).hasClass('clickfail')) {
			var label = mw.user.isAnon() ? 'anon-yes' : 'yes';
			WH.ga.sendEvent(trackingCategory, label);
			enterEditMode();
		}
	});

	$(document).on('dblclick', '#spch-snippet', function(e) {
		e.preventDefault();
		$('.tip_x').click();
		if (!$('#spch-yes').hasClass('clickfail')) {
			enterEditMode();
		}
	});

	$(document).on('click', '#spch-cancel', function(e) {
		e.preventDefault();
		$('.tip_x').click();
		if (!jQuery(this).hasClass('clickfail')) {
			$('.misspell').prop('contenteditable', 'false').removeClass('editable').text(currentWord);
			toggleEditButtons();
			toggleTopButtons();
		}
	});

	$(document).on('click', '#spch-skip-article', function(e) {
		e.preventDefault();
		if (!jQuery(this).hasClass('clickfail')) {
			skipArticle();
		}
	});

	$(document).on('click', '#spch-next', function(e) {
		e.preventDefault();
		if (!jQuery(this).hasClass('clickfail')) {
			if ($('.misspell').text() != currentWord) {
				wordArray[currentWordIdx]['correction'] = $('.misspell').text();
			}
			toggleEditButtons();
			toggleTopButtons();

			var sentence = loadNextWord();
			if (sentence.length == 0) {
				$("#spch-preview").hide();
				$(".spch-waiting").show();
				submitArticle();
			}
		}
	});

	$(document).on('click', '#spch-qe', function (e) {
		e.preventDefault();
		if (!$(this).hasClass('clickfail')) {
			initPopupEdit(quickEditUrl);
		}
	});
});

function showAnonLimitReachedMsg() {
	$('.tip_bubble').hide();
	var href = signupinlink + '&type=signup';
	if (WH.isMobileDomain) {
		href = href + "&useformat=mobile&returntoquery=useformat%3Dmobile";
	}
	var signupLink = mw.html.element(
		'a',
		{
			href: href,
			title: mw.msg('spch-signup'),
			class: "button primary"
		},
		mw.msg('spch-signup')
	);
	$('#spch-prompt, .spch-waiting').hide();
	$('#spch-snippet').addClass('anon_limited').html(mw.msg('spch-msg-anon-limit1', signupLink));
	if (WH.isMobileDomain) {
		$('.mt_button_bar').hide();
		WH.ArticleDisplayWidget.gone();
	}

	hideButtons();

}

function setAnonLimitCookie() {
	$.cookie(anon_cookie, true, { expires: anon_cookie_exp, domain: '.' + mw.config.get('wgCookieDomain') });
}

function getNextSpellchecker(articleName) {

	if (throttler.limitReached()) {
		showAnonLimitReachedMsg();
		return;
	}

	var aid = mw.util.getParamValue('aid') || 0;
	$.get(toolURL,
		{nextArticle: true,
			a: articleName,
			aid: aid
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

function hidePrompt() {
	$('#spch-prompt').hide();
}
/**
 * Loads the next article into the page
 *
 **/
function loadResult(result) {
	if (throttler.limitReached()) {
		showAnonLimitReachedMsg();
		return;
	}

	$('body').data({
		event_type: 'spellchecker',
		article_id: result.articleId,
		label: (typeof result.plantId != 'undefined') ? 'plant' : '',
		assoc_id: (typeof result.plantId != 'undefined') ? result.plantId : '',
	});

	debugResult(result);
	if (result['error'] != undefined) {
		if (WH.isMobileDomain) {
			$('#spch-snippet').addClass('spch-eoq').html(result['error']);
			hideButtons();
			hidePrompt();
			WH.ArticleDisplayWidget.gone();
		} else {
			$('#spch-head').hide().delay(200).html(result['error']).fadeIn();
		}

		$('.spch-waiting').hide();
		disableTopButtons();
	}
	else {
		quickEditUrl = result['qeurl'];
		wordArray = result['words'];
		wordArrayIdx = 0;
		exclusionArray = result['exclusions'];
		articleId = result['articleId'];
		plantId = result['plantId'];

		if (WH.isMobileDomain) {
			WH.ArticleDisplayWidget.updateArticleId(articleId, result['html']);
		} else {
			$("#spch-preview").html(result['html']);
		}

		// If we can't find a sentence, just skip the article
		var sentence = loadNextWord();
		if (sentence.length == 0) {

		if (retries++ < MAX_RETRIES ) {
			$(".spch-waiting").show();
			// Set the error flag to 1 designating that the client couldn't find
			// any words in the word map within the html
			skipArticle(1);
			return;
		} else {
			var noArticlesErrorMessage = 'spch-error-noarticles';

			if (WH.isMobileDomain) {
				noArticlesErrorMessage += '-mobile';
			}

			$('#spch-snippet').html(mw.message(noArticlesErrorMessage).text());
			disableTopButtons();
			if (WH.isMobileDomain) {
				hideButtons();
				hidePrompt();
				$(".spch-waiting").hide();
				WH.ArticleDisplayWidget.gone();
				return;
			}
		}

		}
		if ( $(".firstHeading").length && result && result['title']) {
			$(".firstHeading").html(result['title']);
		}
		$('.mt_prompt_article_title').html(result['text_title']);

		$('.spch-waiting').hide();
		$("#spch-preview").show();
		enableTopButtons();

		retries = 0;
		throttler.recordEdit();
	}
}

function skipArticle(error) {
	disableTopButtons();
	$("#spch-preview").hide();
	submitArticle(error);
}

function submitArticle(error) {
	$(".spch-waiting").show();
	$("#spch-snippet").text(mw.message('spch-loading-next').text());
	disableTopButtons();

	if (WH.isMobileDomain) {
		WH.ArticleDisplayWidget.onBeginArticleChange();
	}
	$.post(toolURL,
		{submit: 1, articleId: articleId, words: wordArray, plantId: plantId, error: error},
		function(result) {
			updateStats(result.increment);
			loadResult(result);
		},
		'json'
	);
}

function loadNextWord() {
	var sentence = "";
	var word = "";
	var misspelledWord = "";
	var key = "";
	if (wordArrayIdx < wordArray.length) {

		do {
			wordArray[wordArrayIdx]['misspelled'] = fixQuote(wordArray[wordArrayIdx]['misspelled']);
			wordArray[wordArrayIdx]['key'] = fixQuote(wordArray[wordArrayIdx]['key']);
			word = wordArray[wordArrayIdx];
			misspelledWord = word['misspelled'];
			key = word['key'];
			//console.log(key);
			sentence = findSentenceContaining(key);
			wordArrayIdx++;
		} while (wordArrayIdx < wordArray.length && sentence.length == 0);

		currentWord = misspelledWord;
		currentWordIdx = wordArrayIdx - 1;
		sentence = wrapMisspelledWord(sentence, key, misspelledWord);

		$('#spch-snippet').html(sentence);
	}
	return sentence;
}

function debugResult(result) {

	// adds debugging log data to the debug console if exists
	if (typeof WH !== 'undefined' && typeof WH.consoleDebug !== 'undefined') {
		WH.consoleDebug(result['debug']);
	}
}

// @param arg often contains ' (\u0027) whereas content
// has ’ (\u2019), so fix by string replacing with what we'll
// call the canonical ' (\u0027)
function fixQuote(arg) {
	fixedQuote = arg.replace("\u2019", "'");
	return fixedQuote;
}

function findSentenceContaining(key) {
	//console.log('key: ' + key);
	var sentence = '';
	// Selectors to check. Prioritize selectors that would result in elements deepest in the DOM tree to make sure they aren't
	// caught by less precise selectors.
	var selectors = [
		'#intro p:containsIgnoreBreaks(' + key + ')',
		'.section:not(.references):not(.sourcesandcitations):not(.relatedwikihows):not(.video) div.section_text ul ul ul li:containsIgnoreBreaks(' + key + ')',
		'.section:not(.references):not(.sourcesandcitations):not(.relatedwikihows):not(.video) div.section_text ul ul li:containsIgnoreBreaks(' + key + ')',
		'.section:not(.references):not(.sourcesandcitations):not(.relatedwikihows):not(.video) div.section_text ul li:containsIgnoreBreaks(' + key + ')',
		'.section:not(.references):not(.sourcesandcitations):not(.relatedwikihows):not(.video) div.section_text li:containsIgnoreBreaks(' + key + ')',
		'.section:not(.references):not(.sourcesandcitations):not(.relatedwikihows):not(.video) div.section_text ul p:containsIgnoreBreaks(' + key + ')',
		'.section:not(.references):not(.sourcesandcitations):not(.relatedwikihows):not(.video) div.section_text p:containsIgnoreBreaks(' + key + ')'
	];

	for (var i = 0; i < selectors.length; i++) {
		var elem = $(selectors[i]).clone();
		if ($(elem).length) {
			$(elem).find('b').each(function(i, elem) {
				$(this).replaceWith($(elem).text());
			});
			$(elem).find('.m-video').remove();
			$(elem).find('ul').remove();
			sentence =  fixQuote($(elem).html());
			break;
		}
	}

	//console.log('sentence: ' + sentence);
	return sentence;
}

function wrapMisspelledWord(sentence, key, word) {
	//console.log('word to wrap: ' + word);
	replacementKey = key.replace(word, '<div class="misspell inline">' + word + '</div>');
	return sentence.replace(key, replacementKey);
}

function placeCaretAtEnd(el) {
	el.focus();
	if (typeof window.getSelection != "undefined"
		&& typeof document.createRange != "undefined") {
		var range = document.createRange();
		range.selectNodeContents(el);
		range.collapse(false);
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(range);
	} else if (typeof document.body.createTextRange != "undefined") {
		var textRange = document.body.createTextRange();
		textRange.moveToElementText(el);
		textRange.collapse(false);
		textRange.select();
	}
}
function hideButtons() {
	$('#spch-yes').hide();
	$('#spch-skip').hide();
	$('#spch-no').hide();
	$('#spch-qe').hide();
	$('#spch-skip-article').hide();
}

function disableTopButtons() {
	//disable edit/skip choices
	$('#spch-yes').addClass('clickfail');
	$('#spch-skip').addClass('clickfail');
	$('#spch-no').addClass('clickfail');
	$('#spch-qe').addClass('clickfail');
	$('#spch-skip-article').addClass('clickfail');
}

function enableTopButtons() {
	//disable edit/skip choices
	$('#spch-yes').removeClass('clickfail');
	$('#spch-skip').removeClass('clickfail');
	$('#spch-no').removeClass('clickfail');
	$('#spch-qe').removeClass('clickfail');
	$('#spch-skip-article').removeClass('clickfail');
}

function toggleTopButtons() {
	//disable edit/skip choices
	$('#spch-options, #spch-skip-article').toggle();
}

function toggleEditButtons() {
	//disable edit/skip choices
	$('#spch-edit-buttons').toggle();
}

function updateStats(increment){
	var statboxes = '#iia_stats_today_spellchecked,#iia_stats_week_spellchecked,#iia_stats_all_spellchecked,#iia_stats_group';
	$(statboxes).each(function(index, elem) {
			$(this).fadeOut(function () {
				var cur = parseInt($(this).html());
				if (increment) {
					$(this).html(cur + 1);
				}

				$(this).fadeIn();
			});
		}
	);
}

updateStandingsTable = function() {
    var url = '/Special:Standings/SpellcheckerStandingsGroup';
    jQuery.get(url, function (data) {
        jQuery('#iia_standings_table').html(data['html']);
    },
	'json'
	);
	$("#stup").html(SC_STANDINGS_TABLE_REFRESH / 60);
	//reset timer
	window.setTimeout(updateStandingsTable, 1000 * SC_STANDINGS_TABLE_REFRESH);
}


if ($('#iia_individual_table_spellchecked').length) {
	window.setTimeout(updateWidgetTimer, 60*1000);
}

if ($('#iia_standings_table').length) {
	window.setTimeout(updateStandingsTable, 1000 * SC_STANDINGS_TABLE_REFRESH);
}


function updateWidgetTimer() {
    WH.updateTimer('stup');
    window.setTimeout(updateWidgetTimer, 60*1000);
}

}(jQuery, mediaWiki) );
