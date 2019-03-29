<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class QuickTemplateWrapper extends QuickTemplate {
	function execute() {
	}
}

class LoginReminder extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'LoginReminder' );
	}

	public function execute($par) {
		$this->getOutput()->setArticleBodyOnly(true);
		if ($this->getRequest()->getVal('submit')) {
			$result = $this->mailPassword();
			if (isset($result)) {
				print json_encode($result);
			}
		} else {
			$template = new QuickTemplateWrapper();
			$template->set('header', '');
			Hooks::run( 'UserCreateForm', array( &$template ) );
			$this->displayForm($template);
		}
	}

	private function displayForm($template) {
		if ( $this->data['message'] ) {
		?>
			<div class="<?php $this->text('messagetype') ?>box">
				<?php if ( $this->data['messagetype'] == 'error' ) { ?>
					<h2><?php $this->msg('loginerror') ?>:</h2>
				<?php } ?>
				<?php $this->html('message') ?>
			</div>
			<div class="visualClear"></div>
		<?php } ?>

		<div class="modal_form">

		<form name="userlogin" method="post" action="#" onSubmit="return WH.LoginReminder.checkSubmit('wpName2', 'wpCaptchaWord', 'wpCaptchaId');">
			<div id="userloginprompt"><?= wfMessage('loginprompt')->text() ?></div>
			<table>
				<tr>
					<td class="mw-label"><label for='wpName2'><?= wfMessage('username_or_email_html')->text() ?></label></td>
					<td class="mw-input">
						<div style="position:relative">
							<input type='text' class='loginText input_med' name="wpName" id="wpName2" value="<?= urldecode($this->getRequest()->getVal('name')) ?>" size='20' />
							<div class="mw-error-bottom mw-error" id="wpName2_error" style="display:none;">
								<div class="mw-error-top">
								</div>
							</div>
							<input type="hidden" id="wpName2_showhide" />
						</div>
					</td>
				</tr>
				<tr>
					<td class="mw-label" style="vertical-align:bottom;"><label style="display:block; padding-bottom: 25px"><?= wfMessage('lr_security_label')->text() ?></label></td>
					<td class="mw-input">
						<div style="position:relative">
							<?php $template->html('header'); /* pre-table point for form plugins... */ ?>
							<div class="mw-error-bottom mw-error" id="wpCaptchaWord_error" style="display:none;">
								<div class="mw-error-top">
								</div>
							</div>
							<input type="hidden" id="wpCaptchaWord_showhide" />
						</div>
					</td>
				</tr>
				<tr>
					<td class="mw-submit"></td>
					<td style="padding-top:5px">
						<table cellpadding="0" cellspacing="0">
							<tr>
								<td>
								<input type="submit" value="<?=wfMessage('lr_reset_password_label')->text();?>" id="wpMailmypassword" class="button primary" name="wpMailmypassword" />
								</td>
								<td>

								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</form>

		</div>
		<div id="loginend"><?= wfMessage( 'loginend' )->text() ?></div>
		<div id="password-reset-dialog" style="display:none" title="<?= wfMessage('lr_password_reset')->text() ?>">
			<div style="font-size:14px">
				<?= wfMessage('loginreminder_password_reset')->text() ?><br/>
			</div>
			<br/>
			<input type="submit" value="OK" id="password-reset-ok" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' class="button button52 submit_button" style="float:right">
		</div>
		<?php

	}

	private function mailPassword() {
		global $wgAuth;

		$result = array();

		if ( !$wgAuth->allowPasswordChange() ) {
			$result['error_general'] = wfMessage( 'resetpass_forbidden' )->text();
			return $result;
		}

		# Check against blocked IPs
		# fixme -- should we not?
		$user = $this->getUser();
		if ( $user->isBlocked() ) {
			$result['error_general'] = wfMessage( 'blocked-mailpassword' )->text();
			return $result;
		}
		# Check against the rate limiter
		if ( $user->pingLimiter( 'mailpassword' ) ) {
			// Commented out By Gershon Bialer on 12/9/2013
			// because they prevented error from showing in the upgrade
			//$out->disable();
			//throw new ThrottledError;
			$result['error_general'] = "<h4>" . wfMessage('actionthrottled')->text() . "</h4>";
			$result['error_general'] .= wfMessage('actionthrottledtext')->text();
			return $result;
		}
		$name = $this->getRequest()->getVal('name');

		if ( !isset($name) || '' == $name ) {
			$result['error_username'] = wfMessage( 'noname' )->text();
			return $result;
		}
		$name = trim( $name );

		$u = null;
		// If $name looks like an email address, we look it up by email
		// address first
		$looksLikeEmail = strpos($name, '@') !== false;
		if ( $looksLikeEmail ) {
			list($u, $count) = WikihowUser::newFromEmailAddress( $name );
		}

		if ( is_null( $u ) ) {
			$u = User::newFromName( $name );
			// Show error specific to email addresses if there's no username
			// with an '@' in it either
			if ($looksLikeEmail) {
				if ($count < 1) {
					$result['error_username'] = wfMessage( 'noemail_login' )->text();
					return $result;
				} elseif ($count > 1) {
					$result['error_username'] = wfMessage( 'multipleemails_login' )->text();
					return $result;
				}
			}
		}

		if ( is_null( $u ) ) {
			$result['error_username'] = wfMessage( 'noname' )->text();
			return $result;
		}
		if ( 0 == $u->getID() ) {
			$result['error_username'] = wfMessage( 'nosuchuser', $u->getName() )->text();
			return $result;
		}

		$abortError = '';
		if ( !Hooks::run( 'AbortAccountReminder', array( $u, &$abortError ) ) ) {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::addNewAccountInternal: a hook blocked creation\n" );
			$result['error_captcha'] = $abortError;

			//had a problem with the captcha, need to load a new one
			$template = new QuickTemplateWrapper();
			$template->set('header', '');
			Hooks::run('AccountReminderNewCaptcha', array( &$template ));

			//hack since templates ECHO the data you want
			ob_start();
			$template->html('header');
			$var = ob_get_contents();
			ob_end_clean();
			//end hack

			$result['newCaptcha'] = $var;
			return $result;
		}

		# Check against password throttle
		if ( $u->isPasswordReminderThrottled() ) {
			global $wgPasswordReminderResendTime;
			# Round the time in hours to 3 d.p., in case someone is specifying minutes or seconds.
			$result['error_general'] = wfMessage( 'throttled-mailpassword', round( $wgPasswordReminderResendTime, 3 ) )->text();
			return $result;
		}

		$mailResult = $this->mailPasswordInternal( $u, true, 'passwordremindertitle', 'passwordremindertext' );
		if ( WikiError::isError( $mailResult ) ) {
			$result['error_general'] = wfMessage( 'mailerror', $mailResult->getMessage() )->text();
			return $result;
		} else {
			$result['success'] = wfMessage( 'passwordsent' )->rawParams( $u->getName() )->escaped();
			return $result;
		}
	}

	private function mailPasswordInternal( $u, $throttle = true, $emailTitle = 'passwordremindertitle', $emailText = 'passwordremindertext' ) {
		global $wgCookiePath, $wgCookieDomain, $wgCookiePrefix, $wgCookieSecure;
		global $wgCanonicalServer, $wgScript;

		if ( '' == $u->getEmail() ) {
			return new WikiError( wfMessage( 'noemail', $u->getName() )->text() );
		}

		$np = $u->randomPassword();
		$u->setNewpassword( $np, $throttle );

		setcookie( "{$wgCookiePrefix}Token", '', time() - 3600, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );

		$u->saveSettings();

		$ip = RequestContext::getMain()->getRequest()->getIP();
		if ( '' == $ip ) { $ip = '(Unknown)'; }

		$m = wfMessage( $emailText, $ip, $u->getName(), $np, $wgCanonicalServer . $wgScript )->text();
		$result = $u->sendMail( wfMessage( $emailTitle )->text(), $m, null, null, false );

		return $result;
	}

}

