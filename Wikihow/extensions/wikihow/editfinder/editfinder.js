/*
 * Edit Finder Class
 */
var editfinder_preview = false;
var g_bEdited = false;

var EF_WIDGET_LEADERBOARD_REFRESH = 10 * 60;


function EditFinder() {
	this.m_title = '';
	this.m_searchterms = '';
}

EditFinder.prototype.init = function () {
	editFinder.initToolTitle();
	editFinder.getArticle();

	$(".firstHeading").after($("#editfinder_cat_header"));

	var mod = Mousetrap.defaultModifierKeys;
	Mousetrap.bind(mod + 'e', function() {$('#editfinder_yes').click();});
	Mousetrap.bind(mod + 's', function() {$('#editfinder_skip').click();});
	Mousetrap.bind(mod + 'p', function() {$('#wpSave').click();});
	Mousetrap.bind(mod + 'v', function() {$('#wpPreview').click();});
	Mousetrap.bind(mod + 'c', function() {document.getElementById('mw-editform-cancel').click();});

	 $("#edit_keys").click(function(e){
        e.preventDefault();
        $("#edit_info").dialog({
            width: 500,
            minHeight: 300,
            modal: true,
            title: 'Greenhouse Keys',
            closeText: 'x',
            position: 'center',
        });
    });

	//bind skip link
	jQuery('#editfinder_skip').click(function(e) {
		e.preventDefault();
		if (!jQuery(this).hasClass('clickfail')) {
			editFinder.disableTopButtons()
			editFinder.getArticle();
		}
	});

	var interests = editFinder.getEditType() == 'topic';

	if (interests) {
		editFinder.showCatChooseLink();
	}
	else {
		editFinder.getUserCats();
	}

	/*category choosing*/
	jQuery('.editfinder_choose_cats').click(function(e){
		e.preventDefault();
		if (interests) {
			editFinder.getThoseInterests();
			$(document).scrollTop(0);
		}
		else {
			editFinder.getThoseCats();
		}
	});

}

//overrides the wikihowbits.js one
EditFinder.prototype.initToolTitle = function() {
	$(".wh_block").before("<div id='editfinder_tool_title'>" + $(".firstHeading").html() + "</div>");
}


EditFinder.prototype.getEditType = function() {
	return $('#editfinder_edittype').val();
}

EditFinder.prototype.getThoseInterests = function() {
	if ($('#editfinder_interests').is(':visible')) {
		//close it
		$('#editfinder_interests').slideUp(function() {
			if ($('#ef_num_cats').val() != $(".csui_category").size()) {
				window.location.reload();
			}
		});
	}
	else {
		if ($('#editfinder_interests').length) {
			//already exists? don't load it again. just show it.
			$('#editfinder_interests').slideDown();
		}
		else {
			//load it and open it
			mw.loader.using( ['ext.wikihow.catsearchui'], function () {

				var url = '/Special:CatSearchUI?embed=1';
				$.get(url, function(data) {
					$('#editfinder_tool_title').after('<div id="editfinder_interests">'+data+'</div>');
					$('#editfinder_interests').slideDown(function() {
						WH.CatSearchUI.initAC();
					});
				});

			});
		}
	}
}

EditFinder.prototype.getThoseCats = function() {
	jQuery('#dialog-box').html('');
	var efType = editFinder.getEditType();

	mw.loader.using( ['ext.wikihow.SuggestedTopics'], function () {

		jQuery('#dialog-box').load('/Special:SuggestCategories?type=' + efType, function(){
			if (efType !== '') {
				jQuery('#suggest_cats').attr('action',"/Special:SuggestCategories?type=" + efType);
			}
			jQuery('#dialog-box').dialog( "option", "position", 'center' );
			jQuery('#dialog-box td').each(function(){
				var myInput = $(this).find('input');
				var position = $(this).position();
				$(myInput).css('top', position.top + 10 + "px");
				$(myInput).css('left', position.left + 10 + "px");
				$(this).click(function(){
					editFinder.choose_cat($(this).attr('id'));
				})
			})
			jQuery('#check_all_cats').click(function(){
				var cats = jQuery('form input:checkbox');
				var bChecked = jQuery(this).prop('checked');
				for (i=0;i<cats.length;i++) {
					var catid = cats[i].id.replace('check_','');
					editFinder.choose_cat(catid,bChecked);
				}
			});
		});
		jQuery('#dialog-box').dialog({
			width: 826,
			modal: true,
			closeText: 'x',
			title: 'Categories'
		});

	});
}

