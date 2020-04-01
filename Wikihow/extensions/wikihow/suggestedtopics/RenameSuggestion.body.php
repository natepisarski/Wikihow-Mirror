<?php

class RenameSuggestion extends UnlistedSpecialPage
{
	public function __construct() {
		parent::__construct('RenameSuggestion');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();

		$mustacheEngine = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
		]);
		$vars = [
			'name' => $req->getVal('name'),
			'id' => $req->getVal('id')
		];
		$html = $mustacheEngine->render('rename_suggestion.mustache', $vars);

		$out->setArticleBodyOnly(true);
		$out->addHTML($html);
	}
}
