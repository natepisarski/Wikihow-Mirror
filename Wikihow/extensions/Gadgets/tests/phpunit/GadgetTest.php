<?php

use Wikimedia\TestingAccessWrapper;

/**
 * @group Gadgets
 */
class GadgetTest extends MediaWikiTestCase {
	/**
	 * @var User
	 */
	protected $user;

	public function setUp() {
		global $wgGroupPermissions;

		parent::setUp();

		$wgGroupPermissions['unittesters'] = [
			'test' => true,
		];
		$this->user = $this->getTestUser( [ 'unittesters' ] )->getUser();
	}

	public function tearDown() {
		GadgetRepo::setSingleton();
		parent::tearDown();
	}

	/**
	 * @param string $line
	 * @return Gadget
	 */
	private function create( $line ) {
		$repo = new MediaWikiGadgetsDefinitionRepo();
		$g = $repo->newFromDefinition( $line, 'misc' );
		$this->assertInstanceOf( Gadget::class, $g );
		return $g;
	}

	private function getModule( Gadget $g ) {
		$module = TestingAccessWrapper::newFromObject(
			new GadgetResourceLoaderModule( [ 'id' => null ] )
		);
		$module->gadget = $g;
		return $module;
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 */
	public function testInvalidLines() {
		$repo = new MediaWikiGadgetsDefinitionRepo();
		$this->assertFalse( $repo->newFromDefinition( '', 'misc' ) );
		$this->assertFalse( $repo->newFromDefinition( '<foo|bar>', 'misc' ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget
	 */
	public function testSimpleCases() {
		$g = $this->create( '* foo bar| foo.css|foo.js|foo.bar' );
		$this->assertEquals( 'foo_bar', $g->getName() );
		$this->assertEquals( 'ext.gadget.foo_bar', Gadget::getModuleName( $g->getName() ) );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getScripts() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.css' ], $g->getStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js', 'MediaWiki:Gadget-foo.css' ],
			$g->getScriptsAndStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getLegacyScripts() );
		$this->assertFalse( $g->supportsResourceLoader() );
		$this->assertTrue( $g->hasModule() );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::supportsResourceLoader
	 * @covers Gadget::getLegacyScripts
	 */
	public function testRLtag() {
		$g = $this->create( '*foo [ResourceLoader]|foo.js|foo.css' );
		$this->assertEquals( 'foo', $g->getName() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( 0, count( $g->getLegacyScripts() ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isAllowed
	 */
	public function testisAllowed() {
		$gUnset = $this->create( '*foo[ResourceLoader]|foo.js' );
		$gAllowed = $this->create( '*bar[ResourceLoader|rights=test]|bar.js' );
		$gNotAllowed = $this->create( '*baz[ResourceLoader|rights=nope]|baz.js' );
		$this->assertTrue( $gUnset->isAllowed( $this->user ) );
		$this->assertTrue( $gAllowed->isAllowed( $this->user ) );
		$this->assertFalse( $gNotAllowed->isAllowed( $this->user ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isSkinSupported
	 */
	public function testSkinsTag() {
		$gUnset = $this->create( '*foo[ResourceLoader]|foo.js' );
		$gSkinSupported = $this->create( '*bar[ResourceLoader|skins=fallback]|bar.js' );
		$gSkinNotSupported = $this->create( '*baz[ResourceLoader|skins=bar]|baz.js' );
		$skin = new SkinFallback();
		$this->assertTrue( $gUnset->isSkinSupported( $skin ) );
		$this->assertTrue( $gSkinSupported->isSkinSupported( $skin ) );
		$this->assertFalse( $gSkinNotSupported->isSkinSupported( $skin ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::getDependencies
	 */
	public function testDependencies() {
		$g = $this->create( '* foo[ResourceLoader|dependencies=jquery.ui]|bar.js' );
		$this->assertEquals( [ 'MediaWiki:Gadget-bar.js' ], $g->getScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( [ 'jquery.ui' ], $g->getDependencies() );
	}

	public static function provideGetType() {
		return [
			[
				'Default (mixed)',
				'* foo[ResourceLoader]|bar.css|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'Default (styles only)',
				'* foo[ResourceLoader]|bar.css',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'Default (scripts only)',
				'* foo[ResourceLoader]|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'Default (styles only with dependencies)',
				'* foo[ResourceLoader|dependencies=jquery.ui]|bar.css',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'Styles type (mixed)',
				'* foo[ResourceLoader|type=styles]|bar.css|bar.js',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'Styles type (styles only)',
				'* foo[ResourceLoader|type=styles]|bar.css',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'Styles type (scripts only)',
				'* foo[ResourceLoader|type=styles]|bar.js',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'General type (mixed)',
				'* foo[ResourceLoader|type=general]|bar.css|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'General type (styles only)',
				'* foo[ResourceLoader|type=general]|bar.css',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'General type (scripts only)',
				'* foo[ResourceLoader|type=general]|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
		];
	}

	/**
	 * @dataProvider provideGetType
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::getType
	 * @covers GadgetResourceLoaderModule::getType
	 */
	public function testType( $message, $definition, $gType, $mType ) {
		$g = $this->create( $definition );
		$this->assertEquals( $gType, $g->getType(), "Gadget: $message" );
		$this->assertEquals( $mType, $this->getModule( $g )->getType(), "Module: $message" );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isHidden
	 */
	public function testIsHidden() {
		$g = $this->create( '* foo[hidden]|bar.js' );
		$this->assertTrue( $g->isHidden() );

		$g = $this->create( '* foo[ResourceLoader|hidden]|bar.js' );
		$this->assertTrue( $g->isHidden() );

		$g = $this->create( '* foo[ResourceLoader]|bar.js' );
		$this->assertFalse( $g->isHidden() );
	}

	/**
	 * @covers Gadget
	 * @covers GadgetHooks::getPreferences
	 * @covers GadgetRepo
	 * @covers MediaWikiGadgetsDefinitionRepo
	 */
	public function testPreferences() {
		$prefs = [];
		$repo = TestingAccessWrapper::newFromObject( new MediaWikiGadgetsDefinitionRepo() );
		// Force usage of a MediaWikiGadgetsDefinitionRepo
		GadgetRepo::setSingleton( $repo );

		$gadgets = $repo->fetchStructuredList( '* foo | foo.js
==keep-section1==
* bar| bar.js
==remove-section==
* baz [rights=embezzle] |baz.js
==keep-section2==
* quux [rights=test] | quux.js' );
		$this->assertGreaterThanOrEqual( 2, count( $gadgets ), "Gadget list parsed" );

		$repo->definitionCache = $gadgets;
		$this->assertTrue( GadgetHooks::getPreferences( $this->user, $prefs ),
			'GetPrefences hook should return true' );

		$options = $prefs['gadgets']['options'];
		$this->assertArrayNotHasKey( '⧼gadget-section-remove-section⧽', $options,
			'Must not show empty sections' );
		$this->assertArrayHasKey( '⧼gadget-section-keep-section1⧽', $options );
		$this->assertArrayHasKey( '⧼gadget-section-keep-section2⧽', $options );
	}
}