EditFinder.prototype.showCatChooseLink = function() {
	var html = '<a id="editfinder_hdr_choose" class="editfinder_choose_cats">'+mw.message('change_topics').text()+'</a>';
	$('#editfinder_tool_title').append(html);
}

EditFinder.prototype.choose_cat = function(key,bChoose) {
	safekey = key.replace("&", "and");
 	var e = $("#" + safekey);

	//forcing it or based off the setting?
	if (bChoose == null)
		bChoose = (e.hasClass('not_chosen')) ? true : false;

 	if (bChoose) {
 		e.removeClass('not_chosen');
 		e.addClass('chosen');
 		document.suggest_cats.cats.value += ", " + key;
		jQuery('#check_' + safekey).prop('checked', true);
 	} else {
 		e.removeClass('chosen');
 		e.addClass('not_chosen');
 		var reg = new RegExp (key, "g");
 		document.suggest_cats.cats.value = document.suggest_cats.cats.value.replace(reg, '');
		jQuery('#check_' + safekey).prop('checked', false);
		jQuery('#check_all_cats').prop('checked', false);
 	}
}

EditFinder.prototype.getArticle = function(the_id) {
	var url = '/Special:EditFinder?fetchArticle=1';
	var e = jQuery('.firstHeading a');
	if (e.html())
		url += '&skip=' + encodeURIComponent(e.html());
	var title = '';

	//add the edit type
	var efType = editFinder.getEditType();
	if (efType !== '')
		url += '&edittype=' + efType;

	//add the article id if we need a specific one
	if (the_id)
		url += '&id=' + the_id;

	jQuery('#editfinder_article_inner').fadeOut('fast');
	jQuery('#editfinder_preview').fadeOut('fast',function() {
		jQuery('#editfinder_spinner').fadeIn();

		jQuery.getJSON(url, function (data) {
			editFinder.display(data['title'], data['url'], data['aid'],'editfinder_preview','intro','',data['cat']);
		});
	});
}

//
//
EditFinder.prototype.display = function (title, url, id, DIV, origin, currentStep, userCat) {
	this.m_title = title;
	this.m_url = url;
	this.m_product = 'editfinder';
	this.m_textAreaID = 'summary';
	this.m_currentStep = 0;

	// set up post- dialog load callback
	var showBox = this.m_currentStep !== 0;
	var that = this;

	var urlget = '/Special:EditFinder?show-article=1&aid=' + id;

	//add the edit type
	var efType = editFinder.getEditType();
	if (efType !== '')
		urlget += '&edittype=' + efType;

	jQuery.get(urlget, function(data) {
		jQuery('#' + DIV).html(data);

		//stop spinning and show stuff
		jQuery('#editfinder_spinner').fadeOut('fast',function() {

			//fill in the blanks
			if (title == undefined) {
				editFinder.disableTopButtons();
				titlelink = '[No articles found]';
				if (efType == 'topic') editFinder.getThoseInterests();
			}
			else {
				titlelink = '<a href="'+url+'">'+title+'</a>';
				editFinder.resetTopButtons();
				editFinder.updateCat(userCat);
				jQuery('#editfinder_cat_header').show();
				jQuery('#editfinder_yes').unbind('click');
				jQuery('#editfinder_yes').click(function(e) {
					e.preventDefault();
					if (!jQuery(this).hasClass('clickfail')) {
						editFinder.edit(id);
					}
				});
			}
			jQuery(".firstHeading").html(titlelink);

			jQuery('#editfinder_article_inner').fadeIn();
			jQuery('#' + DIV).fadeIn();

			// show post-loaded youtube embed videos
			WH.showEmbedVideos();
		});
	});

}

