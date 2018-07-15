( function($, mw) {

var toolName = '/Special:CreatePage';
var html4_client = window.history.replaceState ? false : true;

if (html4_client) {
	resetTool(true);
}
else {
	window.addEventListener("popstate", function(e) {
		if (window.location.search) {
			initPage();
		}
		else {
			resetTool(true);
		}
	});
}

function initPage() {
	//check for historical pages
	if (window.location.search) {
		$.get(toolName+window.location.search+'&ajax=true',function(result) {
			result['topic'] ? loadTopicResults(result) : loadTitleResults(result);
		},'json');
	}

	if (html4_client) {
		$('#cp_title_input, #cp_existing_title_input').keypress(function(e) {
			if (e.which == 13) {
				e.preventDefault();
				$('#cp_title_btn').click();
			}
		});

		$('#cp_topic_input').keypress(function(e) {
			if (e.which == 13) {
				e.preventDefault();
				$('#cp_topic_btn').click();
			}
		});
	}

	$('#cp_title_btn').off().one('click', function() {
		qs = '?target='+$('#cp_title_input').val().replace(/ /g,'+');
		$.get(toolName+qs+'&ajax=true',function(result) {
			if (!result['new'] || result['list']) {
				pushItRealGood(qs);
			}
			loadTitleResults(result);
		},'json');
		return false;
	});

	$('#cp_topic_btn').off().on('click', function() {
		qs = '?topic='+$('#cp_topic_input').val().replace(/ /g,'+');
		$.get(toolName+qs+'&ajax=true',function(result) {
			pushItRealGood(qs);
			loadTopicResults(result);
		},'json');
		return false;
	});
}

function loadTitleResults(result) {
	resetTool(false);
	//wait for a tick since the resetTool() needs to finish
	setTimeout(function() {

		$('#cp_title_input_block').hide();
		$('#cp_title_results').removeClass().addClass(result['class']);
		$('#cpr_title_hdr').html(result['header']);

		if (result['new']) {
			//ah...that new article smell...
			if (result['list']) {
				//got alternates
				list = "<div id='cpr_list'>"+result['list']+"</div>";
				$('#cpr_title_text').html(list);

				$('.article_topic').click(function() {
					//reset all
					$('#cpr_list label').css('font-weight','normal');
					$('#cpr_list .article_options').slideUp();
					//do this one
					$(this).nextAll('label').css('font-weight','bold');
					$(this).nextAll('.article_options').slideDown();
				});

				$('.article_options_options a').click(function(e) {
					art_id = $(this).parent().attr('data-id');
					if (!art_id) return;

					e.preventDefault();
					//create redirect
					qs = '?target='+result['target'].replace(/ /g,'+')+'&createpage_title='+art_id;
					go_to = this.href;

					$.get(toolName+qs+'&ajax=true', function(data) {
						//send them on their way
						location.href = go_to;
					});
				});
			}
			else {
				//original article, let's do this!
				//but only do ArticleCreator on English
				if (wgContentLanguage === 'en') {
					window.location.href = '/Special:ArticleCreator?t='+result['target'].replace(/ /g,'+');
				} else {
					window.location.href = '/index.php?title='+result['target'].replace(/ /g,'+')+'&action=edit';
				}
				return false;
			}
		}
		else {
			//whoops! already exists
			$('#cpr_title_text').html(result['html']);

			$('#cpr_write_something').click(function() {
				$(this).removeClass('primary').addClass('secondary');
				$('#cpr_text_bottom').slideDown(function() {
					$('#cp_existing_title_input').focus();
					$('#cp_existing_title_btn').off().click(function() {
						qs = '?target='+$('#cp_existing_title_input').val().replace(/ /g,'+');
						$.get(toolName+qs+'&ajax=true',function(result) {
							if (!result['new'] || result['list']) {
								pushItRealGood(qs);
							}
							loadTitleResults(result);
						},'json');
						return false;
					});
				});
			});

			$('#cpr_add_something').click(function() {
				if (result['edit_url']) window.location.href = result['edit_url'];
			});
		}

		//show results
		$('#cp_title_results').slideDown();
	},500);
}

function loadTopicResults(result) {
	resetTool(true);
	//wait for a tick since the resetTool() needs to finish
	setTimeout(function() {
		$('#cp_topic_input').val(result['topic']);
		$('#cp_topic_results').removeClass().addClass(result['class']);
		$('#cpr_topic_hdr').html(result['header']);
		$('#cpr_topic_text').html(result['html']);
		//show topics
		$('#cp_topic_results').slideDown();
		//move the chosen title to the top section for further interactions...
		$('#cpr_topic_text a').click(function() {
			resetTool();
			qs = '?target='+$(this).attr('href').replace(/-/g,'+');
			$.get(toolName+qs+'&ajax=true',function(result) {
				if (!result['new'] || result['list']) {
					pushItRealGood(qs);
				}
				loadTitleResults(result);
			},'json');
			return false;
		});
	},500);
}

// TODO: determine if this is still used?
function keyxxx(e) {
	var key;
	if(window.event) {
		// for IE, e.keyCode or window.event.keyCode can be used
		key = e.keyCode;
	}
	else if(e.which) {
		// netscape
		key = e.which;
	}
	else {
		// no event, so pass through
		return true;
	}

	if (key == 13) {
		document.editform.related.options[document.editform.related.length] = new Option(document.editform.q.value,document.editform.q.value);
		document.editform.q.value = "";
		document.editform.q.focus();
		return false;
	}
}

// Used in createpage_step1box.tmpl.php inline html
window.searchTopics = function() {
	var cp_request = null;

	try {
		cp_request = new XMLHttpRequest();
	} catch (error) {
		try {
			cp_request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}

	var t = document.getElementById('createpage_title').value;
	cp_request.open('GET', "http://" + window.location.hostname + "/Special:CreatePageTitleResults?target=" + encodeURIComponent(t));
	cp_request.send('');
	cp_request.onreadystatechange = function() {
		if ( cp_request.readyState == 4) {
			if ( cp_request.status == 200) {
				var e = document.getElementById('createpage_search_results');
				e.innerHTML = cp_request.responseText;
				document.getElementById('cp_next').disabled = false;
			}
		}
	};

	var e = document.getElementById('createpage_search_results');
	e.innerHTML = "<center><img src='/extensions/wikihow/rotate.gif'><br/>Searching...</center>";
	return true;
};

// Hide all the results
function resetTool(bShowInput) {
	$('#cp_title_results').slideUp(function() {
		$('#cpr_title_text').html('');
		$('#cp_title_input').val('');
		if (bShowInput) $('#cp_title_input_block').slideDown();
	});
	$('#cp_topic_results').slideUp(function() {
		$('#cpr_topic_text').html('');
		$('#cp_topic_input').val('');
		$('.search_input').blur();
	});
}

function pushItRealGood(qs) {
	if (!html4_client) {
		history.pushState(null, null, qs);
	}
	else {
		//oh, HTML4...
		url = window.location.href.split("?")[0];
		window.location.href = url+qs;
	}
}

window.WH.shareTwitter = function (source) {
	//var title = encodeURIComponent(wgTitle);
	var title = wgTitle;
	var url = encodeURIComponent(location.href);

	if (title.search(/How to/) != 0) {
		title = 'How to '+title;
	}

	if (source == 'aen') {
		status = "I just wrote an article on @wikiHow - "+title+".";
	} else {
		status = "Reading @wikiHow on "+title+".";
	}

	// window.open('https://twitter.com/intent/tweet?text='+ status +' '+url );
	// open in a new, smaller window
	window.open('https://twitter.com/intent/tweet?text='+ status +' '+url, 'twitter_popup','left='+((window.screenX||window.screenLeft)+10)+',top='+((window.screenY||window.screenTop)+10)+',height=220px,width=550px,resizable=1,alwaysRaised=1');

	return false;
};

// used in a create page MW message: MediaWiki:createpage_review_options
window.saveandpublish = function() {
    window.onbeforeunload = null;
    document.editform.submit();
};

$('document').ready(function() {
	initPage();
});

}(jQuery, mediaWiki) );
