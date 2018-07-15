<?
namespace ContentPortal;
class Document extends AppModel {

	const WRITING = 'writing';
	const VERIFY = 'verification';

	static $table_name = "cf_documents";
	static $belongs_to = ['article'];
	static $all;


	function isWriting() {
		return $this->type == self::WRITING;
	}


	function isVerification() {
		return $this->type == self::VERIFY;
	}

	// CALLBACKS...

	function logStr() {
		return "{$this->article->logStr()} / {$this->type}::{$this->id}";
	}

	function before_create() {
		if ($this->article->is_test || ENV == 'test') return true;

		$gDoc = new GoogleDoc($this->article);
		$file = $this->isWriting() ? $gDoc->createWritingDoc() : $gDoc->createVerificationDoc();
		$this->doc_id = $file->id;
		$this->doc_url = $file->alternateLink;
		return true;
	}
}