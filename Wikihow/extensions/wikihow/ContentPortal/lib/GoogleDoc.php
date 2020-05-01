<?php

namespace ContentPortal;

class GoogleDoc {

	public $service;
	public $article;
	public $folderId;

	const DEV_FOLDER = '1l2q77Jb5yVvhyHVcB6f62rnPl-MBIRw_';
	const PROD_FOLDER = '0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc';

	public function __construct(Article $article) {
		$this->article = $article;
		$this->folderId = ENV == 'production' ? self::PROD_FOLDER : self::DEV_FOLDER;
		$this->service = \GoogleDrive::getService();
	}

	public function createWritingDoc() {
		$fileMeta = new \Google_Service_Drive_DriveFile([
			'name' => $this->article->title,
			'parents' => [ $this->folderId ],
			'mimeType' => 'application/vnd.google-apps.document'
		]);
		$reqParams = [
			'fields' => 'id,name,description,webViewLink',
			'enforceSingleParent' => true,
		];
		$file = $this->service->files->create($fileMeta, $reqParams);

		$perm = new \Google_Service_Drive_Permission([
			'type' => 'anyone',
			'role' => 'commenter',
		]);
		$optParams = [ 'enforceSingleParent' => true ];
		$res = $this->service->permissions->create($file->id, $perm, $optParams);

		return $file;
	}

	public function createVerificationDoc() {
		$tools = new \ExpertDocTools();
		$title = \Title::newFromId($this->article->wh_article_id);
		$slug = $title->getText();
		return $tools->createExpertDoc($slug, null, Router::getInstance()->getContext(), $this->folderId );
	}

}
