/* JavaScript for Drafts extension */

function Draft() {

	/* Private Members */

	// Reference to object's self
	var self = this,
		// Configuration settings
		configuration = null,
		// State of the draft as it pertains to asynchronous saving
		state = 'unchanged',
		// Timer handle for auto-saving
		timer = null,
		// Reference to edit form draft is being edited with
		form = null,
		// NULL for timeout
		timeoutID = null;

	/* Functions */

	/**
	 * Sets the state of the draft
	 * @param {String} newState
	 */
	this.setState = function(newState) {
		if (state !== newState) {
			// Stores state information
			state = newState;
			// Updates UI elements
			switch ( state ) {
				case 'unchanged':
					form.wpDraftSave.disabled = true;
					form.wpDraftSave.className += ' disabled ';
					form.wpDraftSave.value = mw.message( 'drafts-save-save' ).text();
					break;
				case 'changed':
					form.wpDraftSave.disabled = false;
					form.wpDraftSave.className = form.wpDraftSave.className.replace(/ disabled/g,'');
					form.wpDraftSave.value = mw.message( 'drafts-save-save' ).text();
					break;
				case 'saved':
					form.wpDraftSave.disabled = true;
					form.wpDraftSave.className += ' disabled ';
					form.wpDraftSave.value = mw.message( 'drafts-save-saved' ).text();
					break;
				case 'saving':
					form.wpDraftSave.disabled = true;
					form.wpDraftSave.className += ' disabled ';
					form.wpDraftSave.value = mw.message( 'drafts-save-saving' ).text();
					break;
				case 'error':
					form.wpDraftSave.disabled = true;
					form.wpDraftSave.className += ' disabled ';
					form.wpDraftSave.value = mw.message( 'drafts-save-error' ).text();
					break;
				default: break;
			}
		}
	};

	/**
	 * Gets the state of the draft
	 */
	this.getState = function() {
		return state;
	};

	/**
	 * Sends draft data to server to be saved
	 */
	this.save = function(e) {
		e.preventDefault();
		// Checks if a save is already taking place
		if (state === 'saving') {
			// Exits function immediately
			return;
		}
		// Sets state to saving
		self.setState( 'saving' );
		var params = {
			action: 'savedrafts',
			drafttoken: form.wpDraftToken.value,
			token: form.wpEditToken.value,
			id: form.wpDraftID.value,
			title: form.wpDraftTitle.value,
			section: form.wpSection.value,
			starttime: form.wpStarttime.value,
			edittime: form.wpEdittime.value,
			scrolltop: form.wpTextbox1.scrollTop,
			text: form.wpTextbox1.value,
			summary: form.wpSummary.value
		};

		if ( form.wpMinoredit !== undefined && form.wpMinoredit.checked ) {
			params.minoredit = 1;
		}

		// Performs asynchronous save on server
		var api = new mw.Api();
		api.post(params).done( self.respond ).fail( self.respond );

		// Re-allow request if it is not done in 10 seconds
		self.timeoutID = window.setTimeout(
			"wgDraft.setState( 'changed' )", 10000
		);
		// Ensure timer is cleared in case we saved manually before it expired
		clearTimeout( timer );
		timer = null;
	};

	this.save_guided = function(e) {
		e.preventDefault();
		// Checks if a save is already taking place
		if (state === 'saving') {
			// Exits function immediately
			return;
		}
		// Sets state to saving
		self.setState( 'saving' );

		checkMinLength = false;
		WH.Editor.checkForm();
		checkMinLength = true;

		// setu p text
		var parameters = "";
		for (var i=0; i < document.editform.elements.length; i++) {
			var element = document.editform.elements[i];
			if (parameters != "") {
				parameters += "&";
			}

			parameters += element.name + "=" + encodeURIComponent(element.value);
		}
		try {
			this.request = new XMLHttpRequest();
		} catch (error) {
			try {
				this.request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (error) {
				return false;
			}
		}
		var url = "//" + window.location.hostname + "/Special:BuildWikihowArticle";
		this.request.open('POST', url, false);
		this.request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		this.request.send(parameters);
		this.text = this.request.responseText;

		var params = {
			action: 'savedrafts',
			drafttoken: form.wpDraftToken.value,
			token: form.wpEditToken.value,
			id: form.wpDraftID.value,
			title: form.wpDraftTitle.value,
			section: form.wpSection.value,
			starttime: form.wpStarttime.value,
			edittime: form.wpEdittime.value,
			scrolltop: 0,
			text: this.text,
			summary: form.wpSummary.value
		};
		if(form.wpMinoredit.check) {
			params.minoredit = 1;
		}
		// Performs asynchronous save on server
		var api = new mw.Api();
		api.post(params).done( self.respond ).fail( self.respond );

    	// Ensure timer is cleared in case we saved manually before it expired
		clearTimeout( timer );
		timer = null;
	}
	/**
	 * Updates the user interface to represent being out of sync with the server
	 */
	this.change = function() {
		// Sets state to changed
		self.setState( 'changed' );
		// Checks if timer is pending and if we want to wait for user input
		if ( !configuration.autoSaveBasedOnInput ) {
			if ( timer ) {
				return;
			}
			if ( configuration.autoSaveWait && configuration.autoSaveWait > 0 ) {
				// Sets timer to save automatically after a period of time
				timer = setTimeout(function() {
					var e = jQuery.Event();
					if (typeof isGuided !== 'undefined' && isGuided) {
						self.save_guided(e);
					} else {
						self.save(e);
					}
				}, configuration.autoSaveWait * 1000);
			}
			return;
		}

		if ( timer ) {
			// Clears pending timer
			clearTimeout( timer );
		}
		// Checks if auto-save wait time was set, and that it's greater than 0
		if ( configuration.autoSaveWait && configuration.autoSaveWait > 0 ) {
			// Sets timer to save automatically after a period of time
			timer = setTimeout(function() {
				var e = jQuery.Event();
				if (typeof isGuided !== 'undefined' && isGuided) {
					self.save_guided(e);
				} else {
					self.save(e);
				}
			}, configuration.autoSaveWait * 1000);
		}
	};

	/**
	 * Initializes the user interface
	 */
	this.initialize = function() {
		// Cache edit form reference
		form = document.editform;
		// Check to see that the form and controls exist
		if ( form && form.wpDraftSave ) {
			// Handle manual draft saving through clicking the save draft button
			$('#wpDraftSave').on('click', function(e) {
				if (typeof isGuided !== 'undefined' && isGuided) {
					self.save_guided(e);
				} else {
					self.save(e);
				}
			});
			
			// Handle keeping track of state by watching for changes to fields
			if($('#wpTextbox1').length) {
				$('#wpTextbox1').bind('keypress keyup keydown paste cut', function() {
					self.change();
				});
			}
			else {
				// GUIDED HANDLERS                                                                                                                                                            
				//XXCHANGEDXX - addHandler is so 2005... [sc]
				$("#summary, #ingredients, #steps, #tips, #warnings, #thingsyoullneed, #related, #sources").bind('keypress keyup keydown paste cut', function() {
					self.change();
				});

			}

			$('#wpSummary').bind('keypress', 'keyup', 'keydown', 'paste', 'cut', self.change);

			if ($('#wpMinoredit').length) {
				$('#wpMinoredit').bind('change', self.change);
			}
			// Gets configured specific values
			configuration = {
				autoSaveWait: mw.config.get( 'wgDraftAutoSaveWait' ),
				autoSaveTimeout: mw.config.get( 'wgDraftAutoSaveTimeout' ),
				autoSaveBasedOnInput: mw.config.get( 'wgDraftAutoSaveInputBased' )
			};
		}
	};

	/**
	 * Responds to the server after a save request has been handled
	 * @param {Object} data
	 */
	this.respond = function( data ) {
		// Checks that an error did not occur
		if ( data.savedrafts && data.savedrafts.id ) {
			// Changes state to saved
			self.setState( 'saved' );
			// Gets id of newly inserted draft (or updates if it already exists)
			// and stores it in a hidden form field
			form.wpDraftID.value = data.savedrafts.id;
		} else {
			// Changes state to error
			self.setState( 'error' );
		}
		if(self.timeoutID) {
			clearTimeout(self.timeoutID);
			self.timeoutID = null;
		}
	};
}

window.wgDraft = new Draft();
window.wgDraft.initialize();
