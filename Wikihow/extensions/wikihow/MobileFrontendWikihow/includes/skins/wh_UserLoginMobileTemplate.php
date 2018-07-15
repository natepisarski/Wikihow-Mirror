<?php
/**
 * Provides a custom login form for mobile devices
 */
class UserLoginMobileTemplate extends UserLoginAndCreateTemplate {
	protected $actionMessages = array(
		'watch' => 'mobile-frontend-watchlist-login-action',
		'edit' => 'mobile-frontend-edit-login-action',
		'' => 'mobile-frontend-generic-login-action-wh',
	);
	protected $pageMessages = array(
		'Uploads' => 'mobile-frontend-donate-image-login-action',
		'Watchlist' => 'mobile-frontend-watchlist-login-action',
	);

	/**
	 * @TODO refactor this into parent template
	 */
	public function execute() {
		global $wgLanguageCode;

		$action = $this->data['action'];
		$token = $this->data['token'];
		$watchArticle = $this->getArticleTitleToWatch();
		$stickHTTPS = ( $this->doStickHTTPS() ) ? Html::input( 'wpStickHTTPS', 'true', 'hidden' ) : '';
		$username = ( strlen( $this->data['name'] ) ) ? $this->data['name'] : null;

		// @TODO make sure this also includes returnto and returntoquery from the request
		$query = array(
			'type' => 'signup',
		);
		// Security: $action is already filtered by SpecialUserLogin
		$actionQuery = wfCgiToArray( $action );
		if ( isset( $actionQuery['returnto'] ) ) {
			$query['returnto'] = $actionQuery['returnto'];
		}
		if ( isset( $actionQuery['returntoquery'] ) ) {
			$query['returntoquery'] = $actionQuery['returntoquery'];
			// Allow us to distinguish sign ups from the left nav to logins. This allows us to apply story 1402 A/B test
			if ( $query['returntoquery'] === 'welcome=yes' ) {
				$query['returntoquery'] = 'campaign=leftNavSignup';
			}
		}
		// For Extension:Campaigns
		$campaign = $this->getSkin()->getRequest()->getText( 'campaign' );
		if ( $campaign ) {
			$query['campaign'] = $campaign;
		}

		//error(s)?
		$form_errs = array();
		$message = $this->data['message'];
		$messageType = $this->data['messagetype'];
		if ( $message && $messageType == 'error' ) {

			//basic error (will target in a second)
			$errMsg = Html::openElement( 'div', array( 'class' => 'login_error_msg' ) ) .
						$message . Html::closeElement( 'div' );

			if ($this->data['errorlist']) {
				foreach ($this->data['errorlist'] as $key => $errType) {
					$form_errs[$errType] = $errMsg;
					$form_errs[$errType.'_class'] = 'login_error';
				}
			}
		}

		$loginsignup_link = Linker::link( SpecialPage::getTitleFor( 'Userlogin' ),
			wfMessage( 'mobile-frontend-main-menu-account-create' )->text(),
			array(), $query );

		$forgotPass = Linker::link( SpecialPage::getTitleFor( 'PasswordReset' ),
						wfMessage( 'forgot_pwd' )->parse() );

		$login = Html::openElement( 'div', array( 'id' => 'mw-mf-login', 'class' => 'content' ) );
		$civicLoginHtml = class_exists('CivicLogin') && CivicLogin::isEnabled() ?
			'<div id="civicButton" class="social_login_button civic_button">
				<span class="social_login_button_icon"></span>
				<span class="social_login_button_text">Civic</span>
			</div>' :
			'';
		$socialLogin =
'
<div class="section-title">' . wfMessage('mobile-frontend-log-in-via')->text() . '</div>
<div id="social-login-form" class="user-login-social" data-return-to="' . htmlspecialchars($query['returnto']) . '">
	<div id="facebookButton" class="social_login_button facebook_button">
		<span class="social_login_button_icon"></span>
		<span class="social_login_button_text">Facebook</span>
	</div>
	<div id="googleButton" class="social_login_button google_button">
		<span class="social_login_button_icon"></span>
		<span class="social_login_button_text">Google</span>
	</div>
'
. $civicLoginHtml .
'
</div>
<div class="section-title">' . wfMessage('mobile-frontend-or-log-in-here')->text() . '</div>
';

		$form =
			$this->renderGuiderMessage($loginsignup_link) .

			Html::openElement( 'form',
				array( 'name' => 'userlogin',
					'class' => 'user-login',
					'method' => 'post',
					'action' => $action ) ) .
				$socialLogin .
			Html::openElement( 'div', array(
				'class' => 'inputs-box',
			) ) .
			Html::openElement( 'div', array(
				'class' => array('input-icon',$form_errs['username_class']),
			) ) .
			Html::element( 'div', array(
				'id' => 'input-icon-username',
			) ) .
			Html::closeElement( 'div' ) .
			Html::input( 'wpName', $username, 'text',
				array( 'class' => array('loginText',$form_errs['username_class']),
					'placeholder' => wfMessage( 'mobile-frontend-username-placeholder' )->text(),
					'id' => 'wpName1',
					'tabindex' => '1',
					'size' => '20',
					'required' ) ) .
			(($form_errs['username']) ? $form_errs['username'] : '').
			Html::openElement( 'div', array(
				'class' => array('input-icon',$form_errs['password_class']),
			) ) .
			Html::element( 'div', array(
				'id' => 'input-icon-pwd',
			) ) .
			Html::closeElement( 'div' ) .
			Html::input( 'wpPassword', null, 'password',
				array( 'class' => array('loginPassword',$form_errs['password_class']),
					'placeholder' => wfMessage( 'mobile-frontend-password-placeholder' )->text(),
					'id' => 'wpPassword1',
					'tabindex' => '2',
					'size' => '20' ) ) .
			(($form_errs['password']) ? $form_errs['password'] : '').
			Html::input( 'wpRemember', '1', 'hidden' ) .
			Html::input( 'wpLoginAttempt', wfMessage( 'login' )->text(), 'submit',
				array( 'id' => 'wpLoginAttempt',
					'class' => 'mw-ui-button mw-ui-constructive',
					'tabindex' => '3' ) ) .
			Html::openElement( 'div', array(
				'class' => 'forgot-pwd-link',
			) ) .
			$forgotPass .
			Html::closeElement( 'div' ) .
			Html::input( 'wpLoginToken', $token, 'hidden' ) .
			Html::input( 'watch', $watchArticle, 'hidden' ) .
			$stickHTTPS .
			Html::closeElement( 'form' );
		echo $login;
		$this->getLoginTabs($query,false);
		$this->renderMessageHtml();
		echo $form;
		echo Html::closeElement( 'div' );
	}

}
