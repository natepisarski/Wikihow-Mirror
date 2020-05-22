(function($, mw) {

window.turnOffMtsSelect = function() {
	$('#header_container').removeClass('mts-mode');
	$('.mts-toggle').remove();
	$('#mts-header').remove();
	$('.mts-h').hide();
};

window.setupMtsSelect = function() {
	$('#header_container').addClass('mts-mode');
	$('#header_container').prepend('<div id="mts-header"></div>');
	var mtsModeHeader = 'Motion To Static Mode - Steps Selected: none';
	$('#mts-header').text(mtsModeHeader);
	$('#mts-header').prepend('<a id="mts-close" href="#">X</a>');
	$('.mwimg').each(function() {
		if ($(this).find('video.m-video').length) {
			var num = $(this).find('video.m-video:first').data('src');
			num = num.split("Step ").pop();
			num = num.split(/[\s.]+/).shift();
			var toggle = '<div class="mts-toggle" data-num="'+num+'">Toggle To Static</div>';
			$(this).append(toggle);
		}

	});
	$('.mwimg').hover( function() {
		if ($(this).find('video.m-video').length) {
			$(this).addClass('mts-item');
			$(this).find('.mts-toggle').show();
		}
	}, function() {
		if ($(this).find('video.m-video').length) {
			$(this).removeClass('mts-item');
			if (!$(this).find('.mts-toggle').hasClass('toggleset')) {
				$(this).find('.mts-toggle').hide();
			}
		}
	});

	$('#mts-close').on('click',function(e) {
		e.preventDefault();
		turnOffMtsSelect();
	});

	$('.mts-toggle').on('click',function(e) {
		if (!$(this).hasClass('toggleset')) {
			$(this).parents('.mwimg:first').find('video').hide();
			$(this).text('Revert to Video');
			$(this).addClass('toggleset');
		} else {
			$(this).parents('.mwimg:first').find('video').show();
			$(this).removeClass('toggleset');
			$(this).text('Toggle to Static');
		}
		var selectedSteps = $('.toggleset').map(function(v,i) {
			return $(i).data('num');
		}).get();
		selectedSteps = selectedSteps.join();
		if (selectedSteps == '') {
			selectedSteps = 'none';
		}
		var mtsModeHeader = 'Motion To Static Mode - Steps Selected: ' + selectedSteps;
		$('#mts-header').text(mtsModeHeader);
		$('#mts-header').prepend('<a id="mts-close" href="#">X</a>');
	});
};

window.setupMtsMenu = function() {
	$('#mts-title').on('click',function(e) {
		e.preventDefault();
		return;
	});

	$('#mts-cancel').on('click',function(e) {
		e.preventDefault();
		turnOffMtsSelect();
	});

	$('#motion-to-static').hover(function(e) {
		$('#mts-content').show();
		$("#mts-done").remove();
	}, function() {
		$('#mts-content').hide();
	});

	$('#motion-to-static a').on('click',function(e) {
		var type = $(e.target).data('type');
		if (type == 'select') {
			setupMtsSelect();
		}
		$('#mts-textarea').data('type', type);

		var savedEditMessage = $.cookie('mts_'+type);
		if (savedEditMessage) {
			$('#mts-textarea').val(savedEditMessage);
		} else {
			$('#mts-textarea').val('changing some videos to static images');
			if ( type =='changeall' ) {
				$('#mts-textarea').val('changing all videos to static images');
			} else if ( type =='removeall' ) {
				$('#mts-textarea').val('removing all images from article');
			}
		}

		var resetStuChecked = $.cookie('mts_stu');
		if (resetStuChecked == 'false') {
			$('#mts-stu-box').prop('checked', false);
		}
		$('#mts-content').hide();
		$('.mts-h').show();
		e.preventDefault();
		return;
	});

	var mtsSubmitted = false;
	$('#mts-submit').on('click',function(e) {
		if (mtsSubmitted) {
			e.preventDefault();
			return;
		}
		$('#mts-done').text('');
		var textBox = $('#mts-textarea').val();
		var type = $('#mts-textarea').data('type');
		if (textBox == '') {
			alert("you must enter text to submit");
			e.preventDefault();
			return;
		}
		// save the edit message for this type
		$.cookie('mts_'+type, textBox);

		var url = '/Special:PageStats';
		var action ='motiontostatic';
		var payload = {
			action:action,
			editsummary:textBox,
			type:type,
			pageid:wgArticleId
		};
		if (type == 'select') {
			var selectedSteps = $('.toggleset').map(function(v,i) {
				return $(i).data('num');
			}).get();
			if ( selectedSteps == '') {
				alert("no steps selected");
				e.preventDefault();
				return;
			}
			selectedSteps = selectedSteps.join();
			payload['steps'] = selectedSteps;
		}
		var resetStu = $('#mts-stu-box').prop('checked');
		$.cookie('mts_stu', resetStu);
		//if (resetStu == false) {
			//resetStu = confirm("also reset all stu data for this page?");
		//}
		//console.log("payload", payload);return;
		mtsSubmitted = true;
		$.post(
			url,
			payload,
			function(result) {
				mtsSubmitted = false;
				var data = JSON.parse(result);
				console.log("data", data);
				console.log("data success", data.success);
				console.log("data success true", data.success == true);
				if ($('#mts-done').length == 0) {
					$('#mts-submit').after('<p id="mts-done"></p>');
				}
				$('#mts-done').text(data.message);
				if (data.success == true) {
					$('.mts-h').hide();
					$('#mts-textarea').val('');
					$('#mts-textarea').data('type', '');
					if (resetStu) {
						console.log("will reset stu");
						clearStu( function() {
							// reload doesn't seem to work from this context in chrome, so
							// I'm disabling this until it can be further fixed. -Reuben, may 2020
							//window.location.reload();
						} );
					} else {
						//window.location.reload();
					}
				} else {
					alert(data.message);
				}
			});
		e.preventDefault();
		return;
	});
};

window.setupEditMenu = function() {
	$('#staff-editing-menu-title').on('click',function(e) {
		e.preventDefault();
		return;
	});

	$('#staff-editing-menu').hover(function(e) {
		$('#staff-editing-menu-content').show();
		$("#sem-done").remove();
	}, function() {
		$('#staff-editing-menu-content').hide();
	});

	$('#staff-editing-menu a').on('click',function(e) {
		if ($(e.target).data('type') == 'summaryvideo') {
			$('#sem-hp label').text('Request New In a Hurry');
		} else {
			$('#sem-hp label').text('High Priority');
		}
		var text = $(e.target).text();
		$('#semt-type').remove();
		var type = $('<div id="semt-type" class="sem-h"></div>').text(text);
		$('#sem-textarea').data('type', text);
		$('#staff-editing-menu').after(type);
		$('#staff-editing-menu-content').hide();
		// if this is the summary then set the text to something else
		$('.sem-h').show();
		e.preventDefault();
		return;
	});

	var staffEditSubmitted = false;
	$('#staff-editing-menu-submit').on('click',function(e) {
		if (staffEditSubmitted) {
			e.preventDefault();
			return;
		}
		var textBox = $('#sem-textarea').val();
		var type = $('#sem-textarea').data('type');
		if (textBox == '') {
			alert("you must enter text to submit");
			e.preventDefault();
			return;
		}

		staffEditSubmitted = true;
		var url = '/Special:PageStats';
		var action ='editingoptions';
		var highpriority = $('#sem-hp-box').prop('checked');
		$.post(
			url,
			{action:action,textbox:textBox,type:type,pageid:wgArticleId,highpriority:highpriority},
			function(result) {
				staffEditSubmitted = false;
				$('.sem-h').hide();
				$('#sem-textarea').val('');
				$('#sem-textarea').data('type', '');
				$('#staff-editing-menu-submit').after('<p id="sem-done">your submission has been saved</p>');
		});
		e.preventDefault();
		return;
	});
};

window.clearStu = function(onComplete) {
	var url = '/Special:Stu';
	var pagesList = window.location.origin + window.location.pathname;

	$.post(url, {
		"discard-threshold" : 0,
		"data-type": "summary",
		"action" : "reset",
		"pages-list": pagesList
		},
		function(result) {
			console.log(result);
			if (typeof onComplete !== 'undefined') {
				onComplete();
			}
		}
	);
};

window.setupStaffWidgetClearStuLinks = function() {
	$('.clearstu').click(function(e) {
		e.preventDefault();
		var answer = confirm("reset all stu data for this page?");
		if (answer == false) {
			return;
		}
		clearStu();
	});
};

function initPageStats() {
	if ($('#staff_stats_box').length) {
		$('#staff_stats_box').html('Loading...');
		var type = "article";
		if ( window.location.pathname.match(/^\/Sample\//) ) {
			type = "sample";
		}
		var target = (type == "sample") ? wgSampleName : wgTitle;

		var getData = {'action':'ajaxstats', 'target':target, 'type':type};

		$.get('/Special:PageStats', getData, function(data) {
				var result = (data && data['body']) ? data['body'] : 'Could not retrieve stats';
				$('#staff_stats_box').html(result);
				if (data && data['error']) {
					console.log(data['error']);
				}

				if ($('.clearstu').length) {
					setupStaffWidgetClearStuLinks();
				}

				if ( $('#staff-editing-menu').length ) {
					setupEditMenu();
				}

				if ( $('#motion-to-static').length ) {
					setupMtsMenu();
				}

				if (WH.SummaryEditCTA) WH.SummaryEditCTA.showPageStatLink();
			}, 'json');
	}
}

initPageStats();

})(jQuery, mw);
