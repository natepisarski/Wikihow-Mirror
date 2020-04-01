<?php

/*
 * Wraps the Revision class to get text without templates. This is useful for predicting what templates
 * should go on articles without them. For example, we might want to predict what articles should get
 * a stub or NFD template.
 */
class RevisionNoTemplateWrapper {
	private $revision;
	private $text;
	public function __construct($revision) {
		$this->revision = $revision;
		$this->text = false;
	}
	/**
     * Remove text with templates gone, caching the replace
	 */
	public function getText() {
		if (!$this->text) {
			$this->text = ContentHandler::getContentText( $this->revision->getContent() );
			$this->text = preg_replace("@\{\{[^}]+\}\}@","",$this->text);
		}
		return($this->text);
	}
	public function getOrigText() {
		return ContentHandler::getContentText( $this->revision->getContent() );
	}
	public function getId() {
		return $this->revision->getId();
	}
	public function getTitle() {
		return $this->revision->getTitle();
	}
	public function getSize() {
		return $this->revision->getSize();
	}
}
