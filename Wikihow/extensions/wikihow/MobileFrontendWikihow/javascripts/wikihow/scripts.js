( function($, mw) {
'use strict';

//[sc] 8/2019 - this is causing issues, but maybe it's there for a reason? Just commenting out
// if (mw.config.get('wgNamespaceNumber') === 0) {
// 	var isIOS = navigator.userAgent.match(/(iPod|iPhone|iPad)/gi) !== null;
// 	if (isIOS) {
// 		iOSheaderFixes();
// 	}
// }

$(document).ready(function() {
	if (mw.config.get('wgNamespaceNumber') === 0) {
		initializeArticlePage();
	}
});

function initializeArticlePage() {

	// show a bunch of sections that require javascript to work properly
	// it is hidden by default since it requires javascript to work
	$('#uci_section').show();
	$('.trvote_box').show();
	$('.section.video').show();
	$('#hp_navigation').show();

	// Hide pencil button if we're on a page that doesn't exist
	if (mw.config.get('wgArticleId') === 0) {
		$('#ca-edit').hide();
	}

	$(document).one("click", "#summary_wrapper .collapse_link", function(e){
		e.preventDefault();
		$("#summary_text").show();
		$(this).addClass("open");
	});
	$(document).one("click", "#other_languages .collapse_link", function(e){
		e.preventDefault();
		$("#language_links").show();
		$(this).addClass("open");
	});

	$('.checkmark').on('click', function() {
		$(this).toggleClass('checked');
		$(this).closest('li').toggleClass('all_done');
		return false;
	});
}

function moveSamplesSection() {
	//only for small
	if ($(window).width() >= WH.mediumScreenMinWidth) return;

	$('.sample').each( function() {
		$('.steps:last').after(this);
	});
}

// Special:UserLogin
$(document).on('click', '#wpLoginAttempt', function() {
	// Scroll to top to fix fixed header hanging out in the middle of the screen in iOS devices
	// when submitting the login form. Cause by keyboard resizing viewport, then closing and creating
	// a scroll event.
	window.scrollTo(0, 0);
});

// Search
$(document).ready(function() {
	// Clear out all the search boxes on page load
	// Do this in case someone hits the back button
	// which would keep their previous search term in place
	$('input.cse_q, #hp_search').val('');

	//custom search placeholder based on screen size
	var search_ph = mw.message('header-search-placeholder').text();
	if ($(window).width() >= WH.largeScreenMinWidth) search_ph = mw.message('wh_search_line_ph').text();

	$('#hs_query').attr('placeholder', search_ph);
});

$(document).ready(function() {
	var $addUCI = $('#icon-adduci a');
	if ($addUCI.length > 0) {
		$addUCI.click(function(e) {
			e.preventDefault();
			$.scrollTo('#uci_section', 2000, {offset:-105});
		});
	}

	var marginAnimShow = {'margin-left':'50px'};
	var marginAnimHide = {'margin-left':'88%'};
	if (mw.config.get('wgContentLanguage') == 'ar') {
		marginAnimShow = {'margin-right':'50px'};
		marginAnimHide = {'margin-right':'88%'};
	}

	//Snippet to prevent blank queries
	$('.cse_sa').click(function(/*e*/) {
		var $input = $(this).siblings('input[name=search]');
		if ($input.val().length === 0) {
			var placeholderText = $input.attr('placeholder');
			if (placeholderText) {
				$input.val(placeholderText);
			} else {
				// stop submission of the form if we're going to submit nothing
				return false;
			}
		}
	});

	var hs_touched = false;
	$( '#hs_query' ).click( function () {
		if ( !$( '#hs' ).hasClass( 'hs_active' ) || !hs_touched ) {
			hs_touched = true;
			$( '#hs' ).addClass( 'hs_active' );
			var input = this;
			setTimeout( function () {
				if ( 'setSelectionRange' in input ) {
					input.setSelectionRange( 0, 9999 );
				} else if ( 'selectionStart' in input ) {
					input.selectionStart = 0;
					input.selectionEnd = input.value.length;
				}
			}, 10 );
		}
	} );
	$( '#hs_close' ).click( function () {
		$( '#hs' ).removeClass( 'hs_active' );
	});

	//add our click handers
	addClickHandlers();

	if (mw.config.get('wgNamespaceNumber') === 0 || mw.config.get('wgNamespaceNumber') == -1) {
		// George 04/04/16: Disabled for now, as we're using deferred loading for these
		//load tablet sized images for related wikihows section
		//swapRelatedImages('#relatedwikihows a');

		//load tablet sized images for hp fa section
		//swapRelatedImages('#fa_container a');

		resizeVideo();
	}

	moveSamplesSection();
});

/**
 * Adds rel="nofollow" to all the links in a given HTML string and
 * Adds target="_blank" to all the links in a given HTML string
 */
function addNoFollowAndBlank(stringHtml) {
	var $html = $(stringHtml);
	$html.find('a').attr('rel', 'nofollow');
	$html.find('a').attr('target', '_blank');
	return $('<div />').append($html).html();
}

function resizeVideo() {
	// Don't make changes for iPad
	if ( navigator.userAgent.match(/iPad/i) ) {
		return;
	}

	// other tablets...
	if ($(window).width() > 700) {
		var new_width = 600;
		var old_width = $('#video object').attr('width');
		var old_height = $('#video object').attr('height');
		var new_height = Math.round((new_width * old_height) / old_width);

		$('#video object, #video embed').attr('width', new_width);
		$('#video object, #video embed').attr('height', new_height);
	}
}

//[sc] 8/2019 - this is causing issues, but might be needed for something? just commenting out
// // fix for when the keyboard pops up
// function iOSheaderFixes() {
// 	$('.cse_q').first()
// 		.focus(function() {
// 			//search box is being used
// 			//WARNING: the iOS keyboard approacheth!!!
// 			$('.header').css('position','absolute');
// 			$(window).scrollTop(0);
// 		})
// 		.blur(function() {
// 			$('.header').css('position','fixed');
// 		});
// }

function addClickHandlers() {
		// Track pencil click events
		$(document).on('click', 'a.edit-page', function(/*e*/) {
			//e.preventDefault();
			WH.ga.sendEvent('m-edit', 'pencil', mw.config.get('wgTitle'));
		});

		// Track Quiz App clicks
		$(document).on('click', '#icon-quizyourself', function() {
			WH.maEvent('mobile_menu_quiz_app_click');
		});

		// Track search clicks
		var openCount = 0;
		var closeCount = 0;
		var hsCloseClicks = 0;
		var open = $( '.hs_active' ).length > 0;
		var pageType = 'other';
		if (mw.config.get('wgIsArticle')) {
			pageType = 'article';
		} else if ( wgTitle === 'LSearch' ) {
			pageType = 'search';
		} else if ( wgTitle === 'Main Page' ) {
			pageType = 'main';
		}

		$('#hs_query').on( 'click', function ( e ) {
			if ( !open ) {
				openCount++;
				open = true;
				WH.maEvent( 'mobile_search_open', { count: openCount, pageType: pageType } );
			}
		} );
		$('#hs_close').on( 'click', function ( e ) {
			closeCount++;
			open = false;
			WH.maEvent( 'mobile_search_close', { count: closeCount, pageType: pageType } );
		} );
		var hsFormSubmitLogged = false;
		$('#hs form').on( 'submit', function ( e ) {
			if ( !hsFormSubmitLogged ) {
				WH.maEvent( 'mobile_search_submit', { pageType: pageType }, function () {
					hsFormSubmitLogged = true;
					$('#hs form').submit();
				} );
				e.preventDefault();
				return false;
			}
		} );

}

$(document).ready(function() {
	$('#ar_form_details').on('change', '.ar_public', function() {
		if ($(this).val() == 'yes') {
			$('#ar_public_info').show();
		} else {
			$('#ar_public_info').hide();
		}
	});
});

//here's where we handle weird errors that arise from people manually resizing their client
$( window ).resize(function() {

	//@large
	if ($(window).width() >= WH.largeScreenMinWidth) {

		//close menu (lifted from MainMenu.js)
		if ($( document.body ).hasClass( 'navigation-enabled' )) {
			$( document.body ).removeClass( 'navigation-enabled' )
				.removeClass( 'secondary-navigation-enabled' )
				.removeClass( 'primary-navigation-enabled' );
			setTimeout(function(){ $('#mw-mf-viewport').removeClass("menuopen"); }, 1250);
		}

	}

});

}(jQuery, mediaWiki) );
