/* global OO, $, mw, WH */

function EditDialog( config ) {
	// Configuration
	config = config || {};
	config.size = 'large';

	// Inheritance
	EditDialog.super.call( this, config );

	// Properties
	this.wikitext = null;
	this.contentInput = new OO.ui.MultilineTextInputWidget( {
		id: 'editDialog-contentInput',
		placeholder: 'Loading...'
	} );
	this.summaryInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'summary' ).text(),
		id: 'editDialog-summaryInput',
		maxLength: 500
	} );
	this.$warnings = $( '<p>' ).append( $( mw.message( 'anoneditwarning' ).parse() ).contents() );
	this.warningButton = new OO.ui.PopupButtonWidget( {
		title: mw.message( 'warnings' ).text(),
		icon: 'alert',
		popup: {
			padded: true,
			width: 400,
			align: 'backwards',
			$content: this.$warnings
		}
	} );
	this.infoButton = new OO.ui.PopupButtonWidget( {
		title: mw.message( 'termsofuse' ).text(),
		icon: 'logoCC',
		popup: {
			padded: true,
			width: 400,
			align: 'backwards',
			$content: $(
				'<div>' +
					mw.message( 'copyrightwarning2', mw.message( 'copyrightpage' ).text() )
						.parse() +
				'</div>'
			)
		}
	} );
	this.previewButton = new OO.ui.ButtonWidget( {
		label: mw.message( 'showpreview' ).text()
	} );
	this.diffButton = new OO.ui.ButtonWidget( {
		label: mw.message( 'showdiff' ).text()
	} );
	this.actionsFieldset = new OO.ui.HorizontalLayout( {
		items: [
			new OO.ui.FieldLayout( this.previewButton ),
			new OO.ui.FieldLayout( this.diffButton ),
			new OO.ui.FieldLayout( this.warningButton, { classes: [ 'editDialog-warning' ] } ),
			new OO.ui.FieldLayout( this.infoButton )
		]
	} );
	this.toolbar = new OO.ui.Toolbar(
		EditDialog.static.toolFactory,
		EditDialog.static.toolGroupFactory,
		{ actions: true }
	);
	this.toolbar.$content = this.contentInput.$input;
	this.toolbar.protected = false;
	this.toolbar.$actions.append( this.actionsFieldset.$element );
	this.editPanel = new OO.ui.PanelLayout( {
		$content: $( this.toolbar.$element )
			.add( this.contentInput.$element )
			.add( this.summaryInput.$element ),
		expanded: true,
		padded: false,
		id: [ 'editDialog-editPanel' ]
	} );
	this.previewPanel = new OO.ui.PanelLayout( {
		expanded: true,
		padded: false,
		id: [ 'editDialog-previewPanel' ]
	} );
	this.diffPanel = new OO.ui.PanelLayout( {
		expanded: false,
		padded: true,
		id: [ 'editDialog-diffPanel' ]
	} );
	this.captchaInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'fancycaptcha-imgcaptcha-ph' ).text(),
		id: 'editDialog-captchaInput'
	} );
	this.captchaReloadButton = new OO.ui.ButtonWidget( {
		label: mw.message( 'fancycaptcha-reload-text' ).text(),
		icon: 'reload',
		framed: false,
		classes: [ 'editDialog-captchaReloadButton' ]
	} );
	this.$captchaTitle = $( '<h2>' )
		.html( mw.message( 'fancycaptcha-captcha' ).parse() )
		.addClass( 'editDialog-captchaTitle' );
	this.$captchaDetails = $( '<p>' )
		.html( mw.message( 'fancycaptcha-addurl' ).parse() )
		.addClass( 'editDialog-captchaDetails' );
	this.$captchaImage = $( '<img>' )
		.addClass( 'editDialog-captchaImage' );
	this.$captchaChallenge = $( '<div>' )
		.append( this.$captchaImage, this.captchaReloadButton.$element )
		.addClass( 'editDialog-captchaChallenge' );
	this.captchaPanel = new OO.ui.PanelLayout( {
		$content: this.$captchaTitle
			.add( this.$captchaDetails )
			.add( this.$captchaChallenge )
			.add( this.captchaInput.$element ),
		expanded: false,
		padded: true,
		id: [ 'editDialog-captchaPanel' ]
	} );
	this.stack = new OO.ui.StackLayout( {
		items: [
			this.editPanel,
			this.previewPanel,
			this.diffPanel,
			this.captchaPanel
		]
	} );

	// Events
	this.previewButton.connect( this, { click: function () {
		this.executeAction( 'preview' );
	} } );
	this.diffButton.connect( this, { click: function () {
		this.executeAction( 'diff' );
	} } );
	this.contentInput.connect( this, { change: function () {
		var changed = this.wikitext && this.contentInput.getValue() !== this.wikitext.content;
		var protected = this.wikitext && this.wikitext.protection.protected;
		this.actions.setAbilities( {
			diff: changed,
			publish: changed && !protected,
			preview: !protected
		} );
		this.diffButton.setDisabled( !changed );
		this.previewButton.setDisabled( protected );
		this.contentInput.setDisabled( protected );
		this.summaryInput.setDisabled( protected );
		this.toolbar.protected = protected;
		this.toolbar.emit( 'updateState' );
	} } );
	this.captchaReloadButton.connect( this, { click: function () {
		var dialog = this;
		dialog.actions.setAbilities( { publish: false, back: false } );
		dialog.$captchaChallenge.addClass( 'oo-ui-pendingElement-pending' );
		getNewCaptcha().then( function ( captcha ) {
			dialog.captcha = captcha;
			dialog.$captchaImage.attr( { 'src': captcha.url } );
			dialog.actions.setAbilities( {
				publish: !this.wikitext.protection.protected,
				back: true
			} );
		} );
	} } );
	// Remove pending animation when the image loads
	this.$captchaImage.on( 'load', function () {
		this.$captchaChallenge.removeClass( 'oo-ui-pendingElement-pending' );
	}.bind( this ) );
}

