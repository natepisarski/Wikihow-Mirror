<?php
/**
 * VideoApp interface abstraction as described here:
 *
 * https://developer.amazon.com/docs/custom-skills/videoapp-interface-reference.html
 *
 * Currently a light implementation. Could be expanded in the fture
 */
class VideoApp {

	var $title = "";
	var $subtitle = "";
	var $sourceUrl = "";

	public function __construct($sourceUrl, $title, $subtitle) {
		$this->setSourceUrl($sourceUrl);
		$this->setTitle($title);
		if (!empty($subtitle)) {
			$this->setSubtitle($subtitle);
		}
	}

	public function render() {
		$template = [
			'type' => 'VideoApp.Launch',
			'videoItem' => [
				'source' => $this->getSourceUrl(),
				'metadata' => [
					'title' => $this->getTitle(),
					'subtitle' => $this->getSubtitle()
				]
			]
		];

		return $template;
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
	public function getSubtitle(): string {
		return $this->subtitle;
	}

	/**
	 * @param string $subtitle
	 */
	public function setSubtitle(string $subtitle) {
		$this->subtitle = $subtitle;
	}

	/**
	 * @return string
	 */
	public function getSourceUrl(): string {
		return $this->sourceUrl;
	}

	/**
	 * @param string $sourceUrl
	 */
	public function setSourceUrl(string $sourceUrl) {
		$this->sourceUrl = $sourceUrl;
	}
}
