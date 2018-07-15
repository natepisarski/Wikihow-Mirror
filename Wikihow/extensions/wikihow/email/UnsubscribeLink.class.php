<?php

/**
 *
 * @author Lojjik Braughler (SudoKing)
 * @uses Generates a link that allows a user to unsubscribe from wikiHow e-mails based on a user ID
 * @example $link = UnsubscribeLink::newFromEmail("wiki@wikihow.com");
 *          echo $link->getLink();
 * @example $link = UnsubscribeLink::newFromId($wgUser->getId());
 *          echo $link->getLink();
 */
class UnsubscribeLink {
	var $id;
	var $hasAccount;
	var $token;

	public function __construct( $id, $account ) {
		$this->token = new UnsubscribeToken();
		$this->id = $id;
		$this->hasAccount = $account;
	}

	/**
	 * Factory method used for creating this class based on a MediaWiki user ID
	 *
	 * @param $user_id -
	 *        	MediaWiki user ID
	 */
	public static function newFromID( $user_id ) {
		$ul = new UnsubscribeLink( $user_id, true );
		return $ul;
	}

	/**
	 * Factory method used for creating this class based on an e-mail address
	 *
	 * @param $email -
	 *        	An email address
	 */
	public static function newFromEmail( $email ) {
		$ul = new UnsubscribeLink( $email, false );
		return $ul;
	}

	/**
	 * Once factory is used to create an instance of UnsubscribeLink, use this method to generate
	 * the link
	 *
	 * @example echo $link->getLink()
	 */
	public function getLink() {
		global $wgCanonicalServer;
		
		$link_base = $wgCanonicalServer . '/Special:Unsubscribe';
		$link_params = "?" . (($this->hasAccount == true) ? "uid=" : "email=") . $this->id .
			 "&token=" . $this->token->generateToken( $this->id );
		
		return $link_base . $link_params;
	}
}