/* Setup */

OO.inheritClass( EditDialog, OO.ui.ProcessDialog );

/* Static Properties */

EditDialog.static.name = 'editDialog';
EditDialog.static.title = mw.message( 'editing', mw.config.get( 'wgTitle' ) ).text();
EditDialog.static.actions = [
	{
		action: 'publish',
		modes: [ 'edit', 'captcha' ],
		label: mw.message( 'savechanges' ).text(),
		flags: [ 'primary', 'progressive' ]
	},
	{
		action: 'cancel',
		modes: [ 'loading', 'edit' ],
		label: mw.message( 'cancel' ).text(),
		flags: [ 'safe', 'destructive' ],
		href: '#'
	},
	{
		action: 'back',
		modes: [ 'preview', 'diff', 'captcha' ],
		label: 'Back',
		flags: [ 'safe' ]
	}
];
EditDialog.static.tools = [
	{
		type: 'bar',
		name: 'styling',
		include: [ 'bold', 'italic' ]
	},
	{
		type: 'bar',
		name: 'links',
		include: [ 'link', 'linkExternal' ]
	}
];

EditDialog.static.toolFactory = new OO.ui.ToolFactory();
EditDialog.static.toolGroupFactory = new OO.ui.ToolGroupFactory();

/* Methods */

EditDialog.prototype.initialize = function () {
	EditDialog.super.prototype.initialize.apply( this, arguments );
	// Init
	this.$body.append( this.stack.$element );
	this.toolbar.setup( EditDialog.static.tools );
	this.toolbar.initialize();
	this.toolbar.emit( 'updateState' );
};

EditDialog.prototype.getBodyHeight = function () {
	var item = this.stack.getCurrentItem();
	if ( item === this.editPanel || item === this.previewPanel ) {
		return Math.min( window.innerHeight, this.$body.outerWidth() );
	} else {
		return item.$element.outerHeight();
	}
};

EditDialog.prototype.getSetupProcess = function ( data ) {
	var dialog = this;
	return EditDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			dialog.captcha = null;
			dialog.wikitext = null;
			dialog.contentInput.setValue( '' );
			dialog.summaryInput.setValue( '' );
			dialog.captchaInput.setValue( '' );
			dialog.$captchaImage.attr( 'src', '' );
			dialog.stack.setItem( dialog.editPanel );
			dialog.actions.setMode( 'loading' );
			dialog.executeAction( 'load' );
		}, this );
};

EditDialog.prototype.getTeardownProcess = function ( data ) {
	var dialog = this;
	return EditDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			// Remove fragment from URL
			if ( history.pushState ) {
				history.pushState( null, null, '#' );
			} else {
				location.hash = '#';
			}
		}, this );
};

