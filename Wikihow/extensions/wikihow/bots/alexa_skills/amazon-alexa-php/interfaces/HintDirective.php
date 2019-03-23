<?php

/**
 * Hint directive abstraction as described here:
 *
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/display-interface-reference#hint-directive
 *
 */
class HintDirective implements DisplayTemplateInterface {

	/**
	 * @var string
	 */
	var $hintText;

	/**
	 * @return string
	 */
	public function getHintText() {
		return $this->hintText;
	}

	/**
	 * @param string $hintText
	 */
	public function setHintText($hintText) {
		$this->hintText = $hintText;
	}

	public function __construct($hintText) {
		$this->setHintText($hintText);
	}

	public function render() {
		return [
			'type' => 'Hint',
			'hint' => [
				'type' => 'PlainText',
				'text' => $this->getHintText(),
			]
		];
	}

}
