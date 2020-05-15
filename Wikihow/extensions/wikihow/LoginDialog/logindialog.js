/* global OO, $, mw, WH */

function LoginDialog( config ) {
	// Configuration
	config = config || {};
	config.size = 'large';
	this.mode = null;

	// Inheritance
	LoginDialog.super.call( this, config );

	this.$element.attr( 'id', 'loginDialog' );

	// Properties
	this.errorLabel = new OO.ui.LabelWidget( {
		id: 'loginDialog-errorLabel',
		invisibleLabel: true
	} );
	this.usernameInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'userlogin-yourname-ph' ).text(),
		name: 'wpName',
		id: 'wpName1'
	} );
	this.passwordInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'userlogin-yourpassword-ph' ).text(),
		name: 'wpPassword',
		id: 'wpPassword1',
		type: 'password'
	} );
	this.newPasswordInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'userlogin-yourpassword-ph' ).text(),
		name: 'new-password',
		type: 'password'
	} );
	this.confirmPasswordInput = new OO.ui.TextInputWidget( {
		placeholder: mw.message( 'retypenew' ).text(),
		name: 'retype-password',
		type: 'password'
	} );
	this.rememberInput = new OO.ui.CheckboxInputWidget( { 'selected': true } );
	this.loginButton = new OO.ui.ButtonInputWidget( {
		id: 'loginDialog-loginButton',
		label: mw.message( 'pt-login-button' ).text(),
		flags: [ 'primary', 'progressive' ],
		type: 'submit'
	} );
	this.continueLoginButton = new OO.ui.ButtonInputWidget( {
		id: 'loginDialog-continueLoginButton',
		label: mw.message( 'pt-login-continue-button' ).text(),
		flags: [ 'primary', 'progressive' ],
		type: 'submit'
	} );
	this.forgotPasswordButton = new OO.ui.ButtonWidget( {
		label: mw.message( 'userlogin-resetpassword-link' ).text(),
		framed: false,
		href: '/Special:PasswordReset'
	} );

	this.facebookButton = new OO.ui.ButtonWidget( {
		id: 'loginDialog-facebookButton',
		label: mw.message( 'ulb-btn-fb' ).text()
	} );

	this.googleButton = new OO.ui.ButtonWidget( {
		id: 'loginDialog-googleButton',
		label: mw.message( 'ulb-btn-gplus' ).text()
	} );

	this.civicButton = new OO.ui.ButtonWidget( {
		id: 'loginDialog-civicButton',
		label: mw.message( 'ulb-btn-civic' ).text()
	} );

	this.loginFieldset = new OO.ui.FieldsetLayout( {
		id: 'loginDialog-loginFieldset',
		items: [
			new OO.ui.FieldLayout( this.usernameInput, { align: 'top' } ),
			new OO.ui.FieldLayout( this.passwordInput, { align: 'top' } ),
			new OO.ui.FieldLayout( this.rememberInput, {
				label: mw.message( 'rememberme' ).text(),
				align: 'inline',
			} ),
			new OO.ui.FieldLayout( this.loginButton, { align: 'top' } ),
			new OO.ui.FieldLayout( this.forgotPasswordButton, { align: 'top' } )
		]
	} );
	this.resetPasswordFieldset = new OO.ui.FieldsetLayout( {
		id: 'loginDialog-resetPasswordFieldset',
		items: [
			new OO.ui.FieldLayout( this.newPasswordInput, { align: 'top' } ),
			new OO.ui.FieldLayout( this.confirmPasswordInput, { align: 'top' } ),
			new OO.ui.FieldLayout( this.continueLoginButton, { align: 'top' } ),
		]
	} );

	this.socialButtons = new OO.ui.StackLayout( {
		id: 'loginDialog-socialButtons',
		items: [
			this.facebookButton,
			this.googleButton,
			this.civicButton
		],
		continuous: true,
		expanded: false
	} );
	this.socialLayout = new OO.ui.PanelLayout( {
		id: 'loginDialog-social',
		$content: this.socialButtons.$element
			.add( $( '<div>' ).attr( 'id', 'loginDialog-or' ).text( mw.message( 'loginor' ).text() ) ),
		expanded: false
	} );
	this.formLayout = new OO.ui.FormLayout( {
		id: 'loginDialog-form',
		$content: this.errorLabel.$element
			.add( this.loginFieldset.$element )
			.add( this.resetPasswordFieldset.$element ),
		expanded: false
	} );

	this.panelLayout = new OO.ui.HorizontalLayout( {
		items: [ this.socialLayout, this.formLayout ]
	} );

	this.loginButton.connect( this, { click: 'onLoginClick' } );
	this.continueLoginButton.connect( this, { click: 'onContinueLoginClick' } );
}

/* Setup */

OO.inheritClass( LoginDialog, OO.ui.ProcessDialog );

/* Static Properties */

LoginDialog.static.name = 'loginDialog';
LoginDialog.static.title = mw.message( 'log_in_via' ).text();
LoginDialog.static.actions = [
	{
		action: 'create',
		label: mw.message( 'nologinlink' ).text(),
		flags: [ 'primary', 'progressive' ],
		href: '/Special:UserLogin?type=signup'
	},
	{
		action: 'cancel',
		label: mw.message( 'cancel' ).text(),
		flags: [ 'safe', 'destructive' ],
		href: '#'
	}
];