EditDialog.prototype.getActionProcess = function ( action ) {
	var dialog = this;
	return EditDialog.super.prototype.getActionProcess.call( this, action )
		.next( function () {
			// Handle action
			if ( action === 'load' ) {
				return getWikitext()
					.then( function ( wikitext ) {
						dialog.wikitext = wikitext;
						dialog.contentInput.setValue( wikitext.content );
						dialog.actions.setMode( 'edit' );
						if ( wikitext.protection.protected ) {
							dialog.$warnings
								.empty()
								.append( wikitext.protection.messages.join( ' ' ) );
						}
						setTimeout( function () {
							dialog.warningButton.getPopup().toggle( true );
						}, 1000 );
					} );
			} else if ( action === 'publish' ) {
				return savePage( {
					content: dialog.contentInput.getValue(),
					summary: dialog.summaryInput.getValue(),
					captcha: dialog.captcha && {
						id: dialog.captcha.id,
						word: dialog.captchaInput.getValue()
					},
					basetimestamp: dialog.wikitext.basetimestamp,
					starttimestamp: dialog.wikitext.starttimestamp 
				} )
					.then( function ( data ) {
						if ( data.edit && data.edit.result === 'Success' ) {
							// Success
							return dialog.close( { action: action } ).closed.promise()
								.then( function () {
									location.reload( true );
								} );
						} else if ( data.edit && data.edit.result === 'Failure' && data.edit.captcha ) {
							// Captcha required
							dialog.captcha = data.edit.captcha;
							dialog.stack.setItem( dialog.captchaPanel );
							dialog.actions.setMode( 'captcha' );
							dialog.$captchaChallenge.addClass( 'oo-ui-pendingElement-pending' );
							dialog.$captchaImage.attr( 'src', data.edit.captcha.url );
						} else {
							// Failure
							return new OO.ui.Error(
								mw.message( 'editdialog-error-publish' ).text(),
								{ recoverable: false }
							);
						}
					} );
			} else if ( action === 'preview' ) {
				return getPreview( dialog.contentInput.getValue() )
					.then( function ( $content ) {
						dialog.previewPanel.$element
							.empty()
							.append(
								$( '<div>' )
									.append( mw.message( 'previewnote' ).parse() )
									.addClass( 'previewnote' )
							)
							.append( $content );
						if ( WH.video ) {
							// Make inline videos load their thumbnails on scroll
							dialog.previewPanel.$element.find( 'video.m-video' ).each( function () {
								WH.video.add( this );
								WH.shared.addScrollLoadItemByElement( this );
							} );
							// Fix summary videos, which aren't auto-loading images on scroll
							dialog.previewPanel.$element.find( '#summary_video_poster' ).each( function () {
								$( this ).attr( 'src', $( this ).attr( 'data-src' ) );
							} );
						}
						dialog.stack.setItem( dialog.previewPanel );
						dialog.actions.setMode( 'preview' );
					} );
			} else if ( action === 'diff' ) {
				return getDiff( dialog.contentInput.getValue() )
					.then( function ( $content ) {
						dialog.diffPanel.$element.empty().append( $content );
						dialog.stack.setItem( dialog.diffPanel );
						dialog.actions.setMode( 'diff' );
					} );
			} else if ( action === 'back' ) {
				dialog.captcha = null;
				dialog.$captchaImage.attr( 'src', '' );
				dialog.stack.setItem( dialog.editPanel );
				dialog.actions.setMode( 'edit' );
			} else if ( action === 'cancel' ) {
				return dialog.close( { action: action } ).closed.promise();
			}
			return EditDialog.super.prototype.getActionProcess.call( this, action );
		}, this );
};

function BoldTool() {
	BoldTool.parent.apply( this, arguments );
}
OO.inheritClass( BoldTool, OO.ui.Tool );
BoldTool.static.name = 'bold';
BoldTool.static.icon = 'bold';
BoldTool.static.title = mw.message( 'bold_tip' ).text();
BoldTool.prototype.onSelect = function () {
	this.toolbar.$content.textSelection(
		'encapsulateSelection',
		{ pre: '\'\'\'', peri: mw.message( 'bold_sample' ).text(), post: '\'\'\'' }
	);
	this.setActive( false );
};
BoldTool.prototype.onUpdateState = function() {
	this.setDisabled( this.toolbar.protected );
};
EditDialog.static.toolFactory.register( BoldTool );

