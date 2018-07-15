<?
namespace ContentPortal;
use __;
use Title;

class Article extends AppModel {

	public $validator;
	public $validateTitle = false;
	public $import_notes = '';
	static $table_name = "cf_articles";
	static $belongs_to = [
		'category',
		['state', 'class' => 'Role', 'foreign_key' => 'state_id'],
		['assigned_user', 'class' => 'User', 'foreign_key' => 'assigned_id']
	];
	static $has_many = [
		'notes', 'documents',
		['user_articles', 'order' => 'created_at ASC'],
		['events', 'conditions' => "grouping = '".Event::GENERAL."'", 'order' => 'created_at DESC'],
		['users', 'through' => 'user_articles'],
		['roles', 'through' => 'user_articles'],
		['info_notes', 'class' => 'Note', 'conditions' => "type = '" . Note::INFO . "'"]
	];

	static $validates_presence_of = ['title','category_id'];
	static $validates_uniqueness_of = ['title'];

	function before_validation() {
		$this->validator = new ArticleValidator($this);
	}

	function validate() {
		if ($this->validateTitle) $this->validator->validate();
	}

	function belongsTo($user) {
		return $user && ($this->assigned_id == $user->id);
	}

	// may need this some day...
	// static function messages_sort($a, $b) {
	// 	return strcmp(count($a->messages), count($b->messages));
	// }

	function completedAssignments() {
		return __::filter($this->user_articles, ['complete' => true]);
	}

	function mostRecentAction() {
		return __::first($this->events);
	}

	function lastTouched() {
		$event = __::first($this->events);
		return $event ? $event->created_at : $this->updated_at;
	}

	function mostRecentEventMessage() {
		return $this->mostRecentAction() ? $this->mostRecentAction()->message : null;
	}

	function notesForState($mostRecent=false) {
		$notes = [];

		if (!is_null($this->state)) {
			$notes = $this->notesFor($this->state);

			//hack for editors to see reserve notes too
			if ($this->state->id == Role::edit()->id) {
				$notes = array_merge($notes, $this->notesFor(Role::reserveArticle()));
			}
		}

		return $notes;
	}

	function reviewReturnCount() {
		return count(__::filter($this->info_notes, function ($note) {
			return $note->role_id == Role::edit()->id;
		}));
	}

	function lastNote() {
		return __::last($this->notes);
	}

	function notesFor(Role $role) {
		return __::filter($this->notes, function ($note) use ($role) {
			if ($note->note_id) return false; // don't return responses otherwise messes up tree
			if ($note->prev_assign_id) return $note->prev_assignment->role_id == $role->id;
			return $note->role_id == $role->id;
		});
	}

	function currentAssignment() {
		return Assignment::build($this)->currentAssignment;
	}

	function prevAssignment() {
		return Assignment::build($this)->prevAssignment;
	}

	function isBlocked() {
		return $this->state_id == Role::blockingQuestion()->id;
	}

	function blockingQuestion() {
		return __::chain($this->notes)->filter(['type' => Note::BLOCKING])->last()->value();
	}

	function isUnassigned() {
		return $this->assigned_id == null;
	}

	function isLinked() {
		return !is_null($this->wh_article_id);
	}

	function isFinished() {
		return in_array($this->state->key, [Role::COMPLETE_KEY, Role::COMPLETE_VERIFIED_KEY]);
	}

	function isInState($key) {
		return $this->state->key == $key;
	}

	function invalidateVerifiyDocs() {
		foreach($this->verifyDocs() as $doc) {
			$doc->outdated = true;
			$doc->save();
		}
	}


	// documents
	function hasWritingDoc() {
		return Document::exists(['article_id' => $this->id, 'type' => Document::WRITING]);
	}
	function writingDoc() {
		if ($this->hasWritingDoc()) {
			return __::find($this->documents, ['type' => Document::WRITING]);
		} else {
			return Document::create([
				'type' => 'writing', 'article_id' => $this->id
			]);
		}
	}

	function hasVerifyDoc() {
		return Document::exists(['article_id' => $this->id, 'type' => Document::VERIFY, 'outdated' => 0]);
	}

	function verifyDocs() {
		return __::select($this->documents, ['type' => Document::VERIFY]);
	}

	function lastVerifyDoc() {
		if ($this->hasVerifyDoc()) {
			return __::chain($this->documents)->select(['type' => Document::VERIFY])->last()->value();
		} else {
			return Document::create([
				'article_id' => $this->id, 'type' => Document::VERIFY
			]);
		}
	}

	//was this just assigned to the verifier?
	//compare the last updated for article to the verifier row
	function isNewToVerifier() {
		$assoc = __::find($this->user_articles, ['role_id' => Role::verify()->id, 'complete' => false]);
		$result = $assoc ? $assoc->updated_at == $this->updated_at : false;
		return $result;
	}

	function hasHadVerifierFeedback() {
		$assoc = __::find($this->user_articles, ['role_id' => Role::needsRevision()->id, 'complete' => true]);
		$result = $assoc ? true : false;
		return $result;
	}

	public function editLink() {
		if ($this->exists()) {
			$title = Title::newFromId($this->wh_article_id);
			if ($title && $title->exists()) {
				$tech_param = isArticleInCat($this,'Tech') ? '&tech_widget=1' : '';
				return trim(URL_PREFIX, "/") . $title->getEditUrl() . $tech_param;
			}
		}

		return URL_PREFIX . "index.php?title={$this->title}&action=edit";
	}

	//full check to see if the title is valid on wH
	public function titleExists() {
		if ($this->exists()) {
			$title = Title::newFromId($this->wh_article_id);
			if ($title && $title->exists()) return true;
		}
		return false;
	}

	static function redirects() {
		return Article::all(['is_redirect' => true]);
	}

	static function deletes() {
		return Article::all(['is_deleted' => true]);
	}

	function findWhTitle() {
		if ($this->wh_article_id) return;
		(new ArticleValidator($this))->findTitle();
		if ($this->is_dirty()) $this->save(false);
	}

	// CALLBACKS

	function before_destroy() {
		$con = ['conditions' => ['article_id' => $this->id]];
		UserArticle::delete_all($con);
		Event::delete_all($con);
		Note::delete_all($con);
		Document::delete_all($con);

		Event::log("Article __{{article.title}}__ was deleted by __{{currentUser.username}}__", Event::RED);
		return true;
	}

	function after_create() {
		parent::after_create();
		Event::log("Article __{{article.title}}__ was created by __{{currentUser.username}}__.");
	}

	function before_update() {
		$this->wh_article_url = URL_PREFIX . Title::newFromText($this->title)->getPartialUrl();
	}

	function after_update() {
		if (empty($this->assigned_id) && $this->state_id == Role::review()->id) {
			AssignRule::autoAssignArticle($this);
		}
	}

	function logStr() {
		return "{$this->title}::{$this->id}";
	}
}