EditFinder.prototype.edit = function (id,title) {
	var url = '/Special:EditFinder?edit-article=1&aid=' + id;

	jQuery.ajax({
		url: url,
		success: function(data) {
			document.getElementById('editfinder_preview').innerHTML = data;
			jQuery('#weave_button').css('display','none');
			jQuery('#imageupload_button').css('display','none');
			editFinder.restoreToolbarButtons();
			//Preview button
			jQuery('#wpPreview').click(function() {
				editfinder_preview = true;
			});
			//Publish button
			$('#wpSave').addClass('op-action').click(function() {
				editfinder_preview = false;
			});
			//form submit
			jQuery('#editform').submit(function(e) {
				e.preventDefault();
				//just a preview?
				if (editfinder_preview) {
					editFinder.showPreview(id);
					jQuery('html, body').animate({scrollTop:0});
				}
				else {
					//pop conf modal
					if (editFinder.getEditType() == 'topic') {
						editFinder.closeConfirmation(true);
						WH.maEvent('edit_topic_greenhouse', { category: 'edit_topic_greenhouse' }, false);
						return false;
					}
					else {
						editFinder.displayConfirmation(id);
					}
				}
			});

			//pre-fill summary
			jQuery('#wpSummary').val("Edit from "+mw.message('app-name').text()+": " + editFinder.getEditType().toUpperCase());

			//cancel link update
			var cancel_link = jQuery('#mw-editform-cancel').attr('href');
			cancel_link += '/'+editFinder.getEditType();
			jQuery('#mw-editform-cancel').attr('href',cancel_link);

			//make Cancel do the right thing
			jQuery('.editButtons #edit_cancel_btn').unbind('click');
			jQuery('.editButtons #edit_cancel_btn').click(function(e) {
				e.preventDefault();
				//do we need to make the preview disappear?
				if (editfinder_preview) {
					jQuery('#editfinder_preview_updated').fadeOut('fast');
				}
				editFinder.cancelConfirmationModal(id);
			});


			// change titles for buttons with shortcut keys
			var mod = Mousetrap.defaultModifierKeys;
			mod = mod.substring(0, mod.length - 1);
			$('#wpTextbox1').addClass('mousetrap');
			$('#wpSave').attr('title', 'publish [' + mod + ' p]').attr('accesskey', '');
			$('#wpPreview').attr('title', 'preview [' + mod + '  v]').attr('accesskey', '');
			$('.editButtons #edit_cancel_btn').attr('title', 'cancel [' + mod + ' c]').attr('accesskey', '');

			//disable edit/skip choices
			editFinder.disableTopButtons();


			//throw cursor in the textarea
			jQuery('#wpTextbox1').change(function() {
				g_bEdited = true;
			});

			//add the id to the action url
			jQuery('#editform').attr('action',jQuery('#editform').attr('action')+'&aid='+id+'&type='+ editFinder.getEditType());
		}
	});
}

EditFinder.prototype.showPreview = function (id) {
	var editform = jQuery('#wpTextbox1').val();
	var url = '/index.php';
	//var url = '/index.php?action=submit&wpPreview=true&live=true';

	// According to MW, this is only used if the wikitext contains magic
	// words such as {{PAGENAME}}
	// See: http://www.mediawiki.org/wiki/Manual:Live_preview
	var thisTitle = this.m_url.substring(1);

	jQuery.ajax({
		url: url,
		type: 'POST',
		data: $('#editform').serialize() + '&wpPreview=true&live=true&action=edit&title=' + thisTitle,
		success: function(data) {

			var XMLObject = data;
			var previewElement = jQuery(data).find('preview').first();

			/* Inject preview */
			var previewContainer = jQuery('#editfinder_preview_updated');
			if ( previewContainer && previewElement ) {
				previewContainer.html(previewElement.first().text());
				previewContainer.slideDown('slow');
			}
		}
	});
}

