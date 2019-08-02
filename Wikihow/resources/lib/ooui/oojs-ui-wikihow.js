/*!
 * OOUI v0.30.2
 * https://www.mediawiki.org/wiki/OOUI
 *
 * Copyright 2011–2019 OOUI Team and other contributors.
 * Released under the MIT license
 * http://oojs.mit-license.org
 *
 * Date: 2019-07-11T23:18:11Z
 */
( function ( OO ) {

'use strict';

/**
 * @class
 * @extends OO.ui.Theme
 *
 * @constructor
 */
OO.ui.WikihowTheme = function OoUiApexTheme() {
	// Parent constructor
	OO.ui.WikihowTheme.parent.call( this );
};

/* Setup */

OO.inheritClass( OO.ui.WikihowTheme, OO.ui.Theme );

/* Methods */

/**
 * @inheritdoc
 */
OO.ui.WikihowTheme.prototype.getDialogTransitionDuration = function () {
	return 250;
};

/* Instantiation */

OO.ui.theme = new OO.ui.WikihowTheme();

}( OO ) );

//# sourceMappingURL=oojs-ui-wikihow.js.map.json