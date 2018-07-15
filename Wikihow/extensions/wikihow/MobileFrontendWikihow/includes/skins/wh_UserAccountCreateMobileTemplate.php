<?php
/**
 * Provides a custom account creation form for mobile devices
 */
class UserAccountCreateMobileTemplate extends UserLoginAndCreateTemplate {
	protected $actionMessages = array(
		'watch' => 'mobile-frontend-watchlist-signup-action',
		'edit' => 'mobile-frontend-edit-signup-action',
		'signup-edit' => 'mobile-frontend-edit-signup-action',
		'' => 'mobile-frontend-generic-signup-action-wh',
	);
	protected $pageMessages = array(
		'Uploads' => 'mobile-frontend-donate-image-signup-action',
		'Watchlist' => 'mobile-frontend-watchlist-signup-action',
	);

	protected $actionMessageHeaders = array(
		'watch' => 'mobile-frontend-watchlist-purpose',
		'edit' => 'mobile-frontend-edit-login',
		'signup-edit' => 'mobile-frontend-edit-signup-wh',
		'' => 'mobile-frontend-edit-signup-wh',
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
		// handle captcha
		$captcha = $this->handleCaptcha( $this->data['header'] );

		$originalQuery = $this->getSkin()->getRequest()->getQueryValues();
		$query = array();
		if ( $originalQuery['returnto'] )  {
			$query['returnto'] = $originalQuery['returnto'];
		}
		if ( $originalQuery['returntoquery'] )  {
			$query['returntoquery'] = $originalQuery['returntoquery'];
		}
		if ( $originalQuery['useformat'] )  {
			$query['useformat'] = $originalQuery['useformat'];
		}

		//error(s)?
		$form_errs = array();
		$message = $this->data['message'];
		$messageType = $this->data['messagetype'];
		if ( $message && $messageType == 'error' ) {

			//basic error (will target in a second)
			$errMsg = Html::openElement( 'div', array( 'class' => 'login_error_msg' ) ) .
						htmlspecialchars_decode($message) . Html::closeElement( 'div' );

			if ($this->data['errorlist']) {
				foreach ($this->data['errorlist'] as $errType => $val) {
					$form_errs[$errType] = $errMsg;
					$form_errs[$errType.'_class'] = 'login_error';
				}
			}
		}

		$loginsignup_link = Linker::link( SpecialPage::getTitleFor( 'Userlogin' ),
			wfMessage( 'mobile-frontend-main-menu-login' )->text(),
			array(), $query );

		$disclaimer = '<div class="captcha_fineprint">'.wfMessage( 'fancycaptcha-createaccount' ) .'</div>';
		$civicLoginHtml = class_exists('CivicLogin') && CivicLogin::isEnabled() ?
			'<div id="civicButton" class="social_login_button civic_button">
				<span class="social_login_button_icon"></span>
				<span class="social_login_button_text">Civic</span>
			</div>' :
			'';
		$socialHtml =
'
<div class="section-title">' . wfMessage('mobile-frontend-sign-up-via')->text() . '</div>
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
<div class="section-title">' . wfMessage('mobile-frontend-or-sign-in-here')->text() . '</div>
';

		$form =
			$this->renderGuiderMessage($loginsignup_link) .

			Html::openElement( 'form',
				array( 'name' => 'userlogin2',
					'method' => 'post',
					'class' => 'user-login',
					'action' => $action,
					'id' => 'userlogin2' ) ) .
				$socialHtml .
			Html::openElement( 'div',
				array(
					'class' => 'inputs-box'
				)
			) .
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
					'id' => 'wpPassword2',
					'tabindex' => '2',
					'size' => '20' ) ) .
			(($form_errs['password']) ? $form_errs['password'] : '').
			Html::openElement( 'div', array(
				'class' => array('input-icon',$form_errs['password_class']),
			) ) .
			Html::element( 'div', array(
				'id' => 'input-icon-pwd2',
			) ) .
			Html::closeElement( 'div' ) .
			Html::input( 'wpRetype', null, 'password',
				array( 'class' => array('loginPassword',$form_errs['password_class']),
					'placeholder' => wfMessage( 'mobile-frontend-password-confirm-placeholder' )->text(),
					'id' => 'wpRetype',
					'tabindex' => '3',
					'size' => '20' ) ) .
			Html::openElement( 'div', array(
				'class' => array('input-icon',$email_err_class),
			) ) .
			Html::element( 'div', array(
				'id' => 'input-icon-email',
			) ) .
			Html::closeElement( 'div' ) .
			Html::input( 'wpEmail', null, 'email',
				array( 'class' => 'loginText',
					'placeholder' => wfMessage( 'mobile-frontend-account-create-email-placeholder' )->text(),
					'id' => 'wpEmail',
					'tabindex' => '4',
					'size' => '20' ) ) .
			$email_err_msg.
			Html::closeElement( 'div' ) .
			$captcha .
			Html::input( 'wpCreateaccount', wfMessage( 'mobile-frontend-account-create-submit-wh' )->text(), 'submit',
				array( 'id' => 'wpCreateaccount',
					'class' => 'mw-ui-button mw-ui-constructive',
					'tabindex' => '6' ) ) .
			Html::input( 'wpRemember', '1', 'hidden' ) .
			Html::input( 'wpCreateaccountToken', $token, 'hidden' ) .
			Html::input( 'watch', $watchArticle, 'hidden' ) .
			$stickHTTPS .
			$disclaimer .
			Html::closeElement( 'form' );
		echo Html::openElement( 'div', array( 'id' => 'mw-mf-accountcreate', 'class' => 'content' ) );
		$this->getLoginTabs($query, true);
		$this->renderMessageHtml();
		echo $form;
		echo Html::closeElement( 'div' );
	}

	/**
	 * Hijack captcha output
	 *
	 * Captcha output appears in $tpl->data['header'] but there's a lot
	 * of cruft that comes with it. We just want to get the captcha image
	 * a display an input field for the user to enter captcha info, without
	 * the additinal cruft.
	 *
	 * @TODO move this into ConfirmEdit extension when MW is context aware
	 * @param string
	 * @return string
	 */
	protected function handleCaptcha( $header ) {
		// first look for <div class="captcha">, otherwise early return
		if ( !$header || !stristr( $header, 'captcha' ) ) {
			return '';
		}

		// find the captcha ID
		$lines = explode( "\n", $header );
		$pattern = '/wpCaptchaId=([^"]+)"/';
		$matches = array();
		foreach ( $lines as $line ) {
			preg_match( $pattern, $line, $matches );
			// if we have a match, stop processing
			if ( $matches ) break;
		}
		// make sure we've gotten the captchaId
		if ( !isset( $matches[1] ) ) {
			return $header;
		}
		$captchaId = $matches[1];

		// generate src for captcha img
		$captchaSrc = SpecialPage::getTitleFor( 'Captcha', 'image' )->getLocalUrl( array( 'wpCaptchaId' => $captchaId ) );

		// add reload if fancyCaptcha and has reload
		if ( stristr( $header, 'fancycaptcha-reload' ) ) {
			$output = $this->getSkin()->getOutput();
			$output->addModuleStyles( 'ext.confirmEdit.fancyCaptcha.styles' );
			$output->addModules( 'ext.confirmEdit.fancyCaptchaMobile' );
			$captchaReload = Html::element( 'br' ) .
				Html::openElement( 'div', array( 'id' => 'mf-captcha-reload-container' ) ) .
				Html::element(
					'span',
					array(
						'class' => 'confirmedit-captcha-reload fancycaptcha-reload'
					),
					wfMessage( 'fancycaptcha-reload-text' )->text()
				) .
				Html::closeElement( 'div' ); #mf-captcha-reload-container
		} else {
			$captchaReload = '';
		}

		// captcha output html
		$captchaHtml =
			Html::openElement( 'div',
				array( 'class' => 'inputs-box' ) ) .
			Html::element( 'img',
				array(
					'class' => 'fancycaptcha-image',
					'src' => $captchaSrc,
				)
			) .
			$captchaReload .
			Html::input( 'wpCaptchaWord', null, 'text',
				array(
					'placeholder' => wfMessage( 'mobile-frontend-account-create-captcha-placeholder' )->text(),
					'id' => 'wpCaptchaWord',
					'tabindex' => '5',
					'size' => '20',
					'autocorrect' => 'off',
					'autocapitalize' => 'off',
				)
			) .
			Html::input( 'wpCaptchaId', $captchaId, 'hidden', array( 'id' => 'wpCaptchaId' ) ) .
			Html::closeElement( 'div' );
		return $captchaHtml;
	}
}