function ItalicTool() {
	ItalicTool.parent.apply( this, arguments );
}
OO.inheritClass( ItalicTool, OO.ui.Tool );
ItalicTool.static.name = 'italic';
ItalicTool.static.icon = 'italic';
ItalicTool.static.title = mw.message( 'italic_tip' ).text();
ItalicTool.prototype.onSelect = function () {
	this.toolbar.$content.textSelection(
		'encapsulateSelection',
		{ pre: '\'\'', peri: mw.message( 'italic_sample' ).text(), post: '\'\'' }
	);
	this.setActive( false );
};
ItalicTool.prototype.onUpdateState = function() {
	this.setDisabled( this.toolbar.protected );
};
EditDialog.static.toolFactory.register( ItalicTool );

function LinkTool() {
	LinkTool.parent.apply( this, arguments );
}
OO.inheritClass( LinkTool, OO.ui.Tool );
LinkTool.static.name = 'link';
LinkTool.static.icon = 'link';
LinkTool.static.title = mw.message( 'link_tip' ).text();
LinkTool.prototype.onSelect = function () {
	this.toolbar.$content.textSelection(
		'encapsulateSelection',
		{ pre: '[[', peri: mw.message( 'link_sample' ).text(), post: ']]' }
	);
	this.setActive( false );
};
LinkTool.prototype.onUpdateState = function() {
	this.setDisabled( this.toolbar.protected );
};
EditDialog.static.toolFactory.register( LinkTool );

function LinkExternalTool() {
	LinkExternalTool.parent.apply( this, arguments );
}
OO.inheritClass( LinkExternalTool, OO.ui.Tool );
LinkExternalTool.static.name = 'linkExternal';
LinkExternalTool.static.icon = 'linkExternal';
LinkExternalTool.static.title = mw.message( 'extlink_tip' ).text();
LinkExternalTool.prototype.onSelect = function () {
	this.toolbar.$content.textSelection(
		'encapsulateSelection',
		{ pre: '[', peri: mw.message( 'extlink_sample' ).text(), post: ']' }
	);
	this.setActive( false );
};
LinkExternalTool.prototype.onUpdateState = function() {
	this.setDisabled( this.toolbar.protected );
};
EditDialog.static.toolFactory.register( LinkExternalTool );

/* Initialization */

OO.ui.getWindowManager().addWindows( {
	edit: new EditDialog(),
	editConfirmation: new OO.ui.MessageDialog()
} );

/* Helper Functions */

/**
 * Get the wikitext of the latest revision for the current article.
 *
 * @return {jQuery.Promise} Promise to provide an object with content, basetimestamp and
 *     starttimestamp properties after loading is complete
 */
function getWikitext() {
	var api = new mw.Api();
	var pageId = mw.config.get( 'wgArticleId' );
	return api.get( {
		action: 'query',
		prop: 'revisions|info',
		curtimestamp: 1,
		pageids: pageId,
		rvslots: '*',
		rvprop: 'content|timestamp',
		inprop: 'protection',
		intestactions: 'edit',
		intestactionsdetail: 'full'
	} )
		.then( function ( data ) {
			var result = $.Deferred();
			if ( data.query && data.query.pages && pageId in data.query.pages ) {
				var page = data.query.pages[pageId];
				var protection = { protected: false, messages: [] };
				var messages = [];
				var i, len;
				// Handle protected pages
				if ( page.protection ) {
					// Collect messages, they'll be here if editing is protected
					if ( page.actions.edit ) {
						for ( i = 0, len = page.actions.edit.length; i < len; i++ ) {
							messages.push( page.actions.edit[i]['*'] );
						}
					}
					// Detect edit protection
					for ( i = 0, len = page.protection.length; i < len; i++ ) {
						if ( page.protection[i].type === 'edit' ) {
							protection.protected = true;
							protection.messages = messages;
							break;
						}
					}
				}
				if ( page.revisions && page.revisions.length ) {
					var revision = page.revisions[0];
					if ( revision.slots && revision.slots.main && revision.timestamp ) {
						// Bingo!
						result.resolve( {
							protection: protection,
							content: revision.slots.main['*'],
							basetimestamp: revision.timestamp,
							starttimestamp: data.curtimestamp
						} );
					} else {
						result.reject( 'Revision not found' );
					}
				} else {
					result.reject( 'Page not found' );
				}
			} else {
				result.reject( 'Invalid server response' );
			}
			return result.promise();
		} );
}

/**
 * Save wikitext to database for current article.
 *
 * @param {Object} inputs Data to save
 * @param {string} inputs.content Wikitext content to save
 * @param {string} inputs.summary Edit summary
 * @param {string} inputs.basetimestamp Base-timestamp from getWikitext call
 * @param {string} inputs.starttimestamp Start-timestamp from getWikitext call
 * @param {Object} [inputs.captcha] Captcha information
 * @param {string} [inputs.captcha.captchaid] ID of captcha shown to user
 * @param {string} [inputs.captcha.captchaword] Response from user
 * @return {jQuery.Promise} Promise to provide a response object when save is complete
 */
