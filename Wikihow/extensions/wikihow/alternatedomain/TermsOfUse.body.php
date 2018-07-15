<?

if (!defined('MEDIAWIKI')) {
    die();
}

class TermsOfUse extends SpecialPage {

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

    public function execute( $subPage ) {
		global $wgDebugToolbar, $IP;

		$request = $this->getRequest();
		$out = $this->getOutput();

		$title = Title::newFromText( "Terms-of-Use", NS_PROJECT );
		if ( class_exists( 'AlternateDomain' ) && !AlternateDomain::onAlternateDomain() ) {
			// if we are on the normal domain then redirect to the terms of use page
			$title = Title::newFromText( "Terms-of-Use", NS_PROJECT );
			$url = $title->getFullURL();
			$out->redirect( $url, 301 );
		}

		$popts = $this->getOutput()->parserOptions();
		$revision = Revision::newFromTitle( $title );
		$parserOutput = $out->parse($revision->getText(), $title, $popts);
		$magic = WikihowArticleHTML::grabTheMagic($revision->getText());
		$result = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
        $out->setPageTitle( "Terms Of Use" );
        $out->addHtml( $result );

		return;

    }
}
