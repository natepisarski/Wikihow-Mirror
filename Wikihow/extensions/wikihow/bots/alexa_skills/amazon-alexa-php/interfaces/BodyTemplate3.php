<?php

/**
 * BodyTemplate3 abstraction
 *
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/display-interface-reference#bodytemplate3
 */
class BodyTemplate3 implements DisplayTemplateInterface {

	var $title = "";
	var $bodyText = "";
	var $imageUrl = "";


	/**
	 * @return string
	 */
	public function getImageUrl(): string {
		return $this->imageUrl;
	}

	/**
	 * @param string $imageUrl
	 */
	public function setImageUrl(string $imageUrl) {
		$this->imageUrl = $imageUrl;
	}

	public function __construct($title, $bodyText, $imageUrl = null) {
		$this->setTitle($title);
		$this->setBodyText($bodyText);

		if (!empty($imageUrl)) {
			$this->setImageUrl($imageUrl);
		}
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle(string $title) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getBodyText(): string {
		return $this->bodyText;
	}

	/**
	 * @param string $bodyText
	 */
	public function setBodyText(string $bodyText) {
		$this->bodyText = $bodyText;
	}


	public function render() {
		$textContent = new TextContentObject($this->getBodyText());
		$template = [
			'type' => 'Display.RenderTemplate',
			'template' => [
				'type' => DisplayTemplateTypes::TYPE_BODY_TEMPLATE_3,
				"token" => DisplayTemplateTypes::TYPE_BODY_TEMPLATE_3,
				"backButton" => "HIDDEN",
				'title' => $this->getTitle(),
				"textContent" =>  $textContent->render(),
			]
		];

		if ($imgUrl = $this->getImageUrl()) {
			$image = new ImageObject($imgUrl, $this->getTitle());
			$template['template']['image'] = $image->render();
		}

		return $template;
	}
}
