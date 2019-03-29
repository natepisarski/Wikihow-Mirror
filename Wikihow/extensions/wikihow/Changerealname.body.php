<?php

class ChangeRealName extends SpecialPage {

	public function __construct () {
		parent::__construct('ChangeRealName', 'changerealname');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( !$user->isAllowed( 'changerealname' ) ) {
			throw new PermissionsError( 'changerealname' );
			return;
		}

		if ($req->wasPosted()) {
			if ($req->getVal('target') && $req->getVal('realname')) {
				// $dbw = wfGetDB(DB_MASTER);
				$changeUser = User::newFromName($req->getVal('target'));
				if ($changeUser->getId() == 0) {
					$out->addHTML( wfMessage('changerealname_nouser', $changeUser->getName() )->text() );
					return;
				}
				//yeah...if you could not go straight to the database, that'd be great... [sc]
				// $oldname = $dbw->selectField( 'user', 'user_real_name', array('user_name'=>$req->getVal('target')) );
				// $dbw->update('user', array('user_real_name' => $req->getVal('realname')), array('user_name'=>$req->getVal('target')));

				$oldname = $changeUser->getRealName();

				$changeUser->setRealName($req->getVal('realname'));
				$changeUser->saveSettings();

				$summary = wfMessage('changerealname_summary', $user->getName(), $changeUser->getName(), $oldname, $req->getVal('realname'))->text();
				$log = new LogPage( 'realname', true );
				$log->addEntry( 'realname', $changeUser->getUserPage(), $summary );
				$out->addHTML(wfMessage('changerealname_success')->text());
			}
		} else {
			$me = Title::makeTitle(NS_SPECIAL, "ChangeRealName");
			$out->addHTML("<form method='POST' action='{$me->getFullURL()}'>
						Username: <input type='text' name='target'><br/><br/>
						New real name: <input type='text' name='realname'><br/>
						<input type='submit' value='" . wfMessage('changerealname_submit') . "'>
					</form>"
					);
		}
	}

}
