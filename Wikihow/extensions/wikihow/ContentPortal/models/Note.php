<?php
namespace ContentPortal;

class Note extends AppModel {

	const KUDOS = "kudos";
	const INFO = "info";
	const BLOCKING = "blocking";
	const BLOCKING_RESPONSE = 'blocking_response';
	const INSTRUCT = 'instruction';

	static $table_name = 'cf_notes';
	static $belongs_to = [
		'article', 'role',
		['sender', 'class' => 'User', 'foreign_key' => 'user_id'],
		['prev_assignment', 'class' => 'UserArticle', 'foreign_key' => 'prev_assign_id'],
		['recipient', 'class' => 'User', 'foreign_key' => 'recipient_id']
	];
	static $has_many = ['notes'];

	function isKudos() {
		return $this->type == self::KUDOS;
	}

	function isResponse() {
		return $this->type == self::BLOCKING_RESPONSE;
	}

	// CALLBACKS

	function before_create() {
		$this->message = htmlentities($this->message);

		if ($this->type == self::BLOCKING) {
			$assignment = Assignment::build($this->article)->currentAssignment;

			$this->prev_assign_id = $assignment ? $assignment->id : null;
			$this->role_id = Role::blockingQuestion()->id;

			$this->article->update_attributes([
				'assigned_id' => null,
				'state_id' => Role::blockingQuestion()->id,
			]);

			Event::log("__{{currentUser.username}}__ asked a question about __{{article.title}}__");
		}
	}

	function after_create() {
		if ($this->type  == Note::BLOCKING) {
			//run the auto-assign rules
			AssignRule::autoAssignQuestion($this);
		}
	}

	function logStr() {
		return "{$this->article->logStr()} / {$this->type}::{$this->id}";
	}

}
