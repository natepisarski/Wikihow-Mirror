<?
function rolesFromNotes($notes) {
	return __::chain($notes)->pluck('role')->reject('is_null')->uniq('id')->sortBy('step')->value();
}

function notesForRole($notes, ContentPortal\Role $role) {
	return array_reverse(__::filter($notes, ['role_id' => $role->id]));
}

function noteAuthor(ContentPortal\Note $note) {
	return $note->sender ? "From: {$note->sender->username}" : '';
}

function noteRecipient(ContentPortal\Note $note) {
	return $note->recipient ? "To: {$note->recipient->username}" : '';
}

function noteState(ContentPortal\Note $note) {
	return $note->prev_assignment ? $note->prev_assignment->role : $note->role;
}