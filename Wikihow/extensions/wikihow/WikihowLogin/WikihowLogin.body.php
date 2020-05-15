<?php

use MediaWiki\Auth\AuthManager;
use MediaWiki\Logger\LoggerFactory;

class WikihowLogin {

	public function isMobileCapable() {
		return true;
	}

	public static function onSpecialPage_initList( &$list ) {
		$list['Userlogin'] = 'WikihowUserLogin';
		$list['CreateAccount'] = 'WikihowCreateAccount';
	}

	public static function onAuthChangeFormFields( $requests, $fieldInfo, &$formDescriptor, $action ) {
		$title = RequestContext::getMain()->getTitle();

		if ($title->inNamespace(NS_SPECIAL)) {
			if ($title->getText() == 'UserLogin')
				WikihowUserLogin::changeFormFields( $formDescriptor);
			elseif ($title->getText() == 'CreateAccount')
				WikihowCreateAccount::changeFormFields( $formDescriptor );
		}
	}

	public static function renderTemplate(string $template, array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		//add the alt login options
		$alt_login_vars = [
			'alt_logins' => $loader->load('alt_logins'),
			'aria_facebook_login' => wfMessage('aria_facebook_login')->showIfExists(),
			'ulb-btn-fb' => wfMessage('ulb-btn-fb')->text(),
			'ulb-btn-loading' => wfMessage('ulb-btn-loading')->text(),
			'aria_google_login' => wfMessage('aria_google_login')->showIfExists(),
			'ulb-btn-gplus' => wfMessage('ulb-btn-gplus')->text(),
			'show_civic' => CivicLogin::isEnabled(),
			'aria_civic_login' => wfMessage('aria_civic_login')->showIfExists(),
			'ulb-btn-civic' => wfMessage('ulb-btn-civic')->text()
		];

		$vars = array_merge($vars, $alt_login_vars);

		return $m->render($template, $vars);
	}


	private static $BAD_USER_ERRORS = array('noname','noname-mobile','userexists','userexists-mobile','createaccount-hook-aborted');
	private static $BAD_PASSWORD_ERRORS = array('badretype','badretype-mobile','passwordtooshort','passwordtooshort-mobile','password-name-match','password-login-forbidden');
	private static $BAD_EMAIL_ERRORS = array('noemailtitle','invalidemailaddress');
	private static $BAD_CAPTCHA_ERRORS = array('captcha-createaccount-fail');

	public static function topContent($template, $login_type) {
		echo '<p class="wh_block"><span class="login_top_link">';
			if ( $template->haveData( 'createOrLoginHref' ) ) {
				$link_href = $template->get( 'createOrLoginHref' );

				if ($login_type == 'create') {
					$header_text = wfMessage('createaccount')->plain();
					if (!$template->data['loggedin']) {
						$linkq = wfMessage('gotaccount')->plain();
						$link_text = wfMessage('gotaccountlink')->plain();
					}
				}
				else {
					$header_text = wfMessage('login')->plain();
					$linkq = wfMessage('nologin')->plain();
					$link_text = wfMessage('nologinlink')->plain();

					//new users never get returned to the home page
					$homepage = str_replace(' ','-',wfMessage('mainpage')->inContentLanguage()->text());
					$link_href = preg_replace('/&returnto='.$homepage.'/i','',$link_href);
				}
				echo $linkq.' <a href="'.htmlspecialchars($link_href).'">'.$link_text.'</a>';
			}
		echo  '</span><span class="login_headline">' . $header_text . '</span></p>';
	}

