<?php

class Changerealname extends SpecialPage {

	public function __construct () {
		parent::__construct('Changerealname', 'changerealname');
	}


	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		if ( !$wgUser->isAllowed( 'changerealname' ) ) {
			$wgOut->permissionRequired( 'changerealname' );
			return;
		}

		if ($wgRequest->wasPosted()) {
			if ($wgRequest->getVal('target') && $wgRequest->getVal('realname')) {
				// $dbw = wfGetDB(DB_MASTER);
				$user = User::newFromName($wgRequest->getVal('target'));
				if ($user->getId() == 0) {
					$wgOut->addHTML( wfMessage('changerealname_nouser', $user->getName() )->text() );
					return;
				}
				//yeah...if you could not go straight to the database, that'd be great... [sc]
				// $oldname = $dbw->selectField( 'user', 'user_real_name', array('user_name'=>$wgRequest->getVal('target')) );
				// $dbw->update('user', array('user_real_name' => $wgRequest->getVal('realname')), array('user_name'=>$wgRequest->getVal('target')));
				
				$oldname = $user->getRealName();
				
				$user->setRealName($wgRequest->getVal('realname'));
				$user->saveSettings();
				
				$summary = wfMessage('changerealname_summary', $wgUser->getName(), $user->getName(), $oldname, $wgRequest->getVal('realname'))->text();
				$log = new LogPage( 'realname', true );
				$log->addEntry( 'realname', $user->getUserPage(), $summary );
				$wgOut->addHTML(wfMessage('changerealname_success')->text());
			}
		} else {
			$me = Title::makeTitle(NS_SPECIAL, "Changerealname");
			$wgOut->addHTML("<form method='POST' action='{$me->getFullURL()}'>
						Username: <input type='text' name='target'><br/><br/>
						New real name: <input type='text' name='realname'><br/>
						<input type='submit' value='" . wfMessage('changerealname_submit') . "'>
					</form>"
					);
		}
	}

}
