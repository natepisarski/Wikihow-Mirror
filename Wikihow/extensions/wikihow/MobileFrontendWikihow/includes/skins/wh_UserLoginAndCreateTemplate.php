<?php

/**
 * Template overloader for user login and account creation templates
 *
 * Facilitates hijacking existing account creation/login template objects
 * by copying their properties to this new template, and exposing some
 * special mobile-specific magic.
 */
abstract class UserLoginAndCreateTemplate extends QuickTemplate {
	protected $pageMessageHeaders = array(
		'Uploads' => 'mobile-frontend-donate-image-login',
		'Watchlist' => 'mobile-frontend-watchlist-purpose',
	);
	protected $pageMessages = array();

	protected $actionMessageHeaders = array(
		'watch' => 'mobile-frontend-watchlist-purpose',
		'edit' => 'mobile-frontend-edit-login',
		'signup-edit' => 'mobile-frontend-edit-login-wh',
		'' => 'mobile-frontend-edit-login-wh',
	);

	protected $actionMessages = array();

	/**
	 * Overload the parent constructor
	 *
	 * Does not call the parent's constructor to prevent overwriting
	 * $this->data and $this->translatorobject since we're essentially
	 * just hijacking the existing template and its data here.
	 * @param QuickTemplate $template: The original template object to overwrite
	 */
	public function __construct( $template ) {
		$this->copyObjectProperties( $template );
	}

	protected function renderMessageHtml() {
		$msgBox = ''; // placeholder for displaying any login-related system messages (eg errors)

		// Render logged-in notice (beta/alpha)
		if ( $this->data['loggedin'] ) {
			$msgBox .= Html::element( 'div', array( 'class' => 'alert warning' ),
				wfMessage( 'userlogin-loggedin' )->params(
					$this->data['loggedinuser'] )->parse() );
		}

		// Render login errors
		$message = $this->data['message'];
		$messageType = $this->data['messagetype'];
		if ( $message && $messageType != 'error' ) {
			$heading = '';
			$class = 'alert';
			//show the errors inline
			// if ( $messageType == 'error' ) {
				// $heading = wfMessage( 'mobile-frontend-sign-in-error-heading' )->text();
				// $class .= ' error';
			// }

			$msgBox .= Html::openElement( 'div', array( 'class' => $class ) );
			$msgBox .= ( $heading ) ? Html::rawElement( 'h2', array(), $heading ) : '';
			$msgBox .= $message;
			$msgBox .= Html::closeElement( 'div' );
		} else {
			$msgBox .= $this->getLogoHtml();
		}
		echo $msgBox;
	}

	/**
	 * Copy public properties of one object to this one
	 * @param object $obj: The object whose properties should be copied
	 */
	protected function copyObjectProperties( $obj ) {
		foreach( get_object_vars( $obj ) as $prop => $value ) {
			$this->$prop = $value;
		}
	}

	/**
	 * Get the current RequestContext
	 * @return RequestContext
	 */
	public function getRequestContext() {
		return RequestContext::getMain();
	}

	/**
	 * Prepare template data if an anon is attempting to log in after watching an article
	 */
	protected function getArticleTitleToWatch() {
		$ret = '';
		$request = $this->getRequestContext()->getRequest();
		if ( $request->getVal( 'returntoquery' ) == 'article_action=watch' &&
			!is_null( $request->getVal( 'returnto' ) ) ) {
			$ret = $request->getVal( 'returnto' );
		}
		return $ret;
	}

	/**
	 * Determine whether or not we should attempt to 'stick https'
	 *
	 * If wpStickHTTPS is set as a value in login requests, when a user
	 * is logged in to HTTPS and if they attempt to view a page on http,
	 * they will be automatically redirected to HTTPS.
	 * @see https://gerrit.wikimedia.org/r/#/c/24026/
	 * @return bool
	 */
	protected function doStickHTTPS() {
		global $wgSecureLogin;
		$request = $this->getRequestContext()->getRequest();
		if ( $wgSecureLogin && $request->detectProtocol() === 'https' ) {
			return true;
		}
		return false;
	}

