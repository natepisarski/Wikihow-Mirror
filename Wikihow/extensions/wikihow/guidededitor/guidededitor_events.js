( function($, mw) {
'use strict';

function confirmExit() {
	if (window.needToConfirm) {
		return mw.message('all-changes-lost').text();
	}
}

// Declare the $.center() method
$.fn.extend({
	center: function (options) {
		var options =  $.extend({ // Default values
			inside:window, // element, center into window
			transition: 0, // millisecond, transition time
			minX:0, // pixel, minimum left element value
			minY:0, // pixel, minimum top element value
			withScrolling:true, // booleen, take care of the scrollbar (scrollTop)
			vertical:true, // booleen, center vertical
			horizontal:true // booleen, center horizontal
		}, options);
		return this.each(function() {
			var props = {position:'absolute'};
			if (options.vertical) {
				var top = ($(options.inside).height() - $(this).outerHeight()) / 2;
				if (options.withScrolling) top += $(options.inside).scrollTop() || 0;
				top = (top > options.minY ? top : options.minY);
				$.extend(props, {top: top+'px'});
			}
			if (options.horizontal) {
				var left = ($(options.inside).width() - $(this).outerWidth()) / 2;
				if (options.withScrolling) left += $(options.inside).scrollLeft() || 0;
				left = (left > options.minX ? left : options.minX);
				$.extend(props, {left: left+'px'});
			}
			if (options.transition > 0) $(this).animate(props, options.transition);
			else $(this).css(props);
			return $(this);
		});
	}
});

$(document).ready( function() {
	if ( location.href.match(/action=(edit|submit2)/) && !location.href.match(/advanced=true/) ) {
		if (typeof WH.AC != 'undefined' &&
			typeof WH.AC.InstallAC == 'function' &&
			$('#steps').length &&
			$('#wpTextbox1').length === 0
		) {
			WH.AC.InstallAC(document.editform, document.editform.q, document.editform.btnG, '/Special:TitleSearch', 'en');
		}
	}

	window.isGuided = true;
	window.needToConfirm = false;
	window.checkMinLength = true;

	$('.button').click(function () {
		var button = $(this).not('.submit_button');
		if (button.length) {
			window.needToConfirm = true;
		}
	});

	$('textarea').focus(function () {
		window.needToConfirm = true;
	});

	$('#ep_cat').live('click', function(e) {
		e.preventDefault();
		var title = 'Categorize ' + mw.config.get('wgTitle');
		if (title.length > 54) {
			title = title.substr(0, 54) + '...';
		}
		$('#dialog-box').html('');

		$('#dialog-box').load('/Special:Categorizer?a=editpage&id=' + wgArticleId, function() {
			$('#dialog-box').dialog({
				width: 673,
				height: 600,
				modal: true,
				title: title,
				closeText: 'x',
				dialogClass: 'modal2',
			});
			var reCenter = function() {
				$('#dialog-box').dialog('option', 'position', 'center');
			};
			setTimeout(reCenter, 100);
		});
	});

	if (mw.user.options.get('useeditwarning') !== '0') {
		window.onbeforeunload = confirmExit;
	}
	
	$('#change_cats_button').click( function() {
		WH.Editor.removeCategories();
		return false;
	} );

	$('.change_add_video_button').click( function() {
		var title = mw.config.get('wgTitle');
		var isNewArticle = $('#is_new_article').val() == 'true';
		WH.Editor.changeVideo(title, isNewArticle);
		$('#winpop_outer').center();
		return false;
	} );

	$('#show_preview_button').click( function() {
		WH.PreviewVideo.showHideVideoPreview();
		return false;
	} );

	$('#hide_preview_button').click( function() {
		WH.PreviewVideo.showHideVideoPreview();
		return false;
	} );

	$('#editform').submit( function() {
		return WH.Editor.checkForm();
	} );

	$('#ingredients_text').keyup( function() {
		WH.Editor.addStars(event, document.editform.ingredients);
	} );

	$('#steps_text').keyup( function() {
		WH.Editor.addNumToSteps(event);
	} );

	$('#tips_text').keyup( function() {
		WH.Editor.addStars(event, document.editform.tips);
	} );

	$('#warnings_text').keyup( function() {
		WH.Editor.addStars(event, document.editform.warnings);
	} );

	$('#thingsyoullneed_text').keyup( function() {
		WH.Editor.addStars(event, document.editform.thingsyoullneed);
	} );

	$('#references_text').keyup( function() {
		WH.Editor.addStars(event, document.editform.references);
	} );

	$('#relateds_move_up').click( function() {
		WH.Editor.moveRelated(true);
		return false;
	} );

	$('#relateds_move_down').click( function() {
		WH.Editor.moveRelated(false);
		return false;
	} );

	$('#related_select').dblclick( function() {
		WH.Editor.viewRelated();
	} );

	$('#relateds_remove').click( function() {
		WH.Editor.removeRelated();
		return false;
	} );

	$('#relateds_title_search').keypress( function() {
		return WH.Editor.keyxxx(event);
	} );

	$('#add_button').click( function() {
		WH.Editor.addRelated();
		return false;
	} );

	$('#optional_sections * input[type=checkbox]').click( function() {
		var checkboxName = $(this).attr('id');
		var sectionName = checkboxName.replace(/_checkbox$/, '');
		WH.Editor.showHideRow(sectionName, checkboxName);
	} );

	$('#wpDiff, #wpPreview').click( function() {
		window.needToConfirm = false;
		window.checkMinLength = false;
		WH.Editor.checkSummary();
	} );

	$('input[name=wpSave]').click( function() {
		window.needToConfirm = false;
	} );

	$('#wpCancel').click( function() {
		history.back();
	} );

	if ( location.href.match(/action=edit/) && location.href.match(/overwrite=yes/) ) {
		gatTrack('browsing', 'create_overwrite', 'hatchery');
	}
} );

}(jQuery, mediaWiki) );
