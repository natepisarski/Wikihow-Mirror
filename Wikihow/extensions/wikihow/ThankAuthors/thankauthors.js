/* global OO, $, mw, WH */
// TODO: Get content from: index.php?title=Special:ThankAuthors&target=<?= $wgTitle->getPrefixedURL() ?>

function AuthorThanksDialog( config ) {
	// Configuration
	config = config || {};
	config.size = 'medium';

	// Inheritance
	AuthorThanksDialog.super.call( this, config );

	// Properties
	this.$message = $( '<span></span>' );
	this.messageInput = new OO.ui.MultilineTextInputWidget();
	this.messageField = new OO.ui.FieldLayout(
		this.messageInput,
		{
			label: this.$message,
			align: 'top'
		}
	);
	this.fieldsetLayout = new OO.ui.FieldsetLayout( {
		items: [ this.messageField ]
	} );
	this.panel = new OO.ui.PanelLayout( {
		$content: this.fieldsetLayout.$element,
		padded: true,
		expanded: false
	} );
}

/* Setup */

OO.inheritClass( AuthorThanksDialog, OO.ui.ProcessDialog );

/* Static Properties */

AuthorThanksDialog.static.name = 'authorThanksDialog';
AuthorThanksDialog.static.title = mw.message( 'send-kudos' ).text();
AuthorThanksDialog.static.actions = [
	{
		action: 'submit',
		label: mw.message( 'send-kudos-submit' ).text(),
		flags: [ 'primary', 'progressive' ]
	},
	{
		label: mw.message( 'send-kudos-cancel' ).text(),
		flags: 'safe'
	}
];

/* Methods */

AuthorThanksDialog.prototype.initialize = function () {
	AuthorThanksDialog.super.prototype.initialize.apply( this, arguments );
	var title = new mw.Title( mw.config.get( 'wgTitle' ) );
	var talkTitle = title.getTalkPage();
	if ( mw.user.isAnon() ) {
		this.$message.html( mw.message(
			'enjoyed-reading-article-anon',
			title.getPrefixedDb(),
			title.getPrefixedText()
		).parse() );
	} else {
		this.$message.html( mw.message(
			'enjoyed-reading-article',
			title.getPrefixedDb(),
			title.getPrefixedText(),
			talkTitle.getPrefixedDb()
		).parse() );
	}
	this.messageInput.connect( this, { change: 'onMessageInputChange' } );
	this.$body.append( this.panel.$element );
};

AuthorThanksDialog.prototype.getSetupProcess = function ( data ) {
	return AuthorThanksDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.messageInput.setValue( '' );
			this.actions.setAbilities( { 'submit': false } );
		}, this );
};

AuthorThanksDialog.prototype.getActionProcess = function ( action ) {
	var dialog = this;
	if ( action ) {
		return new OO.ui.Process( function () {
			dialog.close( { action: action } );
		} );
	}
	return AuthorThanksDialog.super.prototype.getActionProcess.call( this, action );
};

AuthorThanksDialog.prototype.onMessageInputChange = function ( value ) {
	this.actions.setAbilities( { submit: !!value.length } );
};

AuthorThanksDialog.prototype.onSubmit = function() {
	var title = new mw.Title( mw.config.get( 'wgTitle' ) );
	var form = $('#thanks_form');
	var dialog = this;
	var deferred = $.Deferred();
	$.post( '/Special:ThankAuthors', {
		details: this.messageInput.getValue(),
		target: title.getPrefixedDb()
	} )
		.done( function ( data ) {
			deferred.resolve(
				dialog.close( { action: 'submit' } ).then( function () {
					var thanks = mw.message( 'thank-you-kudos',
						mw.message( 'howto', title.getPrefixedText() ).plain()
					).plain();
					OO.ui.getWindowManager().openWindow( 'authorThanksConfirmDialog', {
						size: 'medium',
						message: $( '<span>' + thanks + '</span>' ),
						actions: [ {
							action: 'done',
							label: mw.message( 'send-kudos-done' ).text(),
							flags: 'primary'
						} ]
					} );
				} )
			);
		} )
		.fail( function ( data ) {
			deferred.reject( new OO.ui.Error(
				mw.message( 'send-kudos-error' ).text(),
				{ recoverable: false }
			) );
		} );
	return deferred.promise();
};

AuthorThanksDialog.prototype.getActionProcess = function ( action ) {
	return AuthorThanksDialog.super.prototype.getActionProcess.call( this, action )
		.next( function () {
			if ( action === 'submit' ) {
				return this.onSubmit();
			}
			return AuthorThanksDialog.super.prototype.getActionProcess.call( this, action );
		}, this );
};

/* Initialization */

OO.ui.getWindowManager().addWindows( {
	authorThanksDialog: new AuthorThanksDialog(),
	authorThanksConfirmDialog: new OO.ui.MessageDialog()
} );
