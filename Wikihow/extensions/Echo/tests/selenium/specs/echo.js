'use strict';

var assert = require( 'assert' ),
	EchoPage = require( '../pageobjects/echo.page' ),
	UserLoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Echo', function () {

	it( 'alerts and notices are visible after logging in', function () {

		UserLoginPage.login( browser.options.username, browser.options.password );

		assert( EchoPage.alerts.isExisting() );
		assert( EchoPage.notices.isExisting() );

	} );

	it( 'flyout for alert appears when clicked', function () {

		UserLoginPage.login( browser.options.username, browser.options.password );
		EchoPage.alerts.click();
		EchoPage.flyout.waitForVisible();

		assert( EchoPage.flyout.isExisting() );

	} );

} );
