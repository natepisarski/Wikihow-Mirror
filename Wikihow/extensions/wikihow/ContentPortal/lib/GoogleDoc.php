<?
namespace ContentPortal;
global $IP;
require_once("$IP/extensions/wikihow/socialproof/ExpertVerifyImporter.php");
require_once("$IP/extensions/wikihow/titus/GoogleSpreadsheet.class.php");

use Title, GoogleSpreadsheet, Google_Service_Drive_Permission, ExpertVerifyImporter,
	Google_Service_Drive_ParentReference, Google_Service_Drive_DriveFile, Google_Service_Drive_Permissions_Resource;

class GoogleDoc {

	public $service;
	public $article;
	public $folderId;

	const DOH_FOLDER = '0B66Rhz56bzLHflROYm5oYlc2dWtHRHNoRE1RandlaG0tY1l0YUtLVWZLMXVydHlZeUtZbk0';
	const PROD_FOLDER = '0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc';

	public function __construct(Article $article) {
		$this->article = $article;
		$gs = new GoogleSpreadsheet();
		$this->folderId = ENV == 'production' ? self::PROD_FOLDER : self::DOH_FOLDER;
		$this->service = $gs->getService();
	}

	public static function build(Article $article) {
		return new GoogleDoc($article);
	}

	public function createWritingDoc() {
		$file = new Google_Service_Drive_DriveFile();
		$file->setTitle($this->article->title);
		$file->setDescription('created document for writing');
		$file->setParents([$this->getFolder()]);

		$createdFile = $this->service->files->insert($file, [
			'data'       => '',
			'mimeType'   => 'text/html',
			'uploadType' => 'multipart',
			'convert'    => 'true'
		]);

		$this->setPermissions($createdFile);
		return $createdFile;
	}

	public function createVerificationDoc() {
		$exporter = new ExpertVerifyImporter();
		$title = Title::newFromId($this->article->wh_article_id);
		$slug = $title->getText();
		return $exporter->createExpertDoc($this->service, $slug, null, Router::getInstance()->getContext(), $this->folderId );
	}

	private function getFolder() {
		$parent = new Google_Service_Drive_ParentReference();
		$parent->setId($this->folderId);
		return $parent;
	}

	private function setPermissions($file, $role='writer') {
		$perm = new Google_Service_Drive_Permission();
		$perm->setRole($role);
		$perm->setType('anyone');
		$perm->setAdditionalRoles(['commenter']);
		$this->service->permissions->insert($file->id, $perm);
	}


}
