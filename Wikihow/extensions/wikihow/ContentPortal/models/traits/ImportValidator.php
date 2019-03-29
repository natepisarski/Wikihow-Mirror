<?php
namespace ContentPortal;

trait ImportValidator {

	// todo: use the one already in category model
	function findOrCreateCategory($title) {
		$title = ucwords($title);
		$existing = Category::find_by_title($title);
		return $existing ? $existing : Category::create(['title' => $title]);
	}

	function findUser($username, $article) {
		// don't run with blank
		if (is_null($username)) return null;

		$username = User::convertFromUrl($username);

		$user = User::find('first', [
			'conditions' => ['username' => $username],
			'include' => ['user_roles']
		]);

		// todo: this needs to fail gracefully and add error msg if cannot create or find user
		if (is_null($user)) {
			return null;
			// $user = User::create([
			// 	'username'    => $username,
			// 	'category_id' => $article->category_id
			// ]);
		}

		if ($user->is_valid() && !$user->hasRoleId($article->state_id)) {
			UserRole::create([
				'role_id' => $article->state_id,
				'user_id' => $user->id
			]);
		}

		return $user->is_valid() ? $user : null;
	}

	function validateFile() {
		if (is_null($this->file)) {
			$this->addError('There was no file uploaded.');
		} elseif ($this->file['type'] !== 'text/csv') {
			$this->addError('Upload must be a CSV file');
		}

		if (isset($this->file['code'])) {
			switch ($this->file['code']) {
				case UPLOAD_ERR_INI_SIZE:
					$this->addError("The uploaded file exceeds the upload_max_filesize directive in php.ini");
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$this->addError("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form");
					break;
				case UPLOAD_ERR_PARTIAL:
					$this->addError("The uploaded file was only partially uploaded");
					break;
				case UPLOAD_ERR_NO_FILE:
					$this->addError("No file was uploaded");
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					$this->addError("Missing a temporary folder");
					break;
				case UPLOAD_ERR_CANT_WRITE:
					$this->addError("Failed to write file to disk");
					break;
				case UPLOAD_ERR_EXTENSION:
					$this->addError("File upload stopped by extension");
					break;
			}
		}
	}

	function isValid() {
		return empty($this->errors);
	}

	function formatKey($key) {
		$key = strtolower(str_replace(' ', '_', $key));
		$key = preg_replace('/[^a-z_]/', '', $key);
		return trim(preg_replace('/_{2,}/', '_', $key), '_');
	}

	function addError($error) {
		if (!array_key_exists('file', $this->errors)) {
			$this->errors['file'] = [];
		}
		array_push($this->errors['file'], $error);
	}
}
