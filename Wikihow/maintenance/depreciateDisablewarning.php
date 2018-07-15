<?

/**
 * One time use script to port all of the current values
 * for the wH preference 'disablewarning' to the new
 * core preference 'useeditwarning'.
 */

require_once('commandLine.inc');

$users = DatabaseHelper::batchSelect('user', array('user_id'));

foreach($users as $user) {
	$user = User::newFromId($user->user_id);

	$disableWarning = $user->getOption('disablewarning', "0");
	$useEditWarning = $user->getOption('useeditwarning', "1");

	$hasChanged = false;
	if($disableWarning == "0" && $useEditWarning == "0") {
		$user->setOption('useeditwarning', 1);
		$hasChanged = true;
	}
	else if ($disableWarning == "1" && $useEditWarning == "1") {
		$user->setOption('useeditwarning', 0);
		$hasChanged = true;
	}

	if($hasChanged) {
		$user->saveSettings();
	}
}