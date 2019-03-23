<?php

use Alexa\Response\Response;

/**
 * Subclass of Alexa\Response\Response to add Display Template functionality
 */
class WikihowAlexaResponse extends Response {

	/**
	 * @var DisplayTemplateInterface
	 */
	var $displayTemplate;

	/**
	 * @var HintDirective  Hint text to be displayed on Alexa Show screens
	 */
	var $hint;

	/**
	 * @var VideoApp VideoApp Interface to be displayed on video-capable Alexa devices
	 */
	var $videoApp;

	public function render() {
		$rendered =  parent::render();

		if ($this->getDisplayTemplate()) {
			$rendered['response']['directives'] []= $this->getDisplayTemplate()->render();
		}

		if ($this->getVideoApp()) {
			$rendered['response']['directives'] []= $this->getVideoApp()->render();
			// shouldEndSession flag is not allowed with LaunchVideoApp.Launch Directive
			unset($rendered['response']['shouldEndSession']);
		}

		if ($this->getHint()) {
			$rendered['response']['directives'] []= $this->getHint()->render();
		}

		wfDebugLog('AlexaSkillReadArticleWebHook', var_export(__METHOD__, true), true);
		wfDebugLog('AlexaSkillReadArticleWebHook', var_export($rendered, true), true);
		return $rendered;
	}

	public function setDisplayTemplate(DisplayTemplateInterface $template) {
		$this->displayTemplate = $template;
	}

	/**
	 * @return DisplayTemplateInterface
	 */
	public function getDisplayTemplate() {
		return $this->displayTemplate;
	}


	/**
	 * @return string
	 */
	public function getHint() {
		return $this->hint;
	}

	/**
	 * @param string $hint
	 */
	public function setHint(string $hint) {
		$this->hint = new HintDirective($hint);
	}

	/**
	 * @param VideoApp $videoApp
	 */
	public function setVideoApp(VideoApp $videoApp) {
		$this->videoApp = $videoApp;
	}

	/**
	 * @return VideoApp
	 */
	public function getVideoApp() {
		return $this->videoApp;
	}
}