EditFinder.prototype.upTheStats = function() {
	var edittype = editFinder.getEditType().toLowerCase();
	var statboxes = '#iia_stats_today_repair_'+edittype+',#iia_stats_week_repair_'+edittype+',#iia_stats_all_repair_'+edittype+',#iia_stats_group';
	$(statboxes).each(function(index, elem) {
			$(this).fadeOut(function () {
				var cur = parseInt($(this).html());
				$(this).html(cur + 1);
				$(this).fadeIn();
			});
		}
	);
}

EditFinder.prototype.displayConfirmation = function( id ) {
	var url = '/Special:EditFinder?confirmation=1&type=' + editFinder.getEditType() + '&aid=' + id;

	jQuery('#dialog-box').load(url, function() {
		jQuery('#dialog-box').dialog({
		   width: 450,
		   modal: true,
		   closeText: 'x',
		   title: 'Article Greenhouse Confirmation',
			closeOnEscape: true,
			position: 'center'
		});

		$('#ef_modal_yes').click(function() {
			 editFinder.closeConfirmation(true);
			 return false;
		});
		$('#ef_modal_no').click(function() {
			 editFinder.closeConfirmation(false);
			 return false;
		});

		var mod = Mousetrap.defaultModifierKeys;
		Mousetrap.bind(mod + 'y', function() {$('#ef_yes').click();});
		Mousetrap.bind(mod + 'n', function() {$('#ef_no').click();});
	});
}

EditFinder.prototype.closeConfirmation = function( bRemoveTemplate ) {
	//removing the template?
	if (bRemoveTemplate) {
		var text = jQuery('#wpTextbox1').val();
		var reg = new RegExp('{{' + editFinder.getEditType() + '[^\r\n]*?}}','i');
		jQuery('#wpTextbox1').val(text.replace(reg,''));
	}

	//close modal window
	if (jQuery('#dialog-box').hasClass('ui-dialog-content')) {
		jQuery('#dialog-box').dialog('close');
	}
	jQuery('#img-box').html('');
	editFinder.resetTopButtons();

	jQuery('#editfinder_article_inner').fadeOut('fast');
	jQuery('#editfinder_preview').fadeOut('fast');
	jQuery('#editfinder_preview_updated').fadeOut('fast', function() {
		jQuery('#editfinder_spinner').fadeIn();
		jQuery('html, body').animate({scrollTop:0});
	});

	//submit
	jQuery.ajax({
		type: 'POST',
		url: jQuery('#editform').attr('action'),
		data: jQuery('#editform').serialize()
	});

	editFinder.upTheStats();

	//next!
	editFinder.getArticle();
}

EditFinder.prototype.cancelConfirmationModal = function( id ) {
	var url = '/Special:EditFinder?cancel-confirmation=1&aid=' + id;

	if (g_bEdited) {
		jQuery('#dialog-box').load(url, function(data) {
			//changes; get the box
			jQuery('#dialog-box').dialog({
			   width: 450,
			   modal: true,
			   closeText: 'x',
			   title: 'Article Greenhouse Confirmation',
				closeOnEscape: true,
				position: 'center'
			});

			//initialize buttons
			jQuery('#efcc_yes').unbind('click');
			jQuery('#efcc_yes').click(function(e) {
				e.preventDefault();
				jQuery('#dialog-box').dialog('close');
				jQuery('html, body').animate({scrollTop:0});
				editFinder.resetTopButtons();
				editFinder.getArticle(id);

			});
			jQuery('#efcc_no').click(function() {
				jQuery('#dialog-box').dialog('close');
			});
		});
	}
	else {
		//no change; go back
		jQuery('html, body').animate({scrollTop:0});
		editFinder.resetTopButtons();
		editFinder.getArticle(id);
		return;
	}
}