class LoginFacebook extends UnlistedSpecialPage {
	public function __construct() {
		global $wgHooks;
		$wgHooks['BeforeTabsLine'][] = array('LoginFacebook::topContent');
		parent::__construct( 'LoginFacebook' );
	}

	public function execute($par) {

		$this->getOutput()->setHTMLTitle('Login Via Facebook - wikiHow');

		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );
		$link = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl( 'type=signup' ) ) . '">';
		$link .= wfMessage( 'nologinlink' )->escaped();
		$link .= '</a>';

		$linkLogin = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl('type=login' ) ) . '">';
		$linkLogin .= wfMessage( 'gotaccountlink' )->escaped();
		$linkLogin .= '</a>';

		$form = "<div id='userloginForm'>

			<table border='0' width='100%'>
				<tr>
					<td style='text-align:center;'>" . wfMessage('facebook_account_login', $linkLogin, $link )->text() . "</td>
				</tr>
			</table>

		</div>";
		$this->getOutput()->addHTML($form);
		Hooks::run( 'FBLoginForm', array() );
	}

	// hooks into BeforeTabsLine
	public static function topContent() {
		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );
		$link = '<a href="' . htmlspecialchars ( $titleObj->getLocalUrl( 'type=signup' ) ) . '">';
		$link .= wfMessage( 'nologinlink' )->escaped();
		$link .= '</a>';
		RequestContext::getMain()->getOutput()->addHTML( '<p style="padding:0 27px 15px 23px"><span style="font-size: 28px">' . wfMessage( 'Loginfacebook' ) . '</span> <span style="float:right;">' . wfMessage( 'nologin' )->rawParams( $link )->escaped() . '</span></p>' );

		return true;
	}
}

class LoginCheck extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'LoginCheck' );
	}

	public function execute($par) {
		$this->getOutput()->setArticleBodyOnly(true);

		$username = $this->getRequest()->getVal('username');
		if (empty($username)) {
			echo json_encode(array('error' => wfMessage('mandatory_field_is_empty')->text()));
		} elseif (isset($username)) {
			echo json_encode(self::checkUsername($username));
		}
	}

	private static function checkUsername($username) {
		$dbr = wfGetDB(DB_REPLICA);
		if (WikihowUser::usernameTaken($dbr, $username)) {
			$pad = function($text) {
				return "<a class='username-suggestion'>$text</a>";
			};
			$suggestions = array_map($pad, WikihowUser::getUsernameSuggestions($dbr, $username));

			$msg = empty($suggestions)
				? wfMessage('userexists')->text()
				: wfMessage('userexists_with_suggestions', implode(' or ', $suggestions))->text();
			return array('error' => $msg);
		}
		return array('success' => '1');
	}

}