function savePage( inputs ) {
	var deferred = $.Deferred(),
		api = new mw.Api(),
		params = {
			action: 'edit',
			title: mw.config.get( 'wgTitle' ),
			text: inputs.content,
			summary: inputs.summary,
			basetimestamp: inputs.basetimestamp,
			starttimestamp: inputs.starttimestamp,
			format: 'json'
		};

	if ( inputs.captcha ) {
		params.captchaid = inputs.captcha.id;
		params.captchaword = inputs.captcha.word;
	}

	api.postWithToken( 'csrf', params )
		.done( function ( data ) {
			// Using trackEdit adds a wiki_sharednum_edits cookie, which bypasses the GoodRevision
			// system to ensure the user sees their edit. If you remove this tacking, you need to
			// also set a different cookie with the 'wiki_shared' prefix (ideally using the
			// mediawiki.cookie module which does that for you).
			WH.opWHTracker.trackEdit();

			deferred.resolve( data );
		} )
		.fail( function ( error ) {
			if ( error === 'editconflict' ) {
				deferred.reject( new OO.ui.Error(
					mw.message( 'editdialog-error-editconflict' ).text(),
					{ recoverable: false }
				) );
			}
			deferred.reject( new OO.ui.Error(
				mw.message( 'editdialog-error-publish' ).text(),
				{ recoverable: false }
			) );
		} );

	return deferred.promise();
}

/**
 * Get a new captcha for the user to solve.
 *
 * @return {Object} Captcha info, including mime, type, id and url properties
 */
function getNewCaptcha() {
	var deferred = $.Deferred(),
		api = new mw.Api();

	api.post( {
		action: 'fancycaptchareload'
	} )
		.then( function ( data ) {
			if ( data && data.fancycaptchareload ) {
				var id = data.fancycaptchareload.index;
				deferred.resolve( {
					mime: 'image/png',
					type: 'image',
					id: id,
					url: '/index.php?title=Special:Captcha/image&wpCaptchaId=' + id
				} );
			} else {
				deferred.reject();
			}
		}, deferred.reject );

	return deferred.promise();
}

/**
 * Get a preview of given wikitext for the current article.
 *
 * @param {string} wikitext Wikitext content to preview
 * @return {jQuery.Promise} Promise to provide a jQuery selection of the preview content when
 *     preview is done rendering
 */
function getPreview( wikitext ) {
	var deferred = $.Deferred();
	function error() {
		deferred.reject( new OO.ui.Error(
			mw.message( 'editdialog-error-preview' ).text(),
			{ recoverable: false }
		) );
	}
	$.ajax( {
		url: '/index.php',
		type: 'POST',
		data: {
			action: 'submit',
			live: 'true',
			wpPreview: 'true',
			title: mw.config.get( 'wgTitle' ),
			wpTextbox1: wikitext
		},
		success: function ( data ) {
			var $content = $( '<div>' + data + '</div>' );
			var $preview = $content.find('.mw-content-ltr,.mw-content-rtl').first();
			if ( $preview.length ) {
				deferred.resolve( $preview );
			} else {
				error();
			}
		},
		error: error
	} );
	return deferred.promise();
}

/**
 * Get a diff for given wikitext on the current article.
 *
 * @param {string} wikitext Wikitext content
 * @return {jQuery.Promise} Promise to provide a jQuery selection of the diff content when diff is
 *     done rendering
 */
function getDiff( wikitext ) {
	var deferred = $.Deferred();
	function error() {
		deferred.reject( new OO.ui.Error(
			mw.message( 'editdialog-error-diff' ).text(),
			{ recoverable: false }
		) );
	}
	var api = new mw.Api();
	api.post( {
		action: 'compare',
		prop: 'diff',
		fromtitle: mw.config.get( 'wgTitle' ),
		fromrev: mw.config.get( 'wgRevisionId' ),
		totext: wikitext
	} )
		.then( function ( data ) {
			if ( data && data.compare ) {
				deferred.resolve(
					$( '<table>' )
						.addClass( 'diff diff-contentalign-left' )
						.append( data.compare['*'] )
				);
			} else {
				error();
			}
		}, error );

	return deferred.promise();
}
