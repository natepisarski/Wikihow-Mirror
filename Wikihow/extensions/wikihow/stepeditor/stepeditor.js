(function($, mw) {
	var oldText;
	var beforeEditText;
	var toolURL = '/Special:StepEditor?';
	var editCTA;
	var editingMsg = "Editing...";
	var currentRevision;
	var timeoutId;

	$(document).ready(function() {
		if (mw.message( 'stepedit-active' ) == "off") {
			//allows us to turn it off instantly if we want.
			return;
		}

		if (!isNewBrowser()) {
			return;
		}

		if(mw.config.get('wgRevisionId') != mw.config.get('wgCurRevisionId')) {
			//This revision is not the most recent one, so don't allow editing (good revision stuff)
			return;
		}

		//position the edit link in the cases where there are large top images
		$(".stepedit").each(function(){
			imageElem = $(this).find(".mwimg");
			if ($(imageElem).length > 0) {
				if ($(imageElem).hasClass("largeimage")) {
					height = $(this).find("img").attr("height"); //calculate the height using the attr b/c the image might not actually be loaded yet
					$(this).find(".stepeditlink").css("top", height+"px");
				} else {
					//oops the image isn't a large image, so we shouldn't be editing this step anyway.
					$(this).find(".stepeditlink").remove();
				}
			}
		});

		//grab the revision to start. We may update this value after an edit
		currentRevision = mw.config.get('wgRevisionId');

		initEventListeners();
	});

	function isNewBrowser() {

		return ($.browser.msie && parseInt($.browser.version) > 8)
		|| (($.browser.firefox || $.browser.mozilla) && parseInt($.browser.version) >= 5)
		|| ($.browser.chrome && parseInt($.browser.version) >= 14)
		|| ($.browser.opera && parseInt($.browser.version) >= 15)
		|| ($.browser.safari && parseInt($.browser.version) >= 5);

	}

	function initEventListeners() {
		attachHovers();

		$(".stepeditlink").on("click", function(){

			if ($(this).hasClass('active')) {
				return false;
			}

			//gatTrack('step_editor', 'edit_step_click');

			stepli = $(this).parent();
			step = $(stepli).find(".step");

			editCTA = $(this).text();
			//$(this).text(editingMsg);

			$( document ).off( "mouseenter mouseleave", ".stepedit" );
			$(step).attr("contenteditable", true);
			$(stepli).addClass("editing");
			$(this).hide();

			oldText = $(step).html();
			//add the save and cancel buttons
			$(step).after("<div class='editbuttons'><a href='#' class='button primary se_save'>Save</a><a href='#' class='button secondary se_cancel'>Cancel</a><div class='clearall'></div></div></div>");

			//remove the first sentence bolding
			$(step).find("b").contents().unwrap();
			$(step)[0].normalize(); //this gets rid of extra text nodes and combines into one
			beforeEditText = $(step).html();

			//let's go ahead and check to see if the article is still at the most recent revision
			$.post(toolURL,
				{
					checkValid: true,
					articleId: mw.config.get('wgArticleId'),
					revisionId: currentRevision
				},
				function(result) {
					if (result['isValid'] != true) {
						//the revision is out of date
						newText = $(step).html();
						$(".stepeditlink").remove();
						gatTrack('step_editor', 'error_on_edit');
						openErrorModal(result, newText);
						clearEdit(stepli);
					}
				},
				'json'
			);

			return false;
		});

		$(document).on("click", ".se_save", function(e){
			e.preventDefault();

			//gatTrack('step_editor', 'save_click');

			ZeroClipboard.config({
				swfPath: "/extensions/wikihow/common/zero-clipboard/ZeroClipboard.swf"
			});

			stepLi = $(this).parent().parent();

			items = [];
			children = $(stepLi).find(".step")[0].childNodes;
			for(i = 0; i < children.length; i++){
				if(children[i].nodeType == 3) {
					items.push(children[i].nodeValue);
				} else {
					items.push($(children[i]).text());
				}
			}

			newText = items.join(' ');
			preSaveText = $(stepLi).find(".step").html();

			//check against preSaveText just to see if nothing has changed at all
			if (beforeEditText != preSaveText) {
				$.post(toolURL,
					{
						newStep: sanitize(newText),
						articleId: mw.config.get('wgArticleId'),
						stepNum: $(stepLi).find(".absolute_stepnum").html(),
						revisionId: currentRevision,
						wpCaptchaWord: $("#wpCaptchaWord").val(),
						wpCaptchaId: $("#wpCaptchaId").val()
					},
					function (result) {
						if (result['success'] == true) {
							clearEdit(stepLi);
							$(stepLi).find(".step").replaceWith(result['step']);
							//currentRevision = result['newRevision']; //maybe we'll try this later when we add logged in users
							if(result['isEditable'] == false) {
								$(stepLi).find(".stepeditlink").remove();
							}
							WH.opWHTracker.trackEdit();
							WH.maEvent('edit_step', { category: 'edit_step' }, false);
							$(".stepeditlink").remove();
						} else {
							$(".stepeditlink").remove();
							gatTrack('step_editor', 'error_on_save');
							if(result['modal'] == true) {
								openErrorModal(result, newText);
								clearEdit(stepLi);
							} else {
								$(".stepeditwarning").remove(); //just in case there was one already there
								$(stepLi).find(".step").after(result['err']);
							}
						}
					},
					'json'
				).fail(function() {
						//let's just fail gracefully
						clearEdit(stepLi);
					});
			} else {
				$(stepLi).find(".step").html(oldText);
				clearEdit(stepLi);
			}
		});

		$(document).on("click", ".se_cancel", function(e){
			e.preventDefault();

			//gatTrack('step_editor', 'cancel_click');

			stepLi = $(this).parent().parent();
			step = $(stepli).find(".step");
			$(step).html(oldText);

			clearEdit(stepLi);

		});

		//we watch for paste events so we can clean the data
		$(document).on("paste", ".editing div[contenteditable='true']", handlepaste);

		//hide the edit links when they scroll behind a method header
		$(window).scroll(function() {
			$(".stepeditlink:visible").each(function(){
				linkObj = this;
				$(".steps.sticky .mw-headline").each(function(){
					if(collision(this, linkObj)) {
						$(linkObj).hide();
					}
				});
			})
		});
	}

	function openErrorModal(result, newText) {
		newText = sanitize(newText); //remove any unwanted html characters like &nbsp;

		$('#dialog-box').html(result['err']);
		$('#dialog-box').dialog({
			width: 360,
			modal: true,
			resizable: false,
			draggable: false,
			closeText: 'x',
			dialogClass: 'step_editor_dialog',
			title: '<strong>Uh Oh!</strong><br />Sadly we couldn\'t save your edit.',
			open: function() {
				$('.ui-dialog #ui-step').text(newText);
				$(".ui-dialog").removeClass("ui-corner-all");
			},
			close: function() {
				$(".ui-dialog").removeClass('step_editor_dialog');
			}
		});

		$("#done").click(function(){
			$("#dialog-box").dialog("close");
			return false;
		});



		var client = new ZeroClipboard( document.getElementById("copypaste") );

		$("#copypaste").attr("data-clipboard-text", newText);

		client.on( "aftercopy", function( event ) {
			$("#copypaste").text("Text copied");
		} );
	}

	function clearEdit(stepLi) {
		step = $(stepLi).find(".step");
		$(stepLi).removeClass("editing");
		$('.editbuttons').remove();
		$(stepLi).find(".stepeditlink").removeClass('active');
		$(step).attr("contenteditable", false);
		$(".stepeditwarning").remove();

		attachHovers();
	}

	function attachHovers() {
		$(document).on("mouseenter", ".stepedit", function(){
			$(".stepeditlink").hide();
			clearTimeout(timeoutId);
			$(this).find(".stepeditlink").show();
		});

		$(document).on("mouseleave", ".stepedit", function(){
			editLink = $(this).find(".stepeditlink");
			timeoutId = setTimeout(
				function(){
					$(editLink).hide();
				},
				500
			);
		});
	}

	/******************
	 *
	 * This takes the text that they've entered and sanitizes it.
	 * This will get added to over time as we allow more complicated
	 * html to be used.
	 *
	 *****************/
	function sanitize(text) {
		text = text.replace(/&nbsp;/gi, " ");
		text = text.replace(/<br>/gi, " ");
		text = text.replace(/\n/gi, " ");

		return text;
	}

	function handlepaste (e) {
		elem = this;
		var savedcontent = elem.innerHTML;
		if (e && e.clipboardData && e.clipboardData.getData) {// Webkit - get data from clipboard, put into editdiv, cleanup, then cancel event
			if (/text\/html/.test(e.clipboardData.types)) {
				elem.innerHTML = e.clipboardData.getData('text/html');
			}
			else if (/text\/plain/.test(e.clipboardData.types)) {
				elem.innerHTML = e.clipboardData.getData('text/plain');
			}
			else {
				elem.innerHTML = "";
			}
			waitforpastedata(elem, savedcontent);
			if (e.preventDefault) {
				e.stopPropagation();
				e.preventDefault();
			}
			return false;
		}
		else {
			waitforpastedata(elem, savedcontent);
			return true;
		}
	}

	function waitforpastedata (elem, savedcontent) {
		if (savedcontent != elem.innerHTML) {
			processpaste(elem, savedcontent);
		}
		else {
			that = {
				e: elem,
				s: savedcontent
			}
			that.callself = function () {
				waitforpastedata(that.e, that.s)
			}
			setTimeout(that.callself,20);
		}
	}

	/**************
	 *
	 * After the past has happened, this cleans
	 * the data since lots of extraneous stuff gets
	 * in when you paste to a contenteditable div
	 *
	 **************/
	function processpaste (elem, savedcontent) {
		elem.innerHTML = $(elem).text();
	}

	function collision(elem1, elem2) {
		var left1 = $(elem1).offset().left;
		var top1 = $(elem1).offset().top;
		var height1 = $(elem1).outerHeight(true);
		var width1 = $(elem1).outerWidth(true);
		var bottom1 = top1 + height1;
		var right1 = left1 + width1;
		var left2 = $(elem2).offset().left;
		var top2 = $(elem2).offset().top;
		var height2 = $(elem2).outerHeight(true);
		var width2 = $(elem2).outerWidth(true);
		var bottom2 = top2 + height2;
		var right2 = left2 + width2;

		if (bottom1 < top2 || top1 > bottom2 || right1 < left2 || left1 > right2) return false;
		return true;
	}


})(jQuery, mw);


