<?php

use Wikimedia\TestingAccessWrapper;

/**
 * @covers GuidedTourLauncher
 */
class GuidedTourLauncherTest extends MediaWikiTestCase {
	protected $wrappedLauncher;

	public function setUp() {
		parent::setUp();

		$this->wrappedLauncher = TestingAccessWrapper::newFromClass( GuidedTourLauncher::class );
	}

	/**
	 * @dataProvider getNewStateProvider
	 */
	public function testGetNewState( $oldStateValue, $tourName, $step, $expectedNewStateValue ) {
		$newStateValue = $this->wrappedLauncher->getNewState( $oldStateValue, $tourName, $step );
		$this->assertSame(
			$expectedNewStateValue,
			$newStateValue
		);
	}

	public function getNewStateProvider() {
		$simpleExpectedState = [
			'version' => 1,
			'tours' => [
				'example' => [
					'step' => 'bar',
				],
			],
		];

		return [
			[
				[
					'version' => 1,
					'tours' => [
						'example' => [
							'step' => 'foo',
							'firstArticleId' => 123,
						],
					],
				],
				'example',
				'bar',
				[
					'version' => 1,
					'tours' => [
						'example' => [
							'step' => 'bar',
							'firstArticleId' => 123,
						],
					]
				]
			],

			[
				null,
				'example',
				'bar',
				$simpleExpectedState,
			],

			[
				[],
				'example',
				'bar',
				$simpleExpectedState
			],

			[
				[
					'version' => 1,
					'tours' => [
						'someOtherTour' => [
							'step' => 'baz',
							'firstSpecialPageName' => 'Special:Watchlist',
						],
					]
				],
				'example',
				'bar',
				[
					'version' => 1,
					'tours' => [
						'someOtherTour' => [
							'step' => 'baz',
							'firstSpecialPageName' => 'Special:Watchlist',
						],
						'example' => [
							'step' => 'bar',
						],
					]
				],
			],
		];
	}

	// This should mostly be covered by testGetNewState; these are a couple tests to
	// handle edge cases.
	/**
	 * @dataProvider getNewCookieProvider
	 */
	public function testGetNewCookie( $oldCookieValue, $tourName, $step, $expectedNewCookieValue ) {
		$newCookieValue = GuidedTourLauncher::getNewCookie( $oldCookieValue, $tourName, $step );
		$this->assertSame(
			$expectedNewCookieValue,
			$newCookieValue
		);
	}

	public function getNewCookieProvider() {
		$simpleExpectedCookieString = FormatJson::encode( [
			'version' => 1,
			'tours' => [
				'example' => [
					'step' => 'bar',
				],
			],
		] );

		return [
			[
				FormatJson::encode( [
					'version' => 1,
					'tours' => [
						'example' => [
							'step' => 'foo',
							'firstArticleId' => 123,
						],
					],
				] ),
				'example',
				'bar',
				FormatJson::encode( [
					'version' => 1,
					'tours' => [
						'example' => [
							'step' => 'bar',
							'firstArticleId' => 123,
						],
					]
				] )
			],

			[
				'',
				'example',
				'bar',
				$simpleExpectedCookieString
			],
		];
	}
}
