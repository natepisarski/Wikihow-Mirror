( function($, mw) {
'use strict';

if (mw.config.get('wgNamespaceNumber') === 0) {
	var isIOS = navigator.userAgent.match(/(iPod|iPhone|iPad)/gi) !== null;
	if (isIOS) {
		iOSheaderFixes();
	}
}

$(document).ready(function() {
	if (mw.config.get('wgNamespaceNumber') === 0) {
		initializeArticlePage();
	}
});

mw.mobileFrontend.on( 'page-loaded', function() {
	initializeArticlePage();

	// Only do this in page-loaded to prevent intro removal jitter on normal
	// article pages
	hideBlankIntro();
} );

function hideBlankIntro() {
	if (!$('#intro').text().trim().length) {
		$('#intro').hide();
	}
}

function initializeArticlePage() {

	// show a bunch of sections that require javascript to work properly
	// it is hidden by default since it requires javascript to work
	$('#uci_section').show();
	$('.trvote_box').show();
	$('#articleinfo').show();
	$('.section.video').show();
	$('#hp_navigation').show();

	// Hide pencil button if we're on a page that doesn't exist
	if (mw.config.get('wgArticleId') === 0) {
		$('#ca-edit').hide();
	}

	$('#info_link').on('click', function(e){
		e.preventDefault();

		$.ajax({
			url: '/api.php?action=app&subcmd=credits&id=' + $(this).attr('aid') + '&format=json',
			async: false,
			success: processArticleInfo
		});
	});

	$('.checkmark').on('click', function() {
		if ($(this).hasClass('checked')) {
			$(this).removeClass('checked');
		}
		else {
			$(this).addClass('checked');
		}
		return false;
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
});

$(document).ready(function() {
	//hide the add tip menu item if there's no tip section
	if ($('.addTipElement').length <= 0) {
		var $addTipIcon = $('#icon-addtip');
		$addTipIcon.hide();
		//is this the last one? uh oh. Hide the whole menu section
		if ($addTipIcon.next().hasClass('side_header')) {
			$('#header3').hide();
		}
	}

	var $addTip = $('#icon-addtip a');
	if ($addTip.length > 0) {
		$addTip.click(function(e) {
			e.preventDefault();
			$.scrollTo('.addTipElement', 2000, {offset:-85});
		});
	}

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

	$('.header .search').click(function() {
		// Hide the visibility of the search button as it wraps if visibile
		// when the search bar animates to its open state
		$('.wh_search .cse_sa').css('visibility', 'hidden');
		$('#search_oversearch').fadeIn(100, function() {
			$('#cse-search-box').animate(marginAnimShow, 500, function() {
				$('.wh_search .cse_sa').css( {
					'background-size': '18px',
					'top': 'auto',
					'right': 'auto',
					'visibility': 'visible'
				} );
			});
			var $cseClose = $('.cse_x');
			$cseClose.fadeIn();
			$cseClose.click(function() {
				$('.wh_search .cse_sa').css('visibility', 'hidden');
				$cseClose.fadeOut();
				$('#cse-search-box').animate(marginAnimHide,500,function() {
					$('#search_oversearch').hide();

				});
			});
		});
		//focus has to be down here for this to work with iOS devices
		// Don't focus for search results since we don't want to automatically
		// pull up the keyboard
		if (mw.config.get('wgTitle') != 'LSearch') {
			$('#search_oversearch').find('.cse_q').focus();
		}

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

function processArticleInfo(data) {
	var sources = null;
	var images = null;
	var info = '';

	var hasSources = data.app.article_sources && data.app.article_sources.numbered.length;
	var hasExtraSources = $('#extra_sources a').length;
	if (hasSources || hasExtraSources) {
		sources = '<div class="section sourcesandcitations"><h2><span class="mw-headline">' +
			mw.message('sources') + '</span></h2><div class="section_text">';
		if (hasSources) {
			sources += '<ol class="references">';
			for (var i = 0; i < data.app.article_sources.numbered.length; i++) {
				sources += '<a class="reference-anchor" id="_refanchor-' + (i+1) + '"></a>';
				sources += '<li id="_note-' + (i+1) + '">' + addNoFollowAndBlank(data.app.article_sources.numbered[i].html) + '</li>';
			}
			sources += '</ol>';
		}
		if (hasExtraSources) {
			var style = hasSources ? ' style="margin-top: 15px;"' : '';
			var extra = $('<ul' + style + '></ul>');
			$('#extra_sources a').each(function(/*i*/) {
				extra.append($('<li></li>').append(this));
			});
			sources += $(extra)[0].outerHTML;
		}
		sources += '</div></div>';
	}

	if (data.app.image_sources.uploaders.length > 0 || data.app.image_sources.licenses.length > 0) {
		images = '<div class="section imageattribution"><h2><span class="mw-headline">' + mw.message('image-attribution').text() + '</span></h2><div class="section_text"><ul>';
		for (var i = 0; i < data.app.image_sources.uploaders.length; i++) {
			images += '<li><span class="info_label">Uploaded by:</span>' + data.app.image_sources.uploaders[i] + '</li>';
		}
		if (data.app.image_sources.licenses.length > 0) {
			for (var i = 0; i < data.app.image_sources.licenses.length; i++) {
				images += '<li><span class="info_label">Licenses:</span>' + data.app.image_sources.licenses[i] + '</li>';
			}
		}
		images += '</ul></div></div>';
	}

	// George 2015-06-18: Disabled for international due to EU privacy
	// protection laws affecting Google results.
	if (mw.config.get('wgContentLanguage') == 'en') {
		info = '<ul><li><span class="info_label">' + mw.message('thanks_to_all_authors').text() + '</span><br />';
		for (var i = 0; i < data.app.authors.length; i++) {
			if (i > 0) {
				info += ', ';
			}
			info += data.app.authors[i];
		}
		info += '</li>';
	}

	// [sc] 12/16 - moved this to the About This WikiHow section
	// if (data.app.categories.length > 0) {
	// 	info += '<li><span class="info_label">' + mw.message('categories').text() + ':</span><br />';
	// 	for(i = 0; i < data.app.categories.length; i++) {
	// 		if (i > 0)
	// 			info += ', ';
	// 		info +=  data.app.categories[i];
	// 	}
	// 	info += '</li></ul>';
	// }

	if (info.length) {
		$('#articleinfo').html(info);
	} else {
		$('#articleinfo').remove();
	}

	var sections = '';
	if (sources) {
		sections += sources;
	}
	if (images) {
		sections += images;
	}
	$('.articleinfo').before(sections);
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

// fix for when the keyboard pops up
function iOSheaderFixes() {
	$('.cse_q').first()
		.focus(function() {
			//search box is being used
			//WARNING: the iOS keyboard approacheth!!!
			$('.header').css('position','absolute');
			$('#search_oversearch').css('position','absolute');
			$(window).scrollTop(0);

			//complete hack fix for Chrome on iPhone
			var isIPhone = navigator.userAgent.match(/(iPod|iPhone)/gi) !== null;
			var isChrome = navigator.userAgent.match(/(CriOS)/gi) !== null;
			if (isIPhone && isChrome) {
				$('#search_oversearch').css('top','40px');
			}
		})
		.blur(function() {
			$('#search_oversearch').css('position','fixed');
			$('.header').css('position','fixed');
		});
}

function addClickHandlers() {
		//footer creature click
		$(document).on('click', '#footer_random_button a', function() {
			WH.ga.sendEvent('m-footer', 'surprise-creature', mw.config.get('wgTitle'));
		});

		// Track pencil click events
		$(document).on('click', 'a.edit-page', function(/*e*/) {
			//e.preventDefault();
			WH.ga.sendEvent('m-edit', 'pencil', mw.config.get('wgTitle'));
		});
}

$(document).ready(function() {
	$('.unnabbed_alert #nab_alert_close').on('click', function(e) {
		e.preventDefault();
		$('.unnabbed_alert').hide();
		$('.unnabbed').removeClass('unnabbed');
		$('.unnabbed_alert_top').show();
	});
	$('#ar_form_details').on('change', '.ar_public', function() {
		if ($(this).val() == 'yes') {
			$('#ar_public_info').show();
		} else {
			$('#ar_public_info').hide();
		}
	});
});

}(jQuery, mediaWiki) );