	public static function CustomSideBar(&$result) {
		$result = true;
		return $result;
	}

/* reuben, unused May 2020
	function generateErrorList($errorArray) {
		$errorlist = array();
		foreach ($errorArray as $error) {
			// We determine where the message goes
			// AbortUserLogin stuff is a raw message, and so we consider all raw messages
			if ($error && is_object($error) && get_class($error) == 'RawMessage') {
				if (preg_match("@confirmation code@", $error->parse(), $matches)) {
					$errorlist['captcha'][] = $error;
				}
				else {
					$errorlist['username'][] = $error;
				}
			}
			elseif(is_array($error)) {
				if (in_array($error[0], self::$BAD_USER_ERRORS)) {
					if (!isset($errorlist['username'])) {
						$errorlist['username'] = array();
					}
					$errorlist['username'][] = $error;
				}
				elseif(in_array($error[0], self::$BAD_PASSWORD_ERRORS)) {
					if (!isset($errorlist['password'])) {
						$errorlist['password'] = array();
					}
					$errorlist['password'][] = $error;
				}
				elseif(in_array($error[0], self::$BAD_EMAIL_ERRORS)) {
					if (!isset($errorlist['email'])) {
						$errorlist['email'] = array();
					}
					$errorlist['email'][] = $error;
				}
				elseif(in_array($error[0], self::$BAD_CAPTCHA_ERRORS)) {
					if (!isset($errorlist['captcha'])) {
						$errorlist['captcha'] = array();
					}
					$errorlist['captcha'][] = $error;
				}
			}
		}
		return $errorlist;
	}
*/

	public static function onMobilePreRenderPreContent( &$data ) {
		if (RequestContext::getMain()->getUser()->isLoggedIn()) return;

		$this_page = (string)$data['titletext'];
		$login_page = (string)SpecialPage::getTitleFor('Userlogin')->getText();
		$signup_page = (string)SpecialPage::getTitleFor('CreateAccount')->getText();

		if ($this_page != $login_page && $this_page != $signup_page) return;

		if ($this_page == $login_page) {
			$prompt = wfMessage('nologin')->text();
			$link = SpecialPage::getTitleFor('CreateAccount');
			$link_text = wfMessage('nologinlink')->text();
		}
		elseif ($this_page == $signup_page) {
			$prompt = wfMessage('gotaccount')->text();
			$link = SpecialPage::getTitleFor('Userlogin');
			$link_text = wfMessage('gotaccountlink')->text();
		}

		$topLink = $prompt.' '.Linker::link( $link, $link_text );
		$data['prebodytext'] .= Html::rawElement('div', ['class' => 'login_top_link'], $topLink);
	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$title = $out->getTitle();

		if ($title && $title->inNamespace(NS_SPECIAL)) {
			if (
				(string)$title == (string)SpecialPage::getTitleFor('Userlogout') ||
				(string)$title == (string)SpecialPage::getTitleFor('PasswordReset')
			) {
				$out->addModuleStyles(['ext.wikihow.login_responsive_styles']);
			}
		}
	}
}

class WikihowUserLogin extends SpecialUserLogin {

	public function isMobileCapable() {
		return true;
	}

	protected function getPageHtml( $form ): string {
		$out = $this->getOutput();

		if (Misc::doResponsive( $this )) {
			$out->addModuleStyles('ext.wikihow.login_responsive_styles');
			$out->addModules('mobile.wikihow.login');
		}
		else {
			$out->addModuleStyles('ext.wikihow.loginpage_styles');
		}

		$vars = $this->getVars($form);
		return WikihowLogin::renderTemplate('wikihow_login.mustache', $vars);
	}

	protected function getVars(string $form): array {
		return [
			'loginor' => wfMessage('loginor')->text(),
			'form' => $form,
			'alt_login_header' => wfMessage('log_in_via')->text(),
			'mobile_tabs' => UserLoginAndCreateTemplate::getMobileTabs(false)
		];
	}

	public static function changeFormFields( &$formDescriptor ) {
		//no username label
		unset($formDescriptor['username']['label-raw']);

		//no password label
		unset($formDescriptor['password']['label-message']);

		//no help link
		unset($formDescriptor['linkcontainer']);

		//no login/create link (we're putting it elsewhere)
		unset($formDescriptor['createOrLogin']);

		//update message for rememberMe
		if (isset($formDescriptor['rememberMe']))
			$formDescriptor['rememberMe']['label-message'] = wfMessage('rememberme');

		//the passwordReset is like school on Saturday...no class
		unset($formDescriptor['passwordReset']['cssclass']);

		//add our cookie message
		$formDescriptor['userloginprompt'] = [
			'type' => 'info',
			'cssclass' => 'userloginprompt',
			'default' => wfMessage('loginprompt')->text(),
			'weight' => 250
		];
	}
}

