(function ($, mw) {

WH.SuggestedTopics = {};

var howtoDefaultText = "How to...";

$(document).ready(function() {
	$('#entry_howto').focus(howtoFocus);
	$('#entry_howto').blur(howtoBlur);
	$('#choose_cats').click(function(){
		$('#dialog-box').html('');
		$('#dialog-box').load('/Special:SuggestCategories', function(){
			$('#dialog-box').dialog( "option", "position", 'center' );
			$('#dialog-box td').each(function(){
				var myInput = $(this).find('input');
				var position = $(this).position();
				$(myInput).css('top', position.top + 10 + "px");
				$(myInput).css('left', position.left + 10 + "px");
				$(this).click(function(){
					chooseCat($(this).attr('id'));
				})
			})
			$('#check_all_cats').click(function(){
				var cats = $('form input:checkbox');
				var bChecked = $(this).prop('checked');
				for (i=0;i<cats.length;i++) {
					var catid = cats[i].id.replace('check_','');
					chooseCat(catid,bChecked);
				}
			});
		});
		$('#dialog-box').dialog({
			width: 826,
			modal: true,
			title: 'Categories',
			closeText: 'x'
		});
		return false;
	});
});

function howtoFocus() {
	if ($(this).val() == howtoDefaultText) {
		$(this).val("");
	}
}

function howtoBlur() {
	if ($(this).val() == "") {
		$(this).val(howtoDefaultText);
	}
}

WH.SuggestedTopics.changeCat = function() {
	location.href='/Special:ListRequestedTopics?category=' + escape(document.getElementById('suggest_cat').value);
};

WH.SuggestedTopics.saveSuggestion = function() {
	var n = document.getElementById('newsuggestion').value;
	var id = document.getElementById('title_id').value;
	document.suggested_topics_manage["st_newname_" + id].value = n;
	document.getElementById("st_display_id_" + id).innerHTML= n;
	for (i=0;i<document.suggested_topics_manage.elements.length;i++) {
		if (document.suggested_topics_manage.elements[i].type == 'radio'
			&& document.suggested_topics_manage.elements[i].name == 'ar_' + id
			&& document.suggested_topics_manage.elements[i].value == 'accept') {
			document.suggested_topics_manage.elements[i].checked = true;
		}
	}

	$('#dialog-box').dialog('close');
};

var gName = null;
WH.SuggestedTopics.editSuggestion = function(id) {
	gName = $('#st_display_id_' + id).html();
	$('#dialog-box').load('/Special:RenameSuggestion?name='+escape(gName)+'&id='+id);
	$('#dialog-box').dialog({
		modal: true,
		title: 'Edit title',
		closeText: 'x',
		width: 500
	});
	return false;
};

WH.SuggestedTopics.checkSTForm = function() {
	if (document.suggest_topic_form.suggest_topic.value =='') {
		alert(mw.msg('suggest_please_enter_title'));
		return false;
	}
	if (document.suggest_topic_form.suggest_category.value =='') {
		alert(mw.msg('suggest_please_select_cat'));
		return false;
	}
	if (document.suggest_topic_form.suggest_email_me_check.checked && document.suggest_topic_form.suggest_email.value =='') {
		alert(mw.msg('suggest_please_enter_email'));
		return false;
	}
	return true;
};

function chooseCat(key,bChoose) {
	var safekey = key.replace("&", "and");
	var e = $("#" + safekey);

	//forcing it or based off the setting?
	if (bChoose == null)
		bChoose = (e.hasClass('not_chosen')) ? true : false;

	if (bChoose) {
		e.removeClass('not_chosen');
		e.addClass('chosen');
		document.suggest_cats.cats.value += ", " + key;
		$('#check_' + safekey).prop('checked', true);
	} else {
		e.removeClass('chosen');
		e.addClass('not_chosen');
		var reg = new RegExp (key, "g");
		document.suggest_cats.cats.value = document.suggest_cats.cats.value.replace(reg, '');
		$('#check_' + safekey).prop('checked', false);
	}
}

/*
function reloadTopRow(){
	$("#top_suggestions_top").fadeOut(400, function() {
		if (Math.random() < 0)  {
			// bat boy easter egg!
			$('#dialog-box').html('<center><img src="http://www.freakingnews.com/images/contest_images/bat-boy.jpg" style="height: 400px;"/><br/>');
			//$('#dialog-box').load(url);
			$('#dialog-box').dialog({
				modal: true,
				width: 400,
				title: 'Surprise!',
				show: 'slide',
				closeOnEscape: true,
				position: 'center'
			});
		}
		$("#top_suggestions_top").load('/Special:RecommendedArticles/TopRow',
				function () {
					$("#top_suggestions_top").fadeIn();
				}
			);
	}
	);
}
*/

})(jQuery, mw);
