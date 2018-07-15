<?php

/**
 * @group wikihow
 * @group ImageHooks
 */
class ImageHooksTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	public function dataImageFileParameters() {
		return array(
			// ( $incomingParams, $resultingParams )
			// test a v3 thumbnail
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "v3-745px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'version' => 3,
					'width' => 745
				)
			),
			// test a v4 thumbnail
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "v4-745px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'version' => 4,
					'width' => 745
				)
			),
			// test a quality thumbnail
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "q80-745px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'quality' => 80,
					'width' => 745
				)
			),
			// test an aid type thumbnail
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "aid2053-745px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'pageId' => 'aid2053',
					'width' => 745
				)
			),
			// test an aid type thumbnail with version
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "aid2053-v4-745px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'pageId' => 'aid2053',
					'version' => 4,
					'width' => 745
				)
			),
			// test crop without height
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "-crop-200--200px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'crop' => 1,
					'width' => 200,
					'height' => 0,
					'reqwidth' => 200
				)
			),
			// test crop
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "-crop-163-119-159px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'crop' => 1,
					'width' => 163,
					'height' => 119,
					'reqwidth' => 159
				)
			),
			// test very small crop.. which is an edge case in the hooks code
			// although it does not produce a usable thumbnail
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "-crop-5-5-159px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'crop' => 1,
					'width' => 159,
					'height' => 5,
				)
			),
			// test no watermark
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "745px-nowatermark-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'width' => 745,
					'noWatermark' => 1
				)
			),
			// test crop and version
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "v3--crop-163-119-159px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'crop' => 1,
					'width' => 163,
					'height' => 119,
					'reqwidth' => 159,
					'version' => 3
				)
			),
			// test crop and version in another position
			array(
				array(
					'file' => "Kiss-Step-1-Version-5.jpg",
					'thumbName' => "v3-crop-163-119-159px-Kiss-Step-1-Version-5.jpg",
				),
				array(
					'crop' => 1,
					'width' => 163,
					'height' => 119,
					'reqwidth' => 159,
					'version' => 3
				)
			),
		);
	}

	/**
	 * test the parseParamString function which calls in to our image hooks
	 * we use the same data that is set up in thumb.php to create the paramString argument
	 * @dataProvider dataImageFileParameters
	 * @covers ImageHooks::
	 */
	public function testParseParamStringAuto( $paramsIn, $paramsOut ) {
		$handler = wfLocalFile( $paramsIn['file'] )->getHandler();
		$fileNamePos = strrpos( $paramsIn['thumbName'], $paramsIn['file'] );
		$paramString = substr( $paramsIn['thumbName'], 0, $fileNamePos - 1 );
		$this->assertEquals( $paramsOut, $handler->parseParamString( $paramString ) );
	}

}
