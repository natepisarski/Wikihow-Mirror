<?php

/**
 * Image object abstraction as described here:
 *
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/display-interface-reference#textcontent-object-specifications
 *
 * Currently a light implementation with only one image. Could be expanded for different sizes in the fture
 */
class TextContentObject implements DisplayTemplateInterface {

	var $primaryText = null;
	var $secondaryText = null;
	var $tertiaryText = null;
	var $textType = null;

	const TEXT_TYPE_PLAIN_TEXT = 'PlainText';
	const TEXT_TYPE_RICH_TEXT = 'RichText';

	public function __construct(
		$primaryText, $secondaryText = null, $tertiaryText = null, $textType = self::TEXT_TYPE_RICH_TEXT) {

		$this->setPrimaryText($primaryText);
		$this->setTextType($textType);

		if (!is_null($secondaryText)) {
			$this->setSecondaryText($secondaryText);
		}

		if (!is_null($tertiaryText)) {
			$this->setTertiaryText($tertiaryText);
		}
	}

	public function render() {
		$rendered = null;
		if ($this->getPrimaryText() && $this->getTextType()) {
			$rendered = [
				'primaryText' => ['type' => $this->getTextType(), 'text' => $this->getPrimaryText()]
			];

			if ($this->getSecondaryText()) {
				$rendered['secondaryText'] = ['type' => $this->getTextType(), 'text' => $this->getSecondaryText()];
			}

			if ($this->getTertiaryText()) {
				$rendered['tertiaryText'] = ['type' => $this->getTextType(), 'text' => $this->getTertiaryText() ];
			}
		}

		return $rendered;
	}

	/**
	 * @return null
	 */
	public function getPrimaryText() {
		return $this->primaryText;
	}

	/**
	 * @param null $primaryText
	 */
	public function setPrimaryText($primaryText) {
		$this->primaryText = $primaryText;
	}

	/**
	 * @return null|string
	 */
	public function getSecondaryText() {
		return $this->secondaryText;
	}

	/**
	 * @param string $secondaryText
	 */
	public function setSecondaryText($secondaryText) {
		$this->secondaryText = $secondaryText;
	}

	/**
	 * @return null|string
	 */
	public function getTertiaryText() {
		return $this->tertiaryText;
	}

	/**
	 * @param null $tertiaryText
	 */
	public function setTertiaryText($tertiaryText) {
		$this->tertiaryText = $tertiaryText;
	}

	/**
	 * @return null|string
	 */
	public function getTextType() {
		return $this->textType;
	}

	/**
	 * @param null $textType
	 */
	public function setTextType($textType) {
		$this->textType = $textType;
	}
}
