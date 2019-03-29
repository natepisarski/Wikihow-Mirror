WH = WH || {};

WH.ArticleCreator = function() {
	
	// Redirect older IE users to the guided editor
	if ($.browser.msie && $.browser.version < 9) {
		window.location.href = mw.util.getUrl(mw.util.getParamValue('t'), {'action': 'edit'});
		return;
	}
		
	// A non-appended DOM node to use as a dispatcher for model update events
	var dispatcher = $('<div />');
		
	var Util = {
		 removeCRs: function(str) {
			 return str.replace(/\r?\n|\r/g, '');
		 },
		// Strip out junk from input including html tags, 
		// leading and trailing whitespace, == or === 
		// or * or # if found at the beginning of a line
		strip: function(html) {
			var txt = document.createElement("DIV");
			txt.innerHTML = html;
			txt = txt.textContent || txt.innerText || "";
			txt = this.removeCRs(txt).trim();
			txt = txt.replace(/^\s*[*#]/, '').replace(/^===|^==/, '');
			return txt;
		 },
		 // We have to strip method or part from method names or articles 
		 // don't render properly
		 stripMethodPart: function (txt) {
			 var re = new RegExp('method\ +|part\ +', 'gi');
			 txt = txt.replace(re, '');
			 return txt;
		 },
		 wrapPlaceholder: function(placeholder) {
			 return "<div>" + placeholder + "</div>";
		 },
		 linkify: function(txt) {
			 var regex = /https?:\/\/[\-\w@:%_\+.~#?,&\/\/=]+/g;
			 var matches = [];
			 while ((match = regex.exec(txt)) !== null){
			     matches.push(match[0]);
			 }

			 for (var i = 0; i < matches.length; i++) {
				 txt = txt.replace(matches[i], matches[i] + ' (<a href="' + matches[i] + '" rel="nofollow" target="_blank" class="ac_link external free">visit link</a>)');
			 }
			 return txt;
		 },
		 glowButton: function(button) {
			 if (!$(button).hasClass('ac_glowed') ) {
		    	$(button).addClass('ac_glowed ac_glowing');   
		    	setTimeout(function(){$(button).removeClass('ac_glowing')}, 5000);
			 }
		 }
	};
	
	// Configuration for the tinymce rich text editors
	var tinymceConfig = {
		skin_url: '/extensions/wikihow/articlecreator/skins/wikihow', // Don't use pad here to prevent cross-site font issue in Firefox - Bug #563
		content_css : '/extensions/wikihow/articlecreator/tinymce_content.css?' + wgWikihowSiteRev,
		skin: 'wikihow',
	    plugins: ["lists"],
	    toolbar: "bullist",
		valid_elements : "-ul/-ol,-li,div",
		forced_root_block : false,
		menubar: false,
		statusbar: false,
		setup: function(editor) {
		    // Set placeholder
		    var placeholder = $('#' + editor.id).attr('placeholder');
		    var wrappedPlaceholder = Util.wrapPlaceholder(placeholder);
		    if (typeof placeholder !== 'undefined' && placeholder !== false) {
		      var is_default = false;
		      editor.on('init', function() {
		        // get the current content
		        var cont = editor.getContent();

		        // If its empty and we have a placeholder set the value
		        if (cont.length === 0) {
					editor.setContent(wrappedPlaceholder);
					// Get updated content
					cont = wrappedPlaceholder;
		        }
		        // convert to plain text and compare strings
		        is_default = (cont == wrappedPlaceholder);

		        // nothing to do
		        if (!is_default) {
		        	return;
		        }
		      })
		      .on('keydown', function() {
		        // replace the default content if the same as original placeholder
		        if (editor.getContent() == wrappedPlaceholder) {
		        	editor.setContent('');
		        }
		      })
		      .on('keyup', function() {
			        // replace the default content if the same as original placeholder
			        if (editor.getContent() == wrappedPlaceholder) {
			        	editor.setContent('');
			        } else if (editor.getContent().length == 0){
			        	editor.setContent(wrappedPlaceholder);
			        } else if (editor.getContent().length > 0){
			        	var domMethod = $(editor.getContainer()).closest('li.ac_method');
			        	Util.glowButton($('.ac_add_li', domMethod));
			        }
		      })
		      .on('BeforeExecCommand', function(e){
		    	  if (e.command == "InsertUnorderedList" && editor.getContent() == wrappedPlaceholder) {
			          editor.setContent('');
			      }
		      })
		      .on('blur', function() {
		        if (editor.getContent().length === 0) {
		        	editor.setContent(wrappedPlaceholder);
		        }
		      });
		    }
		}
	};
	tinymce.suffix = '.min';
	tinymce.baseURL = '/extensions/wikihow/common/tinymce_4.5.3/js/tinymce';
	/*
	 * Object that builds wikitext given an Article object
	 */
	var WikitextBuilder  = function() {
	};
	
	WikitextBuilder.prototype.SECTION_SEPARATOR = '\n\n';
	WikitextBuilder.prototype.SECTION_HEADING_TEMPLATE = '== $name ==\n';
	WikitextBuilder.prototype.METHOD_HEADING_TEMPLATE = '=== $name ===\n';
	WikitextBuilder.prototype.STEP_LI_TYPE = '#';
	WikitextBuilder.prototype.BULLET_LI_TYPE = '*';
	WikitextBuilder.prototype.SUB_LI_TYPE = '#*';
	WikitextBuilder.prototype.PARTS_MAGIC_WORD = '\n__PARTS__\n';
	WikitextBuilder.prototype.METHODS_MAGIC_WORD = '\n__METHODS__\n';
	
	WikitextBuilder.prototype.buildArticle = function(article) {
		var wikitext = this.buildIntro(article.intro);
		wikitext += this.buildSteps(article.steps);
		wikitext += this.buildTips(article.tips);
		wikitext += this.buildWarnings(article.warnings);
		wikitext += this.buildReferences(article.references);
		wikitext += this.buildMagicWords(article);

		return wikitext;
	};

	WikitextBuilder.prototype.buildMagicWords = function(article) {
		var magicWords = '';
		if (article.steps.methods.length > 1) {
			if (article.steps.methodType == Method.PARTS_METHOD_TYPE) {
				magicWords += this.PARTS_MAGIC_WORD;
			} else if (article.steps.methodType == Method.METHODS_METHOD_TYPE){
				magicWords += this.METHODS_MAGIC_WORD;
			}
		}

		return magicWords;
	};

	WikitextBuilder.prototype.buildIntro = function(intro) {
		return intro + this.SECTION_SEPARATOR;
	};
	
	WikitextBuilder.prototype.buildSteps = function(steps) {
		var wikitext = this.SECTION_HEADING_TEMPLATE.replace('$name', steps.sectionName);
		wikitext += this.buildMethods(steps.methods);
		return wikitext;
	};
	
	WikitextBuilder.prototype.buildMethods = function(methods) {
		var wikitext = '';
		if (methods.length > 1) {
			for (var i = 0; i < methods.length; i++) {
				var method = methods[i];
				// Only add methods with a name and content
				if (method.name !== '' && method.lis.length > 0) {
					wikitext += this.buildMethod(method, true);
				}
			}
		} else {
			wikitext += this.buildMethod(methods[0], false);
		}
		return wikitext;
	};
	
	WikitextBuilder.prototype.buildMethod = function(method, withHeading) {
		var wikitext = '';
		if (method.lis.length) {
			if (withHeading) {
				wikitext = this.METHOD_HEADING_TEMPLATE.replace('$name', method.name);
			}
			wikitext += this.buildLIs(method.lis, this.STEP_LI_TYPE);
		}
		return wikitext + '\n';		
	};
	
	WikitextBuilder.prototype.buildLIs = function(lis, liType) {
		var wikitext = '';
		for (var i = 0; i < lis.length; i++) {
			var li = lis[i];
			if (li.txt.length  > 0) {
				wikitext += liType + ' ' + li.txt + '\n';
			}
			if (li.sublis && li.sublis.length > 0) {
				wikitext += this.buildSubLIs(li.sublis);
			}
		}
		return wikitext;
	};
	
	WikitextBuilder.prototype.buildSubLIs = function(sublis) {
		var wikitext = '';
		for (var i = 0; i < sublis.length; i++) {
			wikitext += this.SUB_LI_TYPE + ' ' + sublis[i] + '\n';
		}
		return wikitext;
	};
	
	WikitextBuilder.prototype.buildTips = function(tips) {
		return this.buildOtherSection(tips);
	};
	
	WikitextBuilder.prototype.buildWarnings = function(warnings) {
		return this.buildOtherSection(warnings);
	};
	
	WikitextBuilder.prototype.buildReferences = function(references) {
		return this.buildOtherSection(references);
	};
	
	WikitextBuilder.prototype.buildOtherSection = function(section) {
		var wikitext = '';
		if (section.lis.length) {
			wikitext = this.SECTION_HEADING_TEMPLATE.replace('$name', section.sectionName);
			wikitext += this.buildLIs(section.lis, this.BULLET_LI_TYPE) + '\n';
		}
		return wikitext;
	};
	
	
	var Section = function(name, id) {
	    //this.name = name;
	    //this.id = id;
	};
		
	var Method = function (methodName) {
		this.id;
	    this.name = methodName;
	    this.lis = [];
	    
	};
	
	Method.METHODS_METHOD_TYPE = 'methods';
	Method.PARTS_METHOD_TYPE = 'parts';
	Method.NEITHER_METHOD_TYPE = 'neither';
	Method.DEFAULT_METHOD_TYPE = Method.NEITHER_METHOD_TYPE;
	
	Method.prototype.addLI = function(li) {
	    this.lis.push(li);
		$(dispatcher).trigger('/Method/addLI', {lis: this.lis, method: this});
	};
	
	Method.prototype.getLI = function(pos) {
	    return this.lis[pos];
	};
	
	Method.prototype.editLI = function(pos, li) {
	    this.lis[pos] = li;
	    $(dispatcher).trigger('/Method/editLI', {lis: this.lis, method: this});
	};
	
	Method.prototype.removeLI = function(pos) {		
		this.lis.splice(pos, 1);
		$(dispatcher).trigger('/Method/removeLI', {lis: this.lis, method: this});
	};
	
	Method.prototype.moveLI = function(oldPos, newPos) {
	    this.lis.splice(newPos, 0, this.lis.splice(oldPos, 1)[0]);
	    $(dispatcher).trigger('/Method/moveLI', {lis: this.lis, method: this});
	};
	
	Method.prototype.setName = function(name) {
		this.name = name;
		 $(dispatcher).trigger('/Method/setName', {methodType: a.steps.methodType, method: this});
	};
	
	var StepsSection = function(name, id) {
	    this.id = id;
	    this.sectionName = name;
		this.methodType = Method.DEFAULT_METHOD_TYPE;
	    this.methods = [];
	};
	
	StepsSection.prototype = Section;
	
	StepsSection.prototype.addMethod = function(methodName) {
		var newMethod = new Method(methodName);
		// Give each method a unique id. 
		newMethod.id = 'whm_' + this.methods.length + "_" + (new Date().getTime());
	    this.methods.push(newMethod);
	    $(dispatcher).trigger('/Steps/addMethod', {methodType: this.methodType, method: newMethod});
	    return newMethod.id;
	};
	
	StepsSection.prototype.setMethodType = function(type) {
	    this.methodType = type;
	    $(dispatcher).trigger('/Steps/newMethodType', {methodType: this.methodType, methods: this.methods});    
	};
	
	StepsSection.prototype.getMethod = function(methodId) {
	    var method = null;
	    for (var i = 0; i < this.methods.length; i++) {
	        if (this.methods[i].id == methodId) {
	            method = this.methods[i];
	        }
	    }
	    return method;
	};
	
	StepsSection.prototype.removeMethod = function(methodId) {
		var methods = this.methods;
		var removedMethod = null;
		for (var i = 0; i < methods.length; i++) {
			if (methods[i].id == methodId) {
				removedMethod = methods[i];
				methods.splice(i, 1);
			}
		}
		$(dispatcher).trigger('/Steps/removeMethod', {methodType: this.methodType, method: removedMethod});
	};
	
	StepsSection.prototype.moveMethod = function(oldPos, newPos) {
	    this.methods.splice(newPos, 0, this.methods.splice(oldPos, 1)[0]);
	    $(dispatcher).trigger('/Steps/moveMethod', {methods: this.methods});
	};
		
	var OtherSection = function(name, id) {
	    this.lis = [];
	    this.sectionName = name;
	    this.id = id;
	};
	
	OtherSection.prototype = Section;
	
	OtherSection.prototype.addLI = function(li) {
	    this.lis.push(li);
		$(dispatcher).trigger('/OtherSection/addLI', {lis: this.lis, section: this});
	};
	
	OtherSection.prototype.getLI = function(pos) {
	    return this.lis[pos];
	};
	
	OtherSection.prototype.editLI = function(pos, li) {
	    this.lis[pos] = li;
	    $(dispatcher).trigger('/OtherSection/editLI', {lis: this.lis, section: this});
	};
	
	OtherSection.prototype.removeLI = function(position) {		
		this.lis.splice(position, 1);
		$(dispatcher).trigger('/OtherSection/removeLI', {lis: this.lis, section: this});
	};
	
	OtherSection.prototype.moveLI = function(oldPos, newPos) {
	    this.lis.splice(newPos, 0, this.lis.splice(oldPos, 1)[0]);
	    $(dispatcher).trigger('/OtherSection/moveLI', {lis: this.lis, section: this});
	};
		
	var ListItem = function(txt) {
	    this.txt = txt;
	    this.sublisType = '';
	    this.sublis = [];
	};
	
	var ErrorMsg = function(msg, id) {
		this.msg = msg;
		this.id = id;
	};
	
	var Article = function(title) {
		this.title = title;
		this.intro = '';
		
		this.steps = new StepsSection("Steps", "steps");
		this.tips = new OtherSection("Tips", "tips");
		this.warnings = new OtherSection("Warnings", "warnings");
		this.references = new OtherSection("References", "references");
		
	};
	
	Article.prototype.ERROR_TOO_SHORT = 'ac-error-too-short';
	Article.prototype.ERROR_NO_STEPS = 'ac-error-no-steps';
	Article.prototype.ERROR_MISSING_METHOD_NAMES = 'ac-error-missing-method-names';
	Article.prototype.MIN_WORDS = 50;
	
	Article.prototype.validate = function() {		
		var steps = this.steps;
		var hasSteps = false;
		var numSteps = 0;
		var missingMethodId = '';
		for (var i = 0; i < steps.methods.length; i++) {
			var method = steps.methods[i];
			if (method.lis.length > 0) {
				hasSteps = true;
				numSteps += method.lis.length;
				
				if (method.name.length == 0) {
					missingMethodId = method.id;
					break;
				}
			}  
		}
	
		if (steps.methodType != Method.NEITHER_METHOD_TYPE &&
			steps.methods.length > 1 && missingMethodId.length > 0) {
			return new ErrorMsg(this.ERROR_MISSING_METHOD_NAMES, missingMethodId);
		}
		
		if (!hasSteps || numSteps <= 1) {
			return new ErrorMsg(this.ERROR_NO_STEPS, this.steps.id);
		}
		
		var builder = new WikitextBuilder();	
		var wikitext = builder.buildArticle(this);
		if (wikitext.split(' ').length <  this.MIN_WORDS) {
			return new ErrorMsg(this.ERROR_TOO_SHORT, this.steps.id);
		}
		return true;
	};
		
	Article.prototype.setIntro = function(txt) {
		this.intro = txt;
		$(dispatcher).trigger('/Article/setIntro', {intro : this.intro});
	};
	
	/*
	 * Logic for updating the data model based on interaction with the UI
	 */
	function Controller() {
		
		// input event not supported in IE < 9
		var changeEvent = $.browser.msie && $.browser.version < 9 ?  
			'keydown' : 'input';
		       
       $(document).on(changeEvent, 'textarea.ac_new_li', function() {
    	   var button = $(this).siblings('.ac_add_li');
           if ($(this).val().length > 1) {
        	   	Util.glowButton(button);
           }
       });
       
       $(document).on('click', '#ac_add_references_link', function() {
    	  $('.add_references_section').hide();
    	  $('#references').show();
       });
		      	
       $(document).on('click', '.ac_question', function(e) {
			var msgKey = 'ac-question-neither';
			if ($(this).hasClass('methods')) {
				msgKey = 'ac-question-methods';
			} else if ($(this).hasClass('parts')) {
				msgKey = 'ac-question-parts';
			}
			var txt = mw.message(msgKey).text();
			var dialogHtml = $('#ac_abstract_alert_tmpl').html().replace('$txt', txt);		
			var config = view.getAbstractDialogConfig('ac_question_dialog',  mw.message(msgKey + '-title').text());
			$('#dialog-box').html(dialogHtml).dialog(config);	
		});
		
		$(document).on('click', '.ac_ok', function() {
			$('#dialog-box').dialog('close');
		});
		
		$(document).on('click', '#ac_publish', function(e) {
			savePendingEdits();		
			var result = a.validate();		
			if (result === true) {
				var builder = new WikitextBuilder();	
				var wikitext = builder.buildArticle(a);
				
				var postData = {t: mw.util.getParamValue('t'), wikitext: wikitext, ac_token: $('#ac_token').text(), overwrite: $("#overwrite").val()};
				$.post(mw.util.getUrl(), postData, function(data) {
					var result = $.parseJSON(data);
					if (result.error) {
						var dialogHtml = $('#ac_publish_error_tmpl').html()
							.replace('$txt', result.error).replace('$wikitext', wikitext);
						$('#dialog-box').html(dialogHtml).dialog({
				               modal: true,
				               width: 600,
				               closeOnEscape: false,
				               dialogClass: 'ac_no_close ac_publishing_error',
				               resizable: false,
				               title: 'Publishing Error',
				               position: 'center',
							   closeText: 'x'
						});
					} else {
						// If successful, redirect to published article
						$('body').trigger('trackCreate');
						window.location.replace(result.url);
					}
				});
			} else {
				displayErrorMessage(result);
			}
		});
		
		function displayErrorMessage(error) {
			var selector = '';
			var position = 'top';
			var offset = {'top': 20, 'left': 0};
			switch (error.msg) {
				case a.ERROR_NO_STEPS:
				case a.ERROR_TOO_SHORT:
					selector = '#' + error.id + ' .ac_li_adder';
					position = 'right';
					offset = {'top': 0, 'left': -20};
					break;
				case a.ERROR_MISSING_METHOD_NAMES:
					selector = '#' + error.id + ' .ac_edit_method_text';
					position = 'right';
					offset = {'top': 0, 'left': -20};
					break;
			}
			view.showErrorMessage(selector, error.msg, position, offset);
		}
		
		// Help the users along by saving any steps/tips/etc 
		// that they haven't saved.
		function savePendingEdits() {
			addIntroLI($('#introduction .ac_new_li'));

			$('.ac_method .ac_new_li').each(function(i, textarea) {
				addMethodLI(textarea, false);
			});
			
			$('.ac_other_section .ac_new_li').each(function(i, textarea) {
				addOtherSectionLI(textarea);
			});	
		}	
		
		$(document).on('click', '.ac_close_button,#ac_ordered_methods_close', function(e) {
			$('#dialog-box').dialog('close');
		});
		
		$(document).on('click', 'input[name="method_format"]', function(e) {
			a.steps.setMethodType($(this).val());
		});
			
		$(document).on('click', '.ac_method .ac_add_li', function(e) {
			var textarea = $(this).siblings('.ac_new_li');
			addMethodLI(textarea, true);
		});
		
		$(document).on('click', '.ac_remove_method', function(e) {
			var method = getMethod(this);
			var methodId = getMethodId(method);
			var articleMethod = a.steps.getMethod(methodId);
			var methodTypeLabel = a.steps.methodType == Method.METHODS_METHOD_TYPE ? 'method' : 'part';
			var methodName = view.getMethodName(articleMethod.name);
			var txt = mw.message('ac-confirm-remove-method', methodId, methodTypeLabel, methodName).text();
			var dialogHtml = $('#ac_abstract_confirm_tmpl').html().replace('$txt', txt);
			
			var config = view.getAbstractDialogConfig('ac_no_close ac_remove_method_dialog', 'Remove Method');
			$('#dialog-box').html(dialogHtml).dialog(config);
			
		});
		
		$(document).on('click', '#ac_discard', function(e) {	
			var txt = mw.message('ac-confirm-discard-article').text();
			var dialogHtml = $('#ac_abstract_confirm_tmpl').html().replace('$txt', txt);
			var config = view.getAbstractDialogConfig('ac_no_close ac_discard_dialog', 'Discard Article');
			$('#dialog-box').html(dialogHtml).dialog(config);
		});
		
		$(document).on('click', '.ac_advanced_link', function(e) {
			e.preventDefault();
			var txt = mw.message('ac-confirm-advanced-editor').text();
			var dialogHtml = $('#ac_abstract_confirm_tmpl').html().replace('$txt', txt);	
			var config = view.getAbstractDialogConfig('ac_no_close ac_advanced_editor_dialog', 'Switch to Advanced Editor');
			$('#dialog-box').html(dialogHtml).dialog(config);	
		});
		
		$(document).on('click', '.ac_advanced_editor_dialog .ac_yes', function(e) {
			savePendingEdits();
			var builder = new WikitextBuilder();	
			var wikitext = builder.buildArticle(a);			
			window.location.href = $('.ac_advanced_link').attr('href') + '&ac_wikitext=' + mw.util.rawurlencode(wikitext);
		});
		
		$(document).on('click', '.ac_discard_dialog .ac_yes', function(e) {
			window.location.replace(wgServer + '/Special:CreatePage');
		});
		
		$(document).on('click', '.ac_remove_method_dialog .ac_yes', function(e) {
			a.steps.removeMethod($('.ac_method_id').text());
			$('#dialog-box').dialog('close');
		});		
		
		$(document).on('click', '.ac_remove_method_no,.ac_no', function(e) {
			$('#dialog-box').dialog('close');
		});
			
		
		$(document).on('click', '.ac_reorder_method', function(e) {
			view.showMethodReorderingForm();
		});
		
		$(document).on('sortupdate', '.ac_ordered_methods', function(event, ui) {
			var li = ui.item;
			var newPos = li.index();
			var oldPos = $(this).attr('data-previndex');
	        $(this).removeAttr('data-previndex');
			a.steps.moveMethod(oldPos, newPos);
		});
				
		$(document).on('click', '.ac_add_new_method', function(e) {
			a.steps.addMethod('');
		});
		
		$(document).on('click', '.ac_method_title', function(e) {
			var methodElement = getMethod(this);
			var methodId = getMethodId(methodElement);
			var articleMethod = a.steps.getMethod(methodId);
			view.showMethodEditForm(methodElement, articleMethod);
		});
		
		$(document).on('click', '.ac_edit_method', function(e) {
			setMethodName($(this).siblings('.ac_edit_method_text'), true);	
		});
		
		$(document).on('keyup', '.ac_edit_method_text', function(e) {
			setMethodName($(this), false);
		});
		
		function setMethodName(textarea, hideForm) {
			var method = getMethod(textarea);
			var methodId = getMethodId(method);
			var articleMethod = a.steps.getMethod(methodId);
			var txt = Util.strip(Util.stripMethodPart($(textarea).val()));
			if (txt.length) {
				articleMethod.setName(txt);
				if (hideForm) {
					view.hideMethodEditForm(method, articleMethod);
				}
			}
		}
	
		function addMethodLI(textarea, showFormattingWarning) {	
			var placeHolder = Util.wrapPlaceholder($(textarea).attr('placeholder'));
			if ($(textarea).val().trim() != placeHolder) {
				var li = createLIFromTextarea(textarea, showFormattingWarning);
				if (li !== null) {
					var method = getMethod(textarea);
					var methodId = getMethodId(method);			
					
					$(textarea).val(placeHolder);					
					var articleMethod = a.steps.getMethod(methodId);
					articleMethod.addLI(li);
				}
			}
		}
	
		function createLIFromTextarea(textarea, showFormattingWarning) {
			var html = "<div>" + $(textarea).val() + "</div>";
			var li = new ListItem('');
			
			// Show a warning if there is text after the bullets to let
			// users know they entered incorrect formatting
			if (showFormattingWarning && $(textarea).val().match(/<\/ul>\s*[^\s]+/m)) {
				view.showFormattingWarningDialog();
			}			
			
			$('li', html).each(function(i, val) {	
				li.sublis.push(Util.strip($(val).text()));	
			});
						
			li.txt = Util.strip($('ul', html).remove().end().text());
			
			if (showFormattingWarning && li.txt.length == 0) {
				var method = getMethod(textarea);
				var methodId = getMethodId(method);	
				
				var selector = $('#ac_edit_form', method).length == 0 ? '#' + methodId + ' .ac_li_adder' : '#ac_edit_form';
				view.showErrorMessage(selector, 'ac-error-only-bullets', 'right', {'top': 0, 'left': -20});
				return null;
			}
			
			return li.txt.length > 0 || li.sublis.length > 0 ? li : null;
		}

		$(document).on('click', '#introduction .ac_txt', function(e) {	
			if (e.target === this) {
				view.showIntroEditForm();
			}
		});

		$(document).on('click', '.ac_method .ac_txt', function(e) {		
			if (e.target === this) {
				var liPos = $(this).closest('.ac_lis > li').index();
				var methodId = getMethodId(getMethod(this));
				var li = a.steps.getMethod(methodId).getLI(liPos);
				view.showEditForm($(this).closest('.ac_lis > li'), li);
			}
		});

		$(document).on('click', '.ac_other_section .ac_txt', function(e) {
			if (e.target === this) {
				var liPos = $(this).closest('li').index();
				var sectionId = getSectionId(this);
				var li = a[sectionId].getLI(liPos);
				view.showEditForm($(this).closest('li'), li);
			}
		});
		
		$(document).on('click', '#ac_cancel', function(e) {
			view.removeEditForm();
		});
		
		$(document).on('click', '.ac_method #ac_delete', function(e) {
			var txt = mw.message('ac-confirm-delete-step').text();
			var dialogHtml = $('#ac_abstract_confirm_tmpl').html().replace('$txt', txt);
			var config = view.getAbstractDialogConfig('ac_no_close ac_delete_step_dialog', 'Delete Step');
			$('#dialog-box').html(dialogHtml).dialog(config).data('domElement', this);		
		});
		
		$(document).on('click', '.ac_delete_step_dialog .ac_yes', function() {
			var domElement = $('#dialog-box').dialog().data('domElement');
			var liPos = $(domElement).closest('li').index();
			var methodId = getMethodId(getMethod(domElement));
			a.steps.getMethod(methodId).removeLI(liPos);
			$('#dialog-box').dialog('close');
		});
		
		$(document).on('click', '.ac_other_section #ac_delete', function(e) {
			var txt = mw.message('ac-confirm-delete-bullet').text();
			var dialogHtml = $('#ac_abstract_confirm_tmpl').html().replace('$txt', txt);
			var config = view.getAbstractDialogConfig('ac_no_close ac_delete_bullet_dialog', 'Delete Bullet');
			$('#dialog-box').html(dialogHtml).dialog(config).data('domElement', this);		
		});
		
		$(document).on('click', '.ac_delete_bullet_dialog .ac_yes', function() {
			var domElement = $('#dialog-box').dialog().data('domElement');
			var liPos = $(domElement).closest('li').index();
			var sectionId = getSectionId(domElement);
			a[sectionId].removeLI(liPos);
			$('#dialog-box').dialog('close');
		});
		
		$(document).on('click', '.ac_method #ac_save', function(e) {
			var liPos = $(this).closest('li').index();			
			var methodId = getMethodId(getMethod(this));			
			var textarea = $(this).siblings('#ac_edit_text');		
			var li = createLIFromTextarea(textarea, true);
			if (li !== null) {
				a.steps.getMethod(methodId).editLI(liPos, li);
			}
		});
		
		$(document).on('click', '.ac_other_section #ac_save', function(e) {
			var liPos = $(this).closest('li').index();
			var textarea = $(this).siblings('#ac_edit_text');
			var sectionId = getSectionId(this);
			var li = createLIFromTextarea(textarea, false);
			if (li !== null) {
				a[sectionId].editLI(liPos, li); 
			}
		});
			
		$(document).on('click', '#ac_cancel', function(e) {
			view.removeEditForm();
		});
				
		$(document).on('sortupdate', '.ac_method .ac_lis', function(event, ui){
			var li = ui.item;
			var newPos = li.index();
			var oldPos = $(this).attr('data-previndex');
	        $(this).removeAttr('data-previndex');
	        
	        var method = getMethod(this);
			var methodId = getMethodId(method);
			var articleMethod = a.steps.getMethod(methodId);
			
			var editorOpen = $('#ac_edit_form').length > 0;
			var editorPos = $('#ac_edit_form').closest('li').index();
			var editTxt = '';
			if ($('#ac_edit_text').length) {
				editTxt = $('#ac_edit_text').val();
			}
			
			articleMethod.moveLI(oldPos, newPos);

			if (editorOpen) {
				view.showEditForm($('li', this).eq(editorPos), articleMethod.getLI(editorPos));
				$('#ac_edit_text').val(editTxt);
			}
		});
		
		// Init sortable for tips and warnings sections
		$( ".ac_other_section .ac_lis" ).sortable({
			 start: function(e, ui) {
				// creates a temporary attribute on the element with the old index
				$(this).attr('data-previndex', ui.item.index());
			},
			distance: 10
		});	

		$(document).on('click', '.ac_other_section .ac_add_li', function(e) {
			var textarea = $(this).siblings('.ac_new_li');
			addOtherSectionLI(textarea);
		});
		
		$(document).on('sortupdate', '.ac_other_section .ac_lis', function(event, ui){
			var li = ui.item;
			var newPos = li.index();
	        var oldPos = $(this).attr('data-previndex');
	        $(this).removeAttr('data-previndex');
			var sectionId = getSectionId(li);
			a[sectionId].moveLI(oldPos, newPos);
		});	
		
		$('#intro_text').blur(function() {
			addIntroLI($(this));
		});
		
		function addIntroLI(textarea) {
			var txt = Util.strip(textarea.val());
			if (txt.length > 0) {
				a.setIntro(txt);
			}
		}

		
		function getMethod(methodSubElement) {
			return $(methodSubElement).closest('li.ac_method');
		}
		
		function getMethodId(methodElement) {
			return $(methodElement).attr('id');
		}
	
		function addOtherSectionLI(textarea) {
			var li = createLIFromTextarea(textarea, false);
			
			if (li !== null) {
			$(textarea).val('').focus();
				var sectionId = getSectionId(textarea);
				a[sectionId].addLI(li);
			}
		}
		
		function getSectionId(sectionSubElement) {
			return $(sectionSubElement).closest('div.section').attr('id');
		}
		
		
	};
	
	/*
	 * UI-specific logic to update the display based on model change events and controller manipulation
	 */
	function View() {

		var LIPrototype = "<li><span class='ac_txt'>$txt</span></li>";
		var MethodLIPrototype = "<li><div class='step_num'>$step</div><span class='ac_txt'>$txt</span></li>";
		
		var otherSectionRedrawListeners = ['addLI', 'moveLI', 'removeLI', 'editLI'];
		for (var i = 0; i < otherSectionRedrawListeners.length; i++) {
			$(dispatcher).bind('/OtherSection/' + otherSectionRedrawListeners[i], function(e, data) {
				var ul = $('#' + data.section.id + ' .ac_lis');
				redrawLIs(data.lis, ul, LIPrototype);
			});		
		}
		
		var methodRedrawListeners = ['addLI', 'moveLI', 'removeLI', 'editLI'];
		for (var i = 0; i < methodRedrawListeners.length; i++) {
			$(dispatcher).bind('/Method/' + methodRedrawListeners[i], function(e, data) {
				var ul = $('.ac_lis', getMethodElement(data.method.id));
				redrawLIs(data.lis, ul, MethodLIPrototype);
				updateStepNum(getMethodElement(data.method.id));
			});	
		}
		
		$(dispatcher).bind('/Steps/newMethodType', function(e, data) {
			redrawAllMethodInfo(data.methodType, data.methods);
		});
		
		$(dispatcher).bind('/Steps/moveMethod', function(e, data) {
			reorderMethods(data.methods);
		});
		
		$(dispatcher).bind('/Steps/addMethod', function(e, data) {			
			addMethod(data.methodType, data.method);
			updateMethodSelector();
		});
		
		$(dispatcher).bind('/Steps/removeMethod', function(e, data) {			
			removeMethod(data.methodType, data.method);
			updateMethodSelector();
		});
		
		$(dispatcher).bind('/Method/setName', function(e, data) {
			redrawMethodInfo(data.methodType, data.method);
		});
		
		function updateStepNum(domMethod) {
			var numSteps = $('.ac_lis > li', domMethod).size();
			$('.ac_li_adder .step_num', domMethod).html(numSteps + 1);
			var editorMarginLeft = numSteps + 1 < 10 ? '40px' : '57px';
			$('#steps .ac_li_adder div.mce-tinymce').css('margin-left', editorMarginLeft);
			var editorStepWidth = numSteps + 1 < 10 ? '29px' : '46px';
			$('#steps .ac_li_adder .step_num').css('width', editorStepWidth);
		}
		
		function redrawIntro(txt) {
			if (txt.length)
			var introLI = Util.linkify(LIPrototype.replace('$txt', txt));
			$('#introduction .ac_lis').children().remove();
			$('#introduction .ac_lis').append(introLI).show();	
		};
		
		function reorderMethods(methods) {
			for ( var i = 0; i < methods.length; i++) {
				$('.ac_methods').children('#' + methods[i].id).find('textarea.ac_new_li').tinymce().remove();
				$('.ac_methods').children('#' + methods[i].id).detach().appendTo('.ac_methods');
				$('.ac_methods').children('#' + methods[i].id).find('textarea.ac_new_li').tinymce(tinymceConfig);
			}
			redrawAllMethodInfo(a.steps.methodType, methods);
		}
		
		function updateMethodSelector() {
			if (a.steps.methods.length <= 1) {
				$('input[name=method_format][value=neither]').prop('disabled', false);
				$('#ac_new_method').hide();
			} else {
				$('input[name=method_format][value=neither]').prop('disabled', true);
				$('#ac_new_method').show();
			}
		}
		
		function addMethod(methodType, articleMethod) {
			var methodHtml = $('#ac_method_tmpl').html().replace('$id', articleMethod.id);
			$('.ac_methods').append(methodHtml);
			
			// init tinymce for method
			$('#' + articleMethod.id + ' textarea.ac_new_li').tinymce(tinymceConfig);
			
			// init sortable for method
			$('#' + articleMethod.id + ' .ac_lis').sortable({
				 start: function(e, ui) {
				        // creates a temporary attribute on the element with the old index
					 	$(this).attr('data-previndex', ui.item.index());		
				 },
				 distance: 30
			});	
			
			redrawAllMethodInfo(methodType, a.steps.methods);
		}
		
		function removeMethod(methodType, articleMethod) {
			// Remove tinymce instance
			$('#' + articleMethod.id + ' textarea.ac_new_li').tinymce().remove();
			
			// Destroy sortable for method
			$('#' + articleMethod.id + ' .ac_lis').sortable('destroy');
			
			// Remove DOM element
			$('#' + articleMethod.id).remove();
		
			redrawAllMethodInfo(methodType, a.steps.methods);
		}
		
		function redrawAllMethodInfo(methodType, methods) {
			// Update the 'ADD METHOD' button
			var buttonTxt = '';
			var methodSelectorTxt = '';
			if (a.steps.methodType == Method.METHODS_METHOD_TYPE) {
				buttonTxt = mw.message('ac-add-method-button-txt').text();
				methodSelectorTxt = mw.message('ac-method-selector-txt').text();
			} else {
				buttonTxt = mw.message('ac-add-part-button-txt').text();
				methodSelectorTxt = mw.message('ac-part-selector-txt').text();
			}
				
			$('a.ac_add_new_method').html(buttonTxt);
			$('#ac_method_selector_text').html(methodSelectorTxt);
						
			for (var i = 0; i < methods.length; i++) {
				redrawMethodInfo(methodType, methods[i]);
			}
		}
		
		var that = this;
		function redrawMethodInfo(methodType, articleMethod) {
			var methodElement = getMethodElement(articleMethod.id);		
			switch (methodType) {
				case Method.METHODS_METHOD_TYPE:
				case Method.PARTS_METHOD_TYPE:
					that.showMethodInfo(methodElement, methodType, articleMethod);
					break;
				case Method.NEITHER_METHOD_TYPE:
					that.hideMethodInfo(methodElement);
					break;
			}
		}
			
		this.setMethodPrefix = function(methodElement, methodType, articleMethod) {
			// Set the right method prefix
			var methodLabel = methodType == Method.METHODS_METHOD_TYPE ? 'Method' : 'Part';
			methodLabel += ' ' + ($(methodElement).index() + 1) 
				+ ' of ' + $(methodElement).closest('ul.ac_methods').children().size();
			if (articleMethod.name.length > 0) {
				methodLabel += ':';
			}
			$('.ac_method_prefix', methodElement).html(methodLabel);
			
		};
		
		this.setMethodName =  function(methodElement, articleMethod) {
			// Set the method name
			$('.ac_method_name', methodElement).html(articleMethod.name);	
		};
			
		this.hideMethodInfo = function(methodElement) {
			$('.ac_method_info', methodElement).hide();
			$('#ac_add_new_method').hide();
		};
		
		
		function redrawLIs(articleLIs, ul, prototype) {
			view.removeEditForm();
			var newLIs = [];
			for (var i = 0; i < articleLIs.length; i++) {
				newLIs.push(getLIHtml(prototype, i + 1, articleLIs[i]));
			}
			$(ul).empty().append(newLIs.join(''));
			$(ul).children().size() > 0 ? $(ul).show() : $(ul).hide();
		}
		
		function getLIHtml(prototype, stepNum, li) {
			var liHtml = prototype.replace('$step', stepNum).replace('$txt', Util.linkify(li.txt));
			var sublisHtml = getSubLIsHtml(li);	
	
			liHtml = $('.ac_txt', liHtml).append(sublisHtml).parent().wrap('<p/>').parent().html().replace(/&nbsp;/g, ' ');	
			
			return liHtml;
		}
		
	
		function getMethodElement(methodId) {
			return $('#' + methodId);
		}
		
		function getSubLIsHtml(li) {
			var html = '';
			var newlis = [];
			for (var i = 0; i < li.sublis.length; i++) {
				var liHtml = LIPrototype.replace('$txt', li.sublis[i]);
				newlis.push(liHtml);
			}
			
			if (newlis.length) {
				html = $('<ul/>');
				$(html).append(newlis.join(''));
			}
			return $(html).wrap('<p/>').parent().html();
		}
		
		/*
		 * Setup placeholder support. Taken from
		 * http://www.cssnewbie.com/cross-browser-support-for-html5-placeholder-text-in-forms/#.U0Qqla1dV52
		 */
		jQuery(function() {
			jQuery.support.placeholder = false;
			test = document.createElement('input');
			if('placeholder' in test) jQuery.support.placeholder = true;
		});
		
		$(function() {
			if(!$.support.placeholder) { 
				var active = document.activeElement;
				$(':text').focus(function () {
					if ($(this).attr('placeholder') != '' && $(this).val() == $(this).attr('placeholder')) {
						$(this).val('').removeClass('hasPlaceholder');
					}
				}).blur(function () {
					if ($(this).attr('placeholder') != '' && ($(this).val() == '' || $(this).val() == $(this).attr('placeholder'))) {
						$(this).val($(this).attr('placeholder')).addClass('hasPlaceholder');
					}
				});
				$(':text').blur();
				$(active).focus();
				$('form').submit(function () {
					$(this).find('.hasPlaceholder').each(function() { $(this).val(''); });
				});
			}
		});
	}			
	
	View.prototype.showMethodInfo = function(methodElement, methodType, articleMethod) {
		
		this.setMethodPrefix(methodElement, methodType, articleMethod);
		
		this.setMethodName(methodElement, articleMethod);
		if (articleMethod.name.length == 0) {
			$('.ac_method_editor', methodElement).show();
		}
		
		if (a.steps.methods.length > 1) {
			$('.ac_method_tools', methodElement).show();
		} else {
			$('.ac_method_tools', methodElement).hide();
		}
		
		$('.ac_method_info').show();
		$('#ac_add_new_method').show();
	};
	
	View.prototype.showMethodEditForm = function(methodElement, articleMethod) {
		$('.ac_edit_method_text', methodElement).val(articleMethod.name);
		$('.ac_method_editor', methodElement).show();	
	};
	
	View.prototype.hideMethodEditForm = function(methodElement, articleMethod) {
		$('.ac_method_editor', methodElement).hide();	
	};	
	
	View.prototype.createEditText = function (li) {
		var html = li.txt;
		if (li.sublis.length) {
			var ul = $('<ul/>');
			$.each(li.sublis, function(i, subli) {					
				$(ul).append('<li>' + subli + '</li>');
			});
			html += '\n' + $(ul).wrap('<p/>').parent().html();
		}
		return html;
	};
	
	View.prototype.showEditForm  = function(liElement, li) {
		view.removeEditForm();
		var editHtml = $('#ac_edit_view_tmpl').html();
		
		// Don't put another form on if one currently exists	
		$(liElement).append(editHtml);
		
		// Use a rich text editor for methods and steps
		if ($(liElement).closest('.ac_method').length) {
			$('textarea#ac_edit_text').tinymce(tinymceConfig);
			$('textarea#ac_edit_text').tinymce().setContent(this.createEditText(li));
			$('textarea#ac_edit_text').tinymce().focus();
			//tinymce.execCommand('mceFocus', false, 'ac_edit_text');
		} else {
			$('#ac_edit_text').val(this.createEditText(li)).focus();
		}
		
	};
	
	View.prototype.removeEditForm  = function() {	
		$('#ac_edit_form').hide();
		if ($('textarea#ac_edit_text').tinymce()) {
			$('textarea#ac_edit_text').tinymce().remove();

		}
		$('#ac_edit_form').remove();
	};
	
	View.prototype.showIntroEditForm  = function() {	
		$('#introduction .ac_new_li').val(a.intro);
		$('#introduction .ac_li_adder').show();
	};
	
	View.prototype.getMethodName = function(name) {
		var methodTypeLabel = a.steps.methodType == Method.METHODS_METHOD_TYPE ? 'Method' : 'Part';
		return name.length > 0 ? name : "Unnamed " + methodTypeLabel ;
	};
	
	View.prototype.showMethodReorderingForm = function() {
		var html = 'Hold and drag to reorder:\n';
		var methodTypeLabel = a.steps.methodType == Method.METHODS_METHOD_TYPE ? 'Methods' : 'Parts';
		var ol = $('<ol/>').attr('class', 'ui-draggable ac_ordered_methods');
		if (a.steps.methods.length) {
			$.each(a.steps.methods, function(i, method) {					
				$(ol).append('<li method_id="'+ method.id + '">' + view.getMethodName(method.name) + '</li>');
			});
			html += $(ol).wrap('<p/>').parent().html();
		}
		
		html += "<a id='ac_ordered_methods_close' class='button primary'>Done</a>";
		
		$('#dialog-box').html(html).dialog({
           modal: true,
           resizable: false,
           dialogClass: 'method_reordering_dialog',
           title: 'Reorder ' + methodTypeLabel,
           closeOnEscape: true,
           position: 'center',
		   closeText: 'x'
        });
		
		$( ".ac_ordered_methods" ).sortable({
			start: function(e, ui) {
			    // creates a temporary attribute on the element with the old index
				$(this).attr('data-previndex', ui.item.index());
			},
			distance: 30
		});
	};
	
	View.prototype.getAbstractDialogConfig = function(dialogClasses, title) {
		return {
            modal: true,
            width: 450,
            closeOnEscape: true,
            dialogClass: 'ac_abstract_confirm_dialog ' + dialogClasses,
            resizable: false,
            title: title,
            position:'center',
			closeText: 'x'
		};
	};
	
	View.prototype.showFormattingWarningDialog = function() {
		var txt = mw.message('ac-formatting-warning-txt').text();
		var dialogHtml = $('#ac_abstract_alert_tmpl').html().replace('$txt', txt);		
		var config = view.getAbstractDialogConfig('ac_formatting_warning_dialog',  
				mw.message('ac-formatting-warning-title').text());
		$('#dialog-box').html(dialogHtml).dialog(config);
	};
	
	View.prototype.showErrorMessage = function(selector, msg, position, offset) {
		mw.libs.guiders.hideAll();
		mw.libs.guiders.createGuider({
			id: "ac_error_guider" + new Date().getTime(),
			description: mw.message(msg).text(),
			attachTo: selector,	
			overlay: false,
            closeOnClickOutside: true,
			position: position,
			offset: offset,
			title: mw.message('ac-validation-error-title').text(),
			flipToKeepOnScreen: false,
			width: 300,
			buttons: [ {
                action: 'okay',
                name: 'Okay',
                onclick: function () {
                    mw.libs.guiders.hideAll();
                }
			} ]
		}).show();
	};
	
	// Initialize controller and view
	var controller = new Controller();
	var view = new View();
	
	// Create the data model with a blank method
	var a = new Article("");
	a.steps.addMethod('');
}

$(document).ready(function() {
	WH.ArticleCreator();
});
