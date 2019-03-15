<?php

class AccountCreationFilter {
	public function abortNewAccount($user, $message) {
		if (preg_match("@\s\s@",$user->getName())) {
			$message = "Username cannot contain two spaces in a row.";
			return false;
		}
		return true;
	}
}
