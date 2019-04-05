<?php

class ApiMobileViewWikihow extends ApiMobileView {

	/**
	 * FIXME: Write some unit tests for API results
	 */
	public function execute() {

		parent::execute();

		$params = $this->extractRequestParams();
		$title = $this->makeTitle( $params['page'] );

		// From WikiPage::prepareContentForEdit
		global $wgUser, $wgContLang;
		$popts = ParserOptions::newFromUserAndLang( $wgUser, $wgContLang );

		$contentHandler = new WikitextContentHandler();
		$revision = Revision::newFromTitle($title);
		$content = $revision->getContent();

		$edit = (object)array();

		$edit->pstContent = $content ? $content->preSaveTransform( $title, $wgUser, $popts ) : null;

		$edit->popts = $contentHandler->makeParserOptions( 'canonical' );
		$edit->output = $edit->pstContent ? $edit->pstContent->getParserOutput( $title, null, $edit->popts ) : null;

		$outputPage = $this->getContext()->getOutput();
		$outputPage->addParserOutput($edit->output);

		// From SkinMinerva::prepareQuickTemplate
		$text = ExtMobileFrontend::DOMParse( $outputPage );

		$skin = $this->getContext()->getSkin();
		$oldTitle = $skin->getRelevantTitle();
		$skin->setRelevantTitle($title);

		$this->getResult()->addValue( null, $this->getModuleName(), array( 'text' => WikihowMobileTools::processDom($text, $skin)));

		$skin->setRelevantTitle($oldTitle);
	}

	function getAllowedParams() {
		$res = parent::getAllowedParams();

		$res['sectionId'] = array(
			ApiBase::PARAM_TYPE => 'integer',
			ApiBase::PARAM_DFLT => 0,
		);
		$res['useformat'] = array(
			ApiBase::PARAM_TYPE => 'string',
			ApiBase::PARAM_DFLT => false,
		);

		return $res;
	}
}