class WikihowCreateAccount extends SpecialCreateAccount {

	public function isMobileCapable() {
		return true;
	}

	protected function getPageHtml( $form ): string {
		global $wgHooks;
		$wgHooks['CustomSideBar'][] = ['WikihowLogin::CustomSideBar'];

		$out = $this->getOutput();

		if (Misc::doResponsive( $this )) {
			$out->addModuleStyles('ext.wikihow.login_responsive_styles');
			$out->addModules('mobile.wikihow.login');
		}
		else {
			$out->addModuleStyles('ext.wikihow.loginpage_styles');
		}

		$out->getSkin()->addWidget(wfMessage('signupreasons')->text(), 'usercreate');
		$vars = $this->getVars($form);

		if ($this->getUser()->isLoggedIn()) {
			$returnto = $this->getRequest()->getVal('returnto','');
			$return_title = $returnto != '' ? Title::newFromText($returnto) : Title::newMainPage();
			$return_link = $return_title ? Linker::link( $return_title ) : '';

			$vars = array_merge($vars, [
				'alreadysignedin' => wfMessage('alreadysignedin', 'Special:UserLogout')->parse(),
				'return_link' => $return_link,
				'returnto' => wfMessage('returnto', $return_link)->text()
			]);
		}

		return WikihowLogin::renderTemplate('wikihow_create_account.mustache', $vars);
	}

	protected function getVars(string $form): array {
		return [
			'loginor' => wfMessage('loginor')->text(),
			'or_create_an_account' => wfMessage('or_create_an_account')->text(),
			'form' => $form,
			'alt_login_header' => wfMessage('sign_up_with')->text(),
			'mobile_tabs' => UserLoginAndCreateTemplate::getMobileTabs(true)
		];
	}

	public static function changeFormFields( &$formDescriptor ) {
		//no placeholders
		unset($formDescriptor['username']['placeholder-message']);
		unset($formDescriptor['password']['placeholder-message']);
		unset($formDescriptor['retype']['placeholder-message']);
		unset($formDescriptor['email']['placeholder-message']);

		//add real name checkbox
		$formDescriptor['real_name_check'] = [
			'type' => 'check',
			'id' => 'wpUseRealNameAsDisplay',
			'name' => 'wpUseRealNameAsDisplay',
			'label-message' => 'user_real_name_display'
		];

		//add rememberMe checkbox
		$formDescriptor['rememberMe'] = [
			'type' => 'check',
			'id' => 'wpRemember',
			'name' => 'wpRemember',
			'label-message' => 'rememberme'
		];

		//add final thoughts
		$formDescriptor['captcha_fineprint'] = [
			'type' => 'info',
			'cssclass' => 'captcha_fineprint',
			'default' => wfMessage('fancycaptcha-createaccount')->parse(),
			'raw' => true,
			'weight' => 250
		];

		//add weights
		$formDescriptor['username']['weight'] = 1;
		$formDescriptor['real_name_check']['weight'] = 2;
		$formDescriptor['password']['weight'] = 3;
		$formDescriptor['retype']['weight'] = 4;
		$formDescriptor['email']['weight'] = 5;
		$formDescriptor['realname']['weight'] = 6;
		// These captcha* form fields are only available to anons; logged
		// in users can visit this page, and we don't want to show them
		// a Mediawiki exception if they do.
		if ( RequestContext::getMain()->getUser()->isAnon() ) {
			$formDescriptor['captchaId']['weight'] = 7;
			$formDescriptor['captchaInfo']['weight'] = 8;
			$formDescriptor['captchaWord']['weight'] = 9;
		}
		$formDescriptor['rememberMe']['weight'] = 10;

	}
}
