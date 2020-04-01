'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class EchoPage extends Page {

	get alerts() { return browser.element( '#pt-notifications-alert' ); }
	get notices() { return browser.element( '#pt-notifications-notice' ); }
	get flyout() { return browser.element( '.oo-ui-popupWidget-popup' ); }

}
module.exports = new EchoPage();
