<?php
namespace ContentPortal;
use ActiveRecord\DateTime;
use __;

class Assignment {
	public $article;
	public $currentStep;
	public $allAssignments;
	public $prevStep;
	public $nextStep;

	public $currentAssignment;
	public $prevAssignment;
	public $nextAssignment;

	public $currentUser;
	public $prevUser;
	public $nextUser;

	function __construct(Article $article) {
		$this->article = $article;
		$this->fetch();
	}

	static function build(Article $article) {
		return new Assignment($article);
	}

	function fetch() {
		$this->article        = $this->article->isPersisted() ? $this->article->reload() : $this->article;
		$this->allAssignments = $this->article->isPersisted() ? UserArticle::all(['article_id' => $this->article->id]) : [];

		$this->currentStep       = $this->article->state;
		$this->currentAssignment = __::find($this->allAssignments, ['role_id' => $this->article->state_id]);

		$this->findPrevious();

		$this->nextStep          = $this->currentStep ? $this->article->state->nextStep() : null;
		$this->nextAssignment    = $this->nextStep ? __::find($this->allAssignments, ['role_id' => $this->nextStep->id]) : null;

		$this->prevUser          = $this->prevAssignment ? $this->prevAssignment->user : null;
		$this->currentUser       = $this->currentAssignment ? $this->currentAssignment->user : null;
		$this->nextUser          = $this->nextAssignment ? $this->nextAssignment->user : null;

		return $this;
	}

	function findPrevious() {
		if ($this->article->isBlocked()) {
			$note = $this->article->blockingQuestion();
			$this->prevAssignment = $note->prev_assignment;
			$this->prevStep = $this->prevAssignment->role;
			if ($this->prevAssignment) return;
		}

		$this->prevStep = $this->currentStep ? $this->article->state->prevStep() : null;
		$this->prevAssignment = $this->prevStep ? __::find($this->allAssignments, ['role_id' => $this->prevStep->id]) : null;
	}

	function create(User $user, $message=null) {
		// need to check if user is compatible

		$vals = [
			'article_id' => $this->article->id,
			'user_id'    => $user->id,
			'role_id'    => $this->article->state_id
		];

		if ($this->currentAssignment)
			$this->currentAssignment->update_attributes($vals);
		else
			UserArticle::create($vals);

		Event::log(
			"User __{{currentUser.username}}__ assigned __{{user.username}}__ as __{{role.title}}__ for __{{article.title}}__.",
			Event::BLUE, ['user' => $user, 'role' => $this->currentStep]
		);

		if ($message) {
			Note::create([
				'user_id' => currentUser()->id,
				'role_id' => $this->article->state_id,
				'article_id' => $this->article->id,
				'message' => $message,
				'type' => Note::INSTRUCT
			]);
		}

		$this->article->update_attribute('assigned_id', $user->id);
		return $this->fetch();
	}

	function done($message=null) {

		if ($message) {
			$recip_id = $this->article->prevAssignment() ? $this->article->prevAssignment()->user_id : null;

			Note::create([
				'user_id' => currentUser()->id,
				'role_id' => $this->article->state_id,
				'article_id' => $this->article->id,
				'message' => $message,
				'type' => Note::KUDOS,
				'recipient_id' =>  $recip_id
			]);
		}

		if ($this->currentAssignment){
			Event::log(
				"User __{{currentUser.username}}__ finished __{{role.present_tense}}__ for __{{article.title}}__.", Event::BLUE,
				['role' => $this->currentAssignment->role]
			);
			$this->currentAssignment->update_attributes([
				'complete' => true, 'completed_at' => new DateTime()
			]);
		}
		$this->article->update_attributes([
			"state_id"    => $this->nextStep ? $this->nextStep->id : null,
			'assigned_id' => ($this->nextAssignment && !$this->nextUser->disabled) ? $this->nextUser->id : null,
			"rejected"    => false
		]);
		if ($this->prevAssignment) {
			$this->prevAssignment->update_attributes([
				'complete'    => true,
				'approved'    => true,
				'approved_at' => new DateTime()
			]);
		}
		$this->article->findWhTitle();
		return $this->fetch();
	}

	function reject($message=null) {

		if ($this->prevUser) {
			Event::log(
				"User __{{currentUser.username}}__ sent __{{article.title}}__ back to __{{prevUser.username}}__ for __{{prevStep.present_tense}}__.", Event::RED,
				['prevUser' => $this->prevUser, 'prevStep' => $this->prevStep]
			);
		} else {
			Event::log("User __{{currentUser.username}}__ sent __{{article.title}}__ back for __{{prevStep.present_tense}}__.", Event::RED,
				['prevStep' => $this->prevStep]
			);
		}

		if ($message) {
			$note = Note::create([
				'message'    => $message,
				'user_id'    => currentUser()->id,
				'role_id'    => $this->prevStep->id,
				'type'       => $this->article->isBlocked() ? Note::BLOCKING_RESPONSE : Note::INFO,
				'note_id'    => $this->article->isBlocked() ? $this->article->blockingQuestion()->id : null,
				'article_id' => $this->article->id
			]);
		}

		$this->article->update_attributes([
			'rejected'    => true,
			'state_id'    => $this->prevStep->id,
			'assigned_id' => (is_null($this->prevUser) || $this->prevUser->disabled) ? null : $this->prevUser->id
		]);

		if ($this->prevAssignment){
			$this->prevAssignment->update_attributes([
				'complete'     => false,
				'approved'     => false,
				'completed_at' => null,
				'approved_at'  => null,
			]);
		}

		return $this->fetch();
	}

	function delete() {
		if ($this->currentAssignment) {
			$this->currentAssignment->delete();

			Event::log(
			"__{{user.username}}__ was removed from the role of __{{role.title}}__ from __{{article.title}}__ by __{{currentUser.username}}__",
				Event::RED, ['user' => $this->currentUser, 'role' => $this->currentStep]
			);

			$this->article->update_attribute('assigned_id', null);
		}
		return $this->fetch();
	}
}