	/**
	 * Gets the message that should guide a user who is creating an account or logging in to an account.
	 * @return Array: first element is header of message and second is the content.
	 */
	protected function getGuiderMessage($loginsignup_link = '') {
		$req = $this->getRequestContext()->getRequest();
		if ( $req->getVal( 'returnto' ) && ( $title = Title::newFromText( $req->getVal( 'returnto' ) ) ) ) {
			list( $returnto, /* $subpage */ ) = SpecialPageFactory::resolveAlias( $title->getDBkey() );
			$title = $title->getText();
		} else {
			$returnto = '';
			$title = '';
		}
		$returnToQuery = wfCgiToArray( $req->getVal( 'returntoquery' ) );
		if ( isset( $returnToQuery['article_action'] ) ) {
			$action = $returnToQuery['article_action'];
		} else {
			$action = '';
		}

		$heading = '';
		$content = '';

		if ( isset( $this->pageMessageHeaders[$returnto] ) ) {
			$heading = wfMessage( $this->pageMessageHeaders[$returnto] )->parse();
			if ( isset( $this->pageMessages[$returnto] ) ) {
				$content = wfMessage( $this->pageMessages[$returnto] )->parse();
			}
		} elseif ( isset( $this->actionMessageHeaders[$action] ) ) {
			$heading = wfMessage( $this->actionMessageHeaders[$action], $title )->parse();
			if ( isset( $this->actionMessages[$action] ) ) {
				$content = wfMessage( $this->actionMessages[$action], $title, $loginsignup_link )->plain();
			}
		}
		return array( $heading, $content );
	}

	/**
	 * Renders a prompt above the login or upload screen
	 *
	 */
	protected function renderGuiderMessage($loginsignup_link = '') {
		$out = '';
		if ( !$this->data['loggedin'] ) {
			$msgs = $this->getGuiderMessage($loginsignup_link);
			if ( $msgs[0] ) {
				$out .= Html::openElement( 'div', array( 'class' => 'headmsg' ) );
				$out .= Html::element( 'strong', array(), $msgs[0] );
				if ( $msgs[1] ) {
					$out .= '<div>'.$msgs[1].'</div>';
				}
				$out .= Html::closeElement( 'div' );
			}
		}

		return $out;
	}

	protected function getLogoHtml() {
		global $wgMobileFrontendLogo;

		if ( !$wgMobileFrontendLogo ) {
			return '';
		}
		return '<div class="watermark">'
			. Html::element( 'img',
				array(
					'src' => $wgMobileFrontendLogo,
					'alt' => '',
				)
			)
			. '</div>';
	}

	function getLoginTabs($query, $isSignup) {
		global $wgLang;
		$carat = '<span id="lt_carat" class="icon"></span>';
		$off_class = 'lt_off';

		$attributes = array(
			'class' => ($isSignup) ? '' : $off_class,
			'id' => 'lt_signup',

		);
		$signup_link_inner = $carat.'<span class="icon"></span>'.wfMessage('mobile-frontend-edit-signup-wh-link');
		$signup_link = Linker::link(SpecialPage::getTitleFor( 'Userlogin' ), $signup_link_inner, $attributes, $query, array('known'));

		$attributes = array(
			'class' => (!$isSignup) ? '' : $off_class,
			'id' => 'lt_login',

		);
		$login_link_inner = $carat.'<span class="icon"></span>'.wfMessage('mobile-frontend-edit-login-wh-link');
		$login_link = Linker::link(SpecialPage::getTitleFor( 'Userlogin' ), $login_link_inner, $attributes, $query, array('known'));

		$html = '<div id="login_tabs">'.
				$signup_link.
				$login_link.
				'</div>';

		echo $html;
	}
}
