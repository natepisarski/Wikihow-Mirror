<?php

/**
 * BodyTemplate1 abstraction c
 *
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/display-interface-reference#bodytemplate1
 */
class BodyTemplate1 implements DisplayTemplateInterface {

	var $title = "";
	var $bodyText = "";

	public function __construct($title, $bodyText) {
		$this->setTitle($title);
		$this->setBodyText($bodyText);
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
		//$image = new ImageObject('https://pad1-www.cdnga.net/images/thumb/7/76/Kiss-Step-1-Version-5.jpg/aid2053-v4-728px-Kiss-Step-1-Version-5.jpg.webp', 'description');
		$template = [
			'type' => 'Display.RenderTemplate',
			'template' => [
				'type' => DisplayTemplateTypes::TYPE_BODY_TEMPLATE_1,
				"token" => "WH_BodyTemplate1",
				"backButton" => "VISIBLE",
				'title' => $this->getTitle(),
				"textContent" =>  $textContent->render(),
			]
		];

		return $template;
	}
}
