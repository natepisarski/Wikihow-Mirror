<?php

class UserLoginBox extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('UserLoginBox');
	}

	/**
	 * getLogin()
	 * -----------------
	 * returns html of the login box used for things like desktop nav pulldown & desktop homepage
	 *
	 * - $isHead (t/f) = is the desktop nav
	 * - $isLogin (t/f)
	 *			true = assumes user is logging in w/ their account (alt link for sign up)
	 *			false = assumes user needs to create an account (alt link for log in w/ acct)
	 * - $returnto (string) = page to which to return the user after login/signup
	 */
	public static function getLogin($isHead = false, $isLogin = true, $returnto = '') {
		global $wgSecureLogin;
		$ctx = RequestContext::getMain();

		$userlogin_link = '/'.Title::newFromText('UserLogin', NS_SPECIAL);
		$wH_login_link = '#wh-dialog-login';
		// Trevor 2020/04/28 - No returnto for signup
		$wH_signup_link = wfAppendQuery($userlogin_link, ['type' => 'signup']);

		$privacyLink = "";
		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'suffix' => $isHead ? '_head' : '',
			'return_to' => $returnto,
			'hdr_txt' => $isLogin ? wfMessage('ulb_login')->text() : wfMessage('ulb_joinwh')->text(),
			'wH_button_link' => $isLogin ? $wH_login_link : $wH_signup_link,
			'wh_txt' => $isLogin ? wfMessage('ulb_whacct')->text() : wfMessage('ulb_email')->text(),
			'is_login' => $isLogin ? 'ulb_login' : 'ulb_signup',
			'wH_text_link' => $isLogin ? $wH_signup_link : $wH_login_link,
			'privacy_link' => $privacyLink,
			'bottom_txt_1' => $isLogin ? wfMessage('ulb_nologin')->text() : wfMessage('ulb_haveacct')->text(),
			'bottom_txt_2' => $isLogin ? wfMessage('nologinlink')->text() : wfMessage('ulb_login')->text()
		));
		$html = $tmpl->execute('userloginbox.tmpl.php');

		$ctx->getOutput()->addModules('ext.wikihow.userloginbox');

		return $html;
	}

	public function execute($par) {
		$this->getOutput()->setArticleBodyOnly(true);
	}
}
