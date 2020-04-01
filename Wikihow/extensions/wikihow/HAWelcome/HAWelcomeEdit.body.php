<?php

class HAWelcomeEdit extends UnlistedSpecialPage {

	private	$mTitle;

	public function __construct() {
		parent::__construct( 'HAWelcomeEdit', 'HAWelcomeEdit', null, false );
	}

	public function execute( $subpage ) {
		$user = $this->getUser();
		$req = $this->getRequest();


		$this->setHeaders();
		$this->mTitle = SpecialPage::getTitleFor( 'HAWelcomeEdit' );

		if ( $this->isRestricted() && !$this->userCanExecute( $user ) ) {
			$this->displayRestrictionError();
			return;
		}

		if ( $req->wasPosted() ) {
			$this->doPost();
		}

		$this->showCurrent();
		$this->showChange();

	}

	private function showCurrent(){
		global $wgMemc;

		$out = $this->getOutput();

		$out->addHTML("<fieldset>\n");
		$out->addHTML("<legend>CurrentValue</legend>\n");
		$sysopId = $wgMemc->get( wfMemcKey( "last-sysop-id" ) );
		if ( $sysopId ) {
			$this->mSysop = User::newFromId( $sysopId );
			$sysopName = wfEscapeWikiText( $this->mSysop->getName() );
			$groups = $this->mSysop->getEffectiveGroups();
			$out->addHTML("ID: <code>".$sysopId."</code><br/>");
			$out->addHTML("Name: <code>".$sysopName."</code><br/>");
			$out->addHTML("Groups: <code>". implode(", ", $groups) ."</code><br/>");

			$action_url = $this->mTitle->getFullURL();
			$out->addHTML("<form action='{$action_url}' method='post'>\n");
			$out->addHTML("<input type='hidden' name='method' value='clear' />\n");
			$out->addHTML("<input type='submit' value='clear' />\n");
			$out->addHTML("</form>\n");
		}
		else {
			$out->addHTML("<i>n/a</i>");
		}
		$out->addHTML("</fieldset>\n");
	}

	private function showChange(){
		$out = $this->getOutput();
		$req = $this->getRequest();

		$out->addHTML("<fieldset>\n");
		$out->addHTML("<legend>ChangeValue</legend>\n");

		$action_url = $this->mTitle->getFullURL();
		$out->addHTML("<form action='{$action_url}' method='post'>\n");
		$out->addHTML("<input type='hidden' name='method' value='by_id' />\n");
		$out->addHTML("<label for='new_sysop_id'>Change by ID</label><br/>\n");
		$out->addHTML("<input type='text' name='new_sysop_id' />\n");
		$out->addHTML("<input type='submit' value='change' />\n");
		$out->addHTML("</form>\n");

		$out->addHTML("<hr />\n");

		$out->addHTML("<form action='{$action_url}' method='post'>\n");
		$out->addHTML("<input type='hidden' name='method' value='by_name'>\n");
		$out->addHTML("<label for='new_sysop_text'>Change by Name</label><br/>\n");
		$out->addHTML("<input type='text' name='new_sysop_text' />\n");
		$out->addHTML("<input type='submit' value='change' />\n");
		$out->addHTML("</form>\n");

		$out->addHTML("</fieldset>\n");
	}

	private function doPost(){
		global $wgMemc;

		$out = $this->getOutput();
		$req = $this->getRequest();

		$method = $req->getVal('method');

		if ( $method == 'by_id' ) {
			$new_id = $req->getInt('new_sysop_id');
			if ( empty($new_id) || $new_id < 0 ) {
				$out->addHTML("bad input");
				return false;
			}
			if ( !User::whois($new_id) ) {
				$out->addHTML("no user with that id");
				return false;
			}

			$wgMemc->set( wfMemcKey( "last-sysop-id" ), $new_id, 86400 );
			$out->addHTML("new value saved");
		}
		elseif( $method == 'by_name' ) {
			$new_text = $req->getText('new_sysop_text');
			if ( empty($new_text) ) {
				$out->addHTML("bad input");
				return false;
			}
			$new_id = User::idFromName($new_text);
			if ( empty($new_id) ) {
				$out->addHTML("name not found as user");
				return false;
			}

			$wgMemc->set( wfMemcKey( "last-sysop-id" ), $new_id, 86400 );
			$out->addHTML("new value saved");
		}
		elseif( $method == 'clear' ) {
			$wgMemc->delete( wfMemcKey( "last-sysop-id" ) );
			$out->addHTML("cleared");
		}
		else {
			$out->addHTML( "unknown method [{$method}] used to POST<br/>\n");
		}
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
