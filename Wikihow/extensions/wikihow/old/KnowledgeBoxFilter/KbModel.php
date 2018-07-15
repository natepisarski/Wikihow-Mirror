<?
namespace KB;
use SqlSuper, ToolSkip;
class KbModel extends SqlSuper {
	
	const TABLE = 'knowledgebox_contents';
	
	public $ordering = array(
		// 'ORDER BY' => 'kbc_timestamp DESC',
		'ORDER BY' => 'LENGTH(kbc_content) DESC',
		'LIMIT' => 10
	);
	
	public $title;
	public $skipper;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function findSubmissionsByIds($ids) {
		$ids = implode(',', $ids);
		
		return $this->select(
			self::TABLE, '*', 
			array("kbc_id IN ($ids)"), __METHOD__,
			$this->ordering
		);
	}
	
	public function findArticle($text) {
		return \Misc::getTitleFromText($text);
	}
	
	public function findSubmissions($articleId) {
		
		$subs = $this->select(
			self::TABLE, '*', 
			$this->conditions($articleId), __METHOD__,
			$this->ordering
		);
		
		return $subs;
	}

	public function skipSub($articleId, $subId) {
		$this->skipper = new ToolSkip("KbFilterManual-$articleId");
		// $this->skipper->clearSkipCache();
		$this->skipper->skipItem($subId);
	}
	
	public function conditions($articleId) {
		$this->skipper = new ToolSkip("KbFilterManual-$articleId");

		$cond = array(
			'kbc_aid' => $articleId,
			'kbc_plagiarized' => 0
		);
		
		return $this->whereNotIn(
			$cond, $this->skipper->getSkipped(), 
			NULL, 'kbc_id', 'knowledgebox_contents'
		);
	}
}
