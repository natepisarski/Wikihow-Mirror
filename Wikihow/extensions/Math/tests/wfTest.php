<?php
/**
 * Created by PhpStorm.
 * User: Moritz
 * Date: 14.08.2017
 * Time: 12:09
 */
require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class DummyTest extends Maintenance {
	const REFERENCE_PAGE = 'mediawikiwiki:Extension:Math/CoverageTest';

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'Math' );
		$this->mDescription = 'Test Mathoid CLI';
		$this->addArg( 'page', "The page used for the testset generation.", false );
		$this->addOption( 'offset', "If set the first n equations on the page are skipped", false,
			true, "o" );
		$this->addOption( 'length', "If set the only n equations were processed", false, true,
			"l" );
		$this->addOption( 'user', "User with rights to view the page", false, true, "u" );
	}

	private static function getMathTagsFromPage( $titleString ) {
		global $wgEnableScaryTranscluding;
		$title = Title::newFromText( $titleString );
		if ( $title->exists() ) {
			$article = new Article( $title );
			$wikiText = $article->getPage()->getContent()->getNativeData();
		} else {
			if ( $title == self::REFERENCE_PAGE ) {
				$wgEnableScaryTranscluding = true;
				$parser = new Parser();
				$wikiText = $parser->interwikiTransclude( $title, 'raw' );
			} else {
				return 'Page does not exist';
			}
		}

		$wikiText = Sanitizer::removeHTMLcomments( $wikiText );
		$wikiText = preg_replace( '#<nowiki>(.*)</nowiki>#', '', $wikiText );
		$math = [];
		Parser::extractTagsAndParams( [ 'math' ], $wikiText, $math );

		return $math;
	}

	public function execute() {
		echo "This test accesses the Mathoid CLI.\n";
		$page = $this->getArg( 0, self::REFERENCE_PAGE );
		$offset = $this->getOption( 'offset', 0 );
		$length = $this->getOption( 'length', PHP_INT_MAX );
		$userName = $this->getOption( 'user', 'Maintenance script' );
		$wgUser = User::newFromName( $userName );
		$allEquations = self::getMathTagsFromPage( $page );
		if ( !is_array( $allEquations ) ) {
			echo "Could not get equations from page '$page'\n";
			echo $allEquations . PHP_EOL;

			return;
		} else {
			echo 'got ' . count( $allEquations ) . " math tags. Start processing.";
		}
		$i = 0;
		$rend = [];
		foreach ( array_slice( $allEquations, $offset, $length, true ) as $input ) {
			$output = MathRenderer::renderMath( $input[1], $input[2], 'mathml' );
			$rend[] = [ MathRenderer::getRenderer( $input[1], $input[2], 'mathml' ), $input ];
			$output = preg_replace( '#src="(.*?)/(([a-f]|\d)*).png"#', 'src="\2.png"', $output );
			$parserTests[] = [ (string)$input[1], $output ];
			$i++;
			echo '.';
		}
		echo "Generated $i tests\n";
		MathMathMLCli::batchEvaluate( $rend );
		$retval = null;
		$stdout = "[
  {
    \"query\": {
      \"q\": \"E=mc^{2}\"
    }}]";

// $f = TempFSFile::factory( 'mathoid', 'json', wfTempDir() );
// $f->autocollect();
// $fhandle = fopen( $f->getPath(), 'w' );
// if ( $fhandle ) {
// fwrite( $fhandle, $stdout );
// fclose( $fhandle );
// }
// $contents =
// wfShellExec( '/tmp/mathoid/cli.js -c /tmp/mathoid/config.dev.yaml ' . $f->getPath(),
// $retval );
// $contents =
// wfShellExecMath( '/tmp/mathoid/cli.js -c /tmp/mathoid/config.dev.yaml ',
// $retval, [], [], [], $stdout );

// if ( $retval == 0 ) {
// $res = json_decode( $contents, true );
// echo "JSON result" . var_export( $res, false ) . "\n";
// }
	}
}

$maintClass = 'DummyTest';
require_once RUN_MAINTENANCE_IF_MAIN;