EditFinder.prototype.disableTopButtons = function() {
	jQuery('#editfinder_head').slideUp();
	return;
}

EditFinder.prototype.resetTopButtons = function() {
	jQuery('#editfinder_head').slideDown();
	return;
}

//grab an abbreviated list of a user's chosen interests
EditFinder.prototype.getUserInterests = function() {
	var url = '/Special:CategoryInterests?a=get';
	var cats = '';

	$.getJSON(url, function(data) {
			editFinder.updateCat(data.interests);
			jQuery('#editfinder_cat_header').show();

			if (data.interests == '') {
				editFinder.getThoseInterests();
			}
	});
	return;
}

//grab an abbreviated list of a user's chosen categories
EditFinder.prototype.getUserCats = function() {
	var url = '/Special:SuggestCategories?getusercats=1';
	var cats = '';

	jQuery.ajax({
		url: url,
		success: function(data) {
			cats = editFinder.formatCats(data);
			jQuery('#editfinder_cat_header').show();
			jQuery('#user_cats').html(mw.message('gh_interests').text()+cats);
		}
	});
	return;
}
/*

*  Adapted from EditPage.php code since we kind of hack the edit form in place in the greenhouse
*/
EditFinder.prototype.restoreToolbarButtons = function() {
	if(window.mw){
		mw.loader.using("mediawiki.action.edit", function() {
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Bold text", "\'\'\'", "\'\'\'", "Place bold text here", "mw-editbutton-bold");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Italic text", "\'\'", "\'\'", "Italic text", "mw-editbutton-italic");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Internal link", "[[", "]]", "Link title", "mw-editbutton-link");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "External link (remember http:// prefix)", "[", "]", "http://www.example.com link title", "mw-editbutton-extlink");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Level 2 headline", "\n== ", " ==\n", "Headline text", "mw-editbutton-headline");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Embedded image", "[[Image:", "]]", "Example.jpg", "mw-editbutton-image");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Media file link", "[[Media:", "]]", "Example.ogg", "mw-editbutton-media");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Ignore wiki formatting", "\x3cnowiki\x3e", "\x3c/nowiki\x3e", "Insert non-formatted text here", "mw-editbutton-nowiki");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Your signature with timestamp", "--~~~~", "", "", "mw-editbutton-signature");
		mw.toolbar.addButton("/skins/owl/images/1x1_transparent.gif", "Horizontal line (use sparingly)", "\n----\n", "", "", "mw-editbutton-hr");

		// Create button bar
		$(function() { mw.toolbar.init(); } );
		});
	}
}

EditFinder.prototype.updateCat = function(cat) {
	if (cat) {
		jQuery('#user_cats').html(mw.message('gh_topic_chosen',cat).text());
	}
}

// format the comma array of the categories for display
EditFinder.prototype.formatCats = function(cats_string) {
	cats = cats_string.replace(/-/g, " ");
	if (cats.length == 0) {
		cats = 'No interests selected';
	}

	if (cats.length > 50)
		cats = cats.substring(0,50) + '...';

	return cats;
}

var editFinder = new EditFinder();

//kick it
$(document).ready(function() {
	editFinder.init();
});

//stat stuff
updateStandingsTable = function() {
    var url = '/Special:Standings/EditFinderStandingsGroup?type=' + editFinder.getEditType();
    jQuery.get(url, function (data) {
        jQuery('#iia_standings_table').html(data['html']);
    },
	'json'
	);
	$("#stup").html(EF_WIDGET_LEADERBOARD_REFRESH / 60);
	window.setTimeout(updateStandingsTable, 1000 * EF_WIDGET_LEADERBOARD_REFRESH);
}

window.setTimeout(updateWidgetTimer, 60*1000);
window.setTimeout(updateStandingsTable, 100);


function updateWidgetTimer() {
    WH.updateTimer('stup');
    window.setTimeout(updateWidgetTimer, 60*1000);
}