/* Methods */

LoginDialog.prototype.setMode = function( mode ) {
	if ( this.mode !== mode ) {
		if ( mode === 'login' ) {
			this.loginFieldset.$element.show();
			this.resetPasswordFieldset.$element.hide();
		} else if ( mode === 'reset-password' ) {
			this.loginFieldset.$element.hide();
			this.resetPasswordFieldset.$element.show();
		} else {
			return;
		}
		this.mode = mode;
	}
};

LoginDialog.prototype.initialize = function () {
	LoginDialog.super.prototype.initialize.apply( this, arguments );
	// Init
	this.$body.append( this.panelLayout.$element );

	var buttons = {
		fb: '#loginDialog-facebookButton',
		gplus: '#loginDialog-googleButton'
	};
	if ( mw.config.get( 'wgUserLanguage' ) === 'en' ) {
		buttons.civic = '#loginDialog-civicButton';
	}
	WH.social.setupLoginButtons( buttons, mw.config.get( 'wgPageName' ) );
};

LoginDialog.prototype.onLoginClick = function ( event ) {
	this.executeAction( 'login' );
};

LoginDialog.prototype.onContinueLoginClick = function ( event ) {
	this.executeAction( 'continue-login' );
};

LoginDialog.prototype.getSetupProcess = function ( data ) {
	return LoginDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.setMode( 'login' );
		}, this );
};

LoginDialog.prototype.getReadyProcess = function ( data ) {
	return LoginDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.usernameInput.focus();
		}, this );
};

LoginDialog.prototype.getTeardownProcess = function ( data ) {
	return LoginDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			// Remove #edit from URL
			if ( history.pushState ) {
				history.pushState( null, null, '#' );
			} else {
				location.hash = '#';
			}
		}, this );
};

LoginDialog.prototype.getActionProcess = function ( action ) {
	var dialog = this;

	function disableForm( disable ) {
		dialog.usernameInput.setDisabled( disable );
		dialog.passwordInput.setDisabled( disable );
		dialog.rememberInput.setDisabled( disable );
	}

	function resetErrorLabel() {
		dialog.errorLabel.setInvisibleLabel( true );
	}

	// Handle action
	if ( action === 'create' ) {
		return new OO.ui.Process( function () {
			disableForm( true );
			window.location = '/Special:UserLogin?type=signup';
			// Show pending until page reloads
			return $.Deferred().promise();
		} );
	} else if (
		( action === 'login' || action === 'continue-login' ) &&
		dialog.usernameInput.getValue() !== '' &&
		dialog.passwordInput.getValue() !== ''
	) {
		return new OO.ui.Process( function () {
			disableForm( true );
			var api = new mw.Api();
			var returnUrl = window.location.href.replace( /#.*$/, '' );
			return api.getToken( 'login' ).then(
				function ( token ) {
					// Action:token success
					var data = {
						action: 'clientlogin',
						loginmessageformat: 'html',
						logintoken: token,
						loginreturnurl: returnUrl,
						username: dialog.usernameInput.getValue(),
						password: dialog.passwordInput.getValue(),
						rememberMe: dialog.rememberInput.isSelected() ? 1 : ''
					};
					if ( action === 'continue-login' && dialog.mode === 'reset-password' ) {
						data.loginpreservestate = true;
						data.password = dialog.newPasswordInput.getValue();
						data.retype = dialog.confirmPasswordInput.getValue();
						data.logincontinue = true;
					}
					return api.post( data );
				},
				function ( data ) {
					// Action:token failure
					// console.log( '!TOKEN', data );
					disableForm( false );
					return $.Deferred().reject( new OO.ui.Error( 'Server error.' ) );
				}
			).then(
				function ( data ) {
					// Action:clientlogin success
					if ( data.clientlogin && data.clientlogin.status === 'PASS' ) {
						// PASS
						window.location = returnUrl;
						// Show pending until page reloads
						return $.Deferred().promise();
					} else {
						// FAILURE, UI, REDIRECT or RESTART
						// console.log( '!PASS', data );
						dialog.errorLabel.setLabel( $( '<span>' + data.clientlogin.message + '</span>' ) );
						dialog.errorLabel.setInvisibleLabel( false );
						if (
							data.clientlogin &&
							data.clientlogin.status === 'UI' &&
							data.clientlogin.requests &&
							data.clientlogin.requests[0] &&
							data.clientlogin.requests[0].fields &&
							data.clientlogin.requests[0].fields.password &&
							data.clientlogin.requests[0].fields.retype
						) {
							dialog.setMode( 'reset-password' );
						} else {
							dialog.setMode( 'login' );
						}
						disableForm( false );
						dialog.updateSize();
					}
				},
				function ( data ) {
					// Action:clientlogin failure
					// console.log( '!200', data );
					disableForm( false );
					return $.Deferred().reject( new OO.ui.Error( 'Server error.' ) );
				}
			);
		} );
	} else if ( action === 'cancel' ) {
		return new OO.ui.Process( function () {
			resetErrorLabel();
			return dialog.close( { action: action } );
		} );
	}
	return LoginDialog.super.prototype.getActionProcess.call( this, action );
};

/* Initialization */

OO.ui.getWindowManager().addWindows( {
	login: new LoginDialog()
} );
