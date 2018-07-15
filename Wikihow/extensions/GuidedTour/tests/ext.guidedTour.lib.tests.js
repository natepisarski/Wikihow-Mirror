( function ( mw, $ ) {
	'use strict';

	var gt, originalVE, originalGetParam, cookieConfig, cookieName, cookieParams,
		// This form includes the id and next.
		VALID_STEP = {
			titlemsg: 'guidedtour-tour-test-callouts',
			descriptionmsg: 'guidedtour-tour-test-portal-description',
			attachTo: '#n-portal a',
			position: '3',
			buttons: [ {
				action: 'next'
			} ]
		};

	gt = mw.guidedTour;
	cookieConfig = gt.getCookieConfiguration();
	cookieName = cookieConfig.name;
	cookieParams = cookieConfig.parameters;

	// QUnit's "throws" only lets you check one at a time
	function assertThrowsTypeAndMessage( assert, block, errorConstructor, regexErrorMessage, message ) {
		var actualException;
		try {
			block();
		} catch ( exc ) {
			actualException = exc;
		}

		assert.strictEqual( actualException instanceof errorConstructor, true, message );
		assert.strictEqual( regexErrorMessage.test( actualException ), true, message );
	}

	QUnit.module( 'ext.guidedTour.lib', QUnit.newMwEnvironment( {
		setup: function () {
			originalGetParam = mw.util.getParamValue;
			originalVE = window.ve;
		},
		teardown: function () {
			window.ve = originalVE;
			mw.util.getParamValue = originalGetParam;
		}
	} ) );

	QUnit.test( 'makeTourId', 4, function ( assert ) {
		assert.strictEqual(
			gt.makeTourId( {
				name: 'test',
				step: 3
			} ),
			'gt-test-3',
			'Successful makeTourId call'
		);

		assert.strictEqual(
			gt.makeTourId( 'test' ),
			null,
			'String input returns null'
		);

		assert.strictEqual(
			gt.makeTourId( null ),
			null,
			'null input returns null'
		);

		assert.strictEqual(
			gt.makeTourId(),
			null,
			'Missing parameter returns null'
		);
	} );


	QUnit.test( 'parseTourId', 1, function ( assert ) {
		var tourId = 'gt-test-2', expectedTourInfo;
		expectedTourInfo = {
			name: 'test',
			step: '2'
		};
		assert.deepEqual(
			gt.parseTourId(	tourId ),
			expectedTourInfo,
			'Simple tourId'
		);
	} );

	QUnit.test( 'isPage', 2, function ( assert ) {
		var PAGE_NAME_TO_SKIP = 'TestPage',
			OTHER_PAGE_NAME = 'WrongPage';

		mw.config.set( 'wgPageName', PAGE_NAME_TO_SKIP );
		assert.strictEqual(
			gt.isPage( PAGE_NAME_TO_SKIP ),
			true,
			'Page matches'
		);

		mw.config.set( 'wgPageName', OTHER_PAGE_NAME );
		assert.strictEqual(
			gt.isPage( PAGE_NAME_TO_SKIP ),
			false,
			'Page does match'
		);
	} );

	QUnit.test( 'hasQuery', 7, function ( assert ) {
		var paramMap,
			PAGE_NAME_TO_SKIP = 'RightPage',
			OTHER_PAGE_NAME = 'OtherPage';

		mw.util.getParamValue =	function ( param ) {
			return paramMap[param];
		};

		paramMap = { action: 'edit', debug: 'true' };

		assert.strictEqual(
			gt.hasQuery( { action: 'edit' } ),
			true,
			'Query matches, page name is undefined'
		);
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, null ),
			true,
			'Query matches, page name is null'
		);

		mw.config.set( 'wgPageName', PAGE_NAME_TO_SKIP );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			true,
			'Query and page both match'
		);

		mw.config.set( 'wgPageName', OTHER_PAGE_NAME );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			false,
			'Query matches, but page does not');

		paramMap = { debug: 'true', somethingElse: 'medium' };

		assert.strictEqual(
			gt.hasQuery( { action: 'edit' } ),
			false,
			'Query does not match, page is undefined'
		);

		mw.config.set( 'wgPageName', PAGE_NAME_TO_SKIP );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			false,
			'Query does not match, although page does'
		);

		mw.config.set( 'wgPageName', OTHER_PAGE_NAME );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			false,
			'Neither query nor page match'
		);
	} );

	QUnit.test( 'getStep', 2, function ( assert ) {
		var step;
		mw.util.getParamValue = function () {
			return step;
		};

		step = 6;
		assert.strictEqual(
			gt.getStep(),
			step,
			'Step is returned correctly when present'
		);

		step = null;
		assert.strictEqual(
			gt.getStep(),
			step,
			'Step is returned as null when not present'
		);
	} );

	QUnit.test( 'setTourCookie', 5, function ( assert ) {
		var firstTourName = 'foo',
			secondTourName = 'bar',
			numberStep = 5,
			stringStep = '3',
			oldCookieValue = $.cookie( cookieName );

		function assertValidCookie( expectedName, expectedStep, message ) {
			var cookieValue = $.cookie( cookieName ),
				userState = gt.internal.parseUserState( cookieValue );

			// The stored step should be a number, even for
			// string input.
			expectedStep = Number( expectedStep );

			assert.strictEqual(
				userState.tours[expectedName].step,
				expectedStep,
				message
			);
		}

		function clearCookie() {
			$.cookie( cookieName, null, cookieParams );
		}

		gt.setTourCookie( firstTourName );
		assertValidCookie ( firstTourName, '1', 'Step defaults to 1' );
		clearCookie();

		gt.setTourCookie( firstTourName, numberStep );
		assertValidCookie ( firstTourName, String( numberStep ), 'setTourCookie accepts numeric step' );
		clearCookie();

		gt.setTourCookie( firstTourName, stringStep );
		assertValidCookie( firstTourName, stringStep, 'setTourCookie accepts string step' );

		gt.setTourCookie( secondTourName, numberStep );
		assertValidCookie( firstTourName, stringStep, 'First tour is still remembered after second is stored' );
		assertValidCookie( secondTourName, numberStep, 'Second tour is also remembered' );

		$.cookie( cookieName, oldCookieValue, cookieParams );
	} );

	QUnit.test( 'convertToNewCookieFormat', 6, function ( assert ) {
		var newCookie = $.toJSON( {
			version: 1,
			tours: {
				sometour: {
					step: 2
				}
			}
		} ), newCookieMultipleTours = $.toJSON( {
			version: 1,
			tours: {
				firsttour: {
					step: 4
				},
				secondtour: {
					step: 2
				},
				thirdtour: {
					step: 3,
					firstArticleId: 38333
				}
			}
		} );

		assert.strictEqual(
			gt.convertToNewCookieFormat( null ),
			null,
			'Returns null for null parameter'
		);

		assert.strictEqual(
			gt.convertToNewCookieFormat( 'gt-test-3' ),
			$.toJSON( {
				version: 1,
				tours: {
					test: {
						step: '3'
					}
				}
			} ),
			'Valid tour ID is upgraded correctly'
		);

		assert.strictEqual(
			gt.convertToNewCookieFormat( newCookie ),
			newCookie,
			'Valid JSON cookie with single tour is preserved intact'
		);

		assert.strictEqual(
			gt.convertToNewCookieFormat( newCookieMultipleTours ),
			newCookieMultipleTours,
			'Valid JSON cookie with multiple tours is preserved intact'
		);

		assert.strictEqual(
			gt.convertToNewCookieFormat( '{"bad": "cookie"}' ),
			null,
			'Valid JSON with missing version field returns null'
		);

		assert.strictEqual(
			gt.convertToNewCookieFormat( '<invalid: JSON>' ),
			null,
			'Invalid JSON returns null'
		);
	} );

	QUnit.test( 'shouldShow', 19, function ( assert ) {
		var visualEditorArgs, wikitextArgs, mockOpenVE;

		visualEditorArgs = {
			tourName: 'visualeditorintro',
			userState: {
				version: 1,
				tours: {}
			},
			pageName: 'Page',
			articleId: 123,
			condition: 'VisualEditor'
		};

		wikitextArgs = {
			tourName: 'wikitextintro',
			userState: {
				version: 1,
				tours: {}
			},
			pageName: 'Page',
			articleId: 123,
			condition: 'wikitext'
		};

		mockOpenVE = {
			instances: [ {} ]
		};

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.shouldShowTour( {
					tourName: 'test',
					userState: {
						version: 1,
						tours: {
							test: {
								step: 1
							}
						}
					},
					pageName: 'Foo',
					articleId: 123,
					condition: 'bogus'
				} );
			},
			gt.TourDefinitionError,
			/'bogus' is not a supported condition/,
			'gt.TourDefinitionError	with correct error message for invalid condition'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstArticleId: 123,
							step: 1
						}
					}
				},
				pageName: 'Foo',
				articleId: 123,
				condition: 'stickToFirstPage'
			} ),
			true,
			'Returns true for stickToFirstPage when on the original article'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstArticleId: 123,
							step: 1
						}
					}
				},
				pageName: 'Foo',
				articleId: 987,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for stickToFirstPage when on a different article'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							step: 1
						}
					}
				},
				pageName: 'Bar',
				articleId: 123
			} ),
			true,
			'Returns true when there is no condition'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstArticleId: 234,
							step: 1
						}
					}
				},
				pageName: 'Bar',
				articleId: 123
			} ),
			true,
			'Returns true when there is no condition even when there is a non-matching article ID in the cookie'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {},
						othertour: {
							firstArticleId: 234,
							step: 1
						}
					}
				},
				pageName: 'Bar',
				articleId: 123
			} ),
			true,
			'Returns true when there is no condition even when there is a non-matching article ID in the cookie, for another tour'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstSpecialPageName: 'Special:ImportantTask',
							step: 1
						}
					}
				},
				pageName: 'Special:ImportantTask',
				articleId: 0,
				condition: 'stickToFirstPage'
			} ),
			true,
			'Returns true for stickToFirstPage and matching special page'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstSpecialPageName: 'Special:ImportantTask',
							step: 1
						}
					}
				},
				pageName: 'Special:OtherTask',
				articleId: 0,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for stickToFirstPage and different special page'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'secondtour',
				userState: {
					version: 1,
					tours: {
						firsttour: {
							firstArticleId: 123,
							step: 1
						},
						secondtour: {
							firstArticleId: 234,
							step: 2
						}
					}
				},
				pageName: 'Foo',
				articleId: 123,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for stickToFirstPage for non-matching article ID when another tour\'s article ID matches'
		);

		// Mock the ve global, and its array of instances.
		window.ve = mockOpenVE;
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			true,
			'Returns true for VisualEditor condition when VisualEditor open'
		);

		// ve = undefined deliberately applies to all of the below until it is
		// reset to a mock instance for the expected false text
		window.ve = undefined;
		mw.config.set( 'wgAction', 'view' );
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			true,
			'Returns true for VisualEditor condition when viewing page with VE closed'
		);

		mw.config.set( 'wgAction', 'edit' );
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			false,
			'Returns false for VisualEditor condition when in wikitext editor'
		);

		mw.config.set( 'wgAction', 'submit' );
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			false,
			'Returns false for VisualEditor condition when reviewing wikitext changes'
		);

		mw.config.set( 'wgAction', 'edit' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			true,
			'Returns true for wikitext condition when editing wikitext'
		);

		mw.config.set( 'wgAction', 'submit' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			true,
			'Returns true for wikitext condition when reviewing wikitext'
		);

		mw.config.set( 'wgAction', 'view' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			true,
			'Returns true for wikitext condition when viewing page with VE closed'
		);

		window.ve = mockOpenVE;
		mw.config.set( 'wgAction', 'view' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			false,
			'Returns false for wikitext condition when VisualEditor is open'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'firsttour',
				userState: {
					version: 1,
					tours: {
						firsttour: {
							firstSpecialPageName: 'Special:ImportantTask',
							step: 1
						},
						secondtour: {
							firstSpecialPageName: 'Special:OtherTask',
							step: 2
						}
					}
				},
				pageName: 'Special:OtherTask',
				articleId: 0,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for non-matching article ID when another tour\'s special page matches'
		);
	} );

	QUnit.test( 'defineTour', 13, function ( assert ) {
		var SPEC_MUST_BE_OBJECT = /Check your syntax. There must be exactly one argument, 'tourSpec', which must be an object\./,
			NAME_MUST_BE_STRING = /'tourSpec.name' must be a string, the tour name\./,
			STEPS_MUST_BE_ARRAY = /'tourSpec.steps' must be an array, the list of steps\./,
			VALID_TOUR_SPEC = {
				name: 'valid',

				steps: [ {
					title: 'First step title',
					description: 'Second step title',
					overlay: true,
					buttons: [ {
						action: 'next'
					} ]
				}, {
					title: 'Second step title',
					description: 'Second step description',
					overlay: true,
					buttons: [ {
						action: 'end'
					} ]
				} ]
			};

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.defineTour();
			},
			gt.TourDefinitionError,
			SPEC_MUST_BE_OBJECT,
			'gt.TourDefinitionError with correct error message for empty call'
		);

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.defineTour( VALID_TOUR_SPEC, VALID_STEP );
			},
			gt.TourDefinitionError,
			SPEC_MUST_BE_OBJECT,
			'gt.TourDefinitionError with correct error message for multiple parameters'
		);

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.defineTour( null );
			},
			gt.TourDefinitionError,
			SPEC_MUST_BE_OBJECT,
			'gt.TourDefinitionError with correct error message for null call'
		);

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.defineTour( {
					steps: [ VALID_STEP ]
				} );
			},
			gt.TourDefinitionError,
			NAME_MUST_BE_STRING,
			'gt.TourDefinitionError with correct error message for missing name'
		);

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.defineTour( {
					name: 'test',
					steps: VALID_STEP
				} );
			},
			gt.TourDefinitionError,
			STEPS_MUST_BE_ARRAY,
			'gt.TourDefinitionError with correct error message for object passed for steps'
		);

		assertThrowsTypeAndMessage(
			assert,
			function () {
				return gt.defineTour( {
					name: 'test'
				} );
			},
			gt.TourDefinitionError,
			STEPS_MUST_BE_ARRAY,
			'gt.TourDefinitionError with correct error message for missing steps'
		);

		assert.strictEqual(
			gt.defineTour( VALID_TOUR_SPEC ),
			true,
			'Valid tour is defined successfully'
		);
	} );
} ( mediaWiki, jQuery ) );
