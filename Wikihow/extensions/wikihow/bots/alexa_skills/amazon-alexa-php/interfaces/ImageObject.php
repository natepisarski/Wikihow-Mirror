<?php

/**
 * Image object abstraction as described here:
 *
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/display-interface-reference#image-object-specifications
 *
 * Currently a light implementation with only one image. Could be expanded for different sizes in the fture
 */
class ImageObject implements DisplayTemplateInterface {

	var $imageUrl = null;
	var $imageDescription = null;
	public function __construct($imgUrl, $imageDescription) {
		$this->imageUrl = $imgUrl;
		$this->imageDescription = $imageDescription;
	}

	/**
	 * @return null
	 */
	public function getImageUrl() {
		return $this->imageUrl;
	}

	/**
	 * @param null $imageUrl
	 */
	public function setImageUrl($imageUrl) {
		$this->imageUrl = $imageUrl;
	}

	/**
	 * @return null
	 */
	public function getImageDescription() {
		return $this->imageDescription;
	}

	/**
	 * @param null $imageDescription
	 */
	public function setImageDescription($imageDescription) {
		$this->imageDescription = $imageDescription;
	}
	public function render() {
		$rendered = null;
		if ($this->getImageDescription() && $this->getImageUrl()) {
			$rendered = [
				'contentDescription' => $this->getImageDescription(),
				'sources' => [
					['url' => $this->getImageUrl()]
				]
			];
		}

		return $rendered;
	}
}
