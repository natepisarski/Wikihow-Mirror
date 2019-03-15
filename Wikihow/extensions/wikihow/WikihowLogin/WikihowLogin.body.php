<?php

class WikihowLogin {

	private static $BAD_USER_ERRORS = array('noname','noname-mobile','userexists','userexists-mobile','createaccount-hook-aborted');
	private static $BAD_PASSWORD_ERRORS = array('badretype','badretype-mobile','passwordtooshort','passwordtooshort-mobile','password-name-match','password-login-forbidden');
	private static $BAD_EMAIL_ERRORS = array('noemailtitle','invalidemailaddress');
	private static $BAD_CAPTCHA_ERRORS = array('captcha-createaccount-fail');

	/**
	*	Added by Gershon Bialer with upgrade to add header
	* 	Tweaked by Scott Cushman for upgrade 1.22
	*	Made possible (in part) by a grant from the National Awesome Society
	*	And viewers like you.
	*/
	static function topContent($template, $login_type) {
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
		return true;
	}

	static function CustomSideBar(&$result) {
		$result = true;
		return $result;
	}

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
}

/*
 * wikiHow's custom login form
 */
class WikihowLoginTemplate extends BaseTemplate {

	function __construct() {
		global $wgHooks;
		parent::__construct();
		$wgHooks['BeforeTabsLine'][] = array('WikihowLogin::topContent',$this,'login');
	}

	function execute() {
		global $wgCookieExpiration;
		$expirationDays = ceil( $wgCookieExpiration / ( 3600 * 24 ) );

		$ctx = RequestContext::getMain();

		if ($this->data['loggedin'] && !empty($ctx->getRequest()->getVal('returnto'))) {
			$lf = new LoginForm();
			$lf->showReturnToPage('successredirect',$ctx->getRequest()->getVal('returnto'),'',true);
		}

		$ctx->getOutput()->addModules('ext.wikihow.loginpage');
?>
<div id="userlogin_alt_logins">
	<div class="headline"><?= wfMessage('log_in_via')->plain() ?></div>
	<div id="fb_connect<?=$suffix?>"><a id="fb_login<?=$suffix?>" href="#" role="button" class="ulb_button loading" aria-label="<?=wfMessage('aria_facebook_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-fb')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
	<div id="gplus_connect<?=$suffix?>"><a id="gplus_login<?=$suffix?>" href="#" role="button" class="ulb_button loading"  aria-label="<?=wfMessage('aria_google_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-gplus')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
	<?php if (CivicLogin::isEnabled()): ?>
		<div id="civic_connect<?=$suffix?>"><a id="civic_login<?=$suffix?>" href="#" role="button" class="ulb_button loading"  aria-label="<?= wfMessage('aria_civic_login')->showIfExists() ?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?= wfMessage('ulb-btn-civic')?></span><span class="ulb_status"><?= wfMessage('ulb-btn-loading')?></span></a></div>
	<?php endif ?>
</div>

<div class="mw-ui-container">
	<?php if ( $this->haveData( 'languages' ) ) { ?>
		<div id="languagelinks">
			<p><?php $this->html( 'languages' ); ?></p>
		</div>
	<?php } ?>

	<div id="userloginForm" class="userloginform">
		<div id="userLoginOr"><?= wfMessage('loginor')->text() ?></div>
		<form name="userlogin" id="userlogin" class="mw-ui-vform" method="post" action="<?php $this->text( 'action' ); ?>">
			<?php if ( $this->data['loggedin'] ) { ?>
				<div class="warningbox">
					<?= $this->getMsg( 'userlogin-loggedin' )->params( $this->data['loggedinuser'] )->parse() ?>
				</div>
			<?php } ?>
			<section class="mw-form-header">
				<?php $this->html( 'header' ); /* extensions such as ConfirmEdit add form HTML here */ ?>
			</section>

			<div>
				<?php
				$extraAttrs = array();
				echo Html::input( 'wpName', $this->data['name'], 'text', array(
					'class' => 'loginText input_med',
					'id' => 'wpName1',
					'tabindex' => '1',
					'size' => '20',
					// 'required' is blacklisted for now in Html.php due to browser issues.
					// Keeping here in case that changes.
					'required' => true,
					// Set focus to this field if it's blank.
					'autofocus' => !$this->data['name'],
					'placeholder' => $this->getMsg( 'userlogin-yourname-ph' )->text()
				) );
				?>
				<?php if ( in_array('username',$this->data['errorlist'])): ?>
					<div class="mw-error" id="wpName1_error">
						<?php $this->html('message') ?>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<?php
				echo Html::input( 'wpPassword', null, 'password', array(
					'class' => 'loginPassword input_med',
					'id' => 'wpPassword1',
					'tabindex' => '2',
					'size' => '20',
					// Set focus to this field if username is filled in.
					'autofocus' => (bool)$this->data['name'],
					'placeholder' => $this->getMsg( 'userlogin-yourpassword-ph' )->text()
				) );
				?>
				<?php if ( in_array('password', $this->data['errorlist'] )): ?>
					<div class="mw-error" id="wpPassword1_error">
						<?php $this->html('message') ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="remember_pwd">
				<?php if ( $this->data['canremember'] ) { ?>
					<input name="wpRemember" type="checkbox" value="1" id="wpRemember" tabindex="4"
						<?php if ( $this->data['remember'] ) {
							echo 'checked="checked"';
						} ?>
					>
					<label class="mw-ui-checkbox-label">
						<?= $this->getMsg( 'rememberme' )->numParams( $expirationDays )->escaped() ?>
					</label>
				<?php } ?>
			</div>
			<?php
			if ( isset( $this->data['usedomain'] ) && $this->data['usedomain'] ) {
				$select = new XmlSelect( 'wpDomain', false, $this->data['domain'] );
				$select->setAttribute( 'tabindex', 3 );
				foreach ( $this->data['domainnames'] as $dom ) {
					$select->addOption( $dom );
				}
			?>
				<div id="mw-user-domain-section">
					<label for='wpDomain'><?php $this->msg( 'yourdomainname' ); ?></label>
					<?= $select->getHTML() ?>
				</div>
			<?php } ?>

			<?php
			if ( $this->haveData( 'extrafields' ) ) {
				echo $this->data['extrafields'];
			}
			?>

			<div>
				<?php
				echo Html::input( 'wpLoginAttempt', $this->getMsg( 'login' )->text(), 'submit', array(
					'id' => 'wpLoginAttempt',
					'tabindex' => '6',
					'class' => 'mw-ui-button mw-ui-big mw-ui-block button primary submit_button'
				) );
				?>
			</div>
			<div id="forgot_pwd">
				<?php
				if ( $this->data['useemail'] && $this->data['canreset'] && $this->data['resetlink'] === true ) {
					echo ' ' . Linker::link(
							SpecialPage::getTitleFor( 'PasswordReset' ),
							$this->getMsg( 'forgot_pwd' )->parse(),
							array( 'class' => 'mw-ui-flush-right' )
						);
				}
				?>
			</div>
			<div id="userloginprompt"><?php  $this->msgWiki('loginprompt') ?></div>
			<?php if ( $this->haveData( 'uselang' ) ) { ?><input type="hidden" name="uselang" value="<?php $this->text( 'uselang' ); ?>" /><?php } ?>
			<?php if ( $this->haveData( 'token' ) ) { ?><input type="hidden" name="wpLoginToken" value="<?php $this->text( 'token' ); ?>" /><?php } ?>
			<?php if ( $this->data['cansecurelogin'] ) {?><input type="hidden" name="wpForceHttps" value="<?php $this->text( 'stickhttps' ); ?>" /><?php } ?>
		</form>
	</div>
</div>
<?php

	}
}


/*
 * wikiHow's custom sign up form
 */
class WikihowCreateTemplate extends BaseTemplate {

	function __construct() {
		global $wgHooks;
		parent::__construct();
		$wgHooks['BeforeTabsLine'][] = array('WikihowLogin::topContent',$this,'create');
		$wgHooks['CustomSideBar'][] = array('WikihowLogin::CustomSideBar');
	}

	/**
	 * [from includes/template/Usercreate.php]
	 * Extensions (AntiSpoof and TitleBlacklist) call this in response to
	 * UserCreateForm hook to add checkboxes to the create account form.
	 */
	function addInputItem( $name, $value, $type, $msg, $helptext = false ) {
		$this->data['extraInput'][] = array(
			'name' => $name,
			'value' => $value,
			'type' => $type,
			'msg' => $msg,
			'helptext' => $helptext,
		);
	}

	function execute() {
		global $wgCookieExpiration;
		$expirationDays = ceil( $wgCookieExpiration / ( 3600 * 24 ) );

		$ctx = RequestContext::getMain();
		$ctx->getOutput()->addModules('ext.wikihow.loginpage');

		//is the user already logged in?
		if ($this->data['loggedin']) {
			//why is this user even here? let's give the user some options
			echo wfMessage('alreadysignedin','Special:UserLogout')->parse();
			return;
		}
		if ($ctx->getLanguage()->getCode() != "en") {
?>
<style type="text/css">
#userlogin2 > div > label {
    float: left;
    display: inline-block;
    width: 80px;
}
#userlogin2 > div.remember_pwd > label.mw-ui-checkbox-label {
	width: inherit;
	margin-bottom: 10px;
}
label[for="wpName2"], label[for="wpPassword2"] {
    margin-top:1.5em;
}
#realname_check {
	margin-left:95px;
}

<?php
/**
 * George 2015-04-30
 * This was breaking the login page on int'l.
 * Commenting out until better solution is found.
.mw-ui-container {
	float: left;
	width: 50%;
}
*/
?>

</style>

<?php } ?>

<div class="mw-ui-container">

	<div id="userlogin_alt_logins" class="sign_up">
		<div class="headline"><?= wfMessage('sign_up_with')->plain() ?></div>
		<div id="fb_connect<?=$suffix?>"><a id="fb_login<?=$suffix?>" href="#" role="button" class="ulb_button loading" aria-label="<?=wfMessage('aria_facebook_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-fb')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
		<div id="gplus_connect<?=$suffix?>"><a id="gplus_login<?=$suffix?>" href="#" role="button" class="ulb_button loading"  aria-label="<?=wfMessage('aria_google_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-gplus')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
		<?php if (CivicLogin::isEnabled()): ?>
			<div id="civic_connect<?=$suffix?>"><a id="civic_login<?=$suffix?>" href="#" role="button" class="ulb_button loading"  aria-label="<?=wfMessage('aria_civic_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-civic')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
		<?php endif ?>
	</div>

	<?php if ( $this->haveData( 'languages' ) ) { ?>
		<div id="languagelinks">
			<p><?php $this->html( 'languages' ); ?></p>
		</div>
	<?php }
	      if ( !wfMessage( 'signupstart' )->isDisabled() ) { ?>
		<div id="signupstart"><?php $this->msgWiki( 'signupstart' ); ?></div>
	<?php } ?>
	<div id="userloginForm" class="usercreateform">
		<div id="userLoginOr">or</div>
		<div class="sub_social_login"><?= wfMessage('or_create_an_account')->plain() ?></div>
		<form name="userlogin2" id="userlogin2" class="mw-ui-vform" method="post" action="<?php $this->text( 'action' ); ?>">
			<div>
				<div id="wpName2_mark" class="wpMark" />
				<label for='wpName2' class="userlogin_label">
					<?php $this->msg( 'userlogin-yourname' ); ?>

					<span class="mw-ui-flush-right"><?= $this->getMsg( 'createacct-helpusername' )->parse() ?></span>
				</label>
				<?php
				echo Html::input( 'wpName', $this->data['name'], 'text', array(
					'class' => 'mw-input loginText input_med',
					'id' => 'wpName2',
					'tabindex' => '1',
					'size' => '20',
					'required',
					// 'placeholder' => $this->getMsg( $this->data['loggedin'] ?
						// 'createacct-another-username-ph' : 'userlogin-yourname-ph' )->text(),
				) );
				?>
				<div class="mw-error" id="wpName2_error" <?php if (!isset($this->data['errorlist']['username'])) echo 'style="display:none;"' ?>>
					<? if (isset($this->data['errorlist']['username'])) {
						foreach ($this->data['errorlist']['username'] as $error) {
							if ( is_array($error) ) {
								echo $this->msgHtml($error[0]);
							} elseif ( get_class($error) == 'RawMessage' ) {
								echo $error->parse();
							}
						}
					} ?>
				</div>
				<div class="mw-info" id="wpName2_info" style="display:none">
					<?= wfMessage('info_username')->text() ?>
				</div>
				<div id="realname_check">
					<input type='checkbox' id='wpUseRealNameAsDisplay' name='wpUseRealNameAsDisplay' <? if ($this->data['userealname']) { ?>checked='checked'<? } ?> />
					<label for="wpUseRealNameAsDisplay"><?php $this->msg('user_real_name_display'); ?></label>
				</div>
			</div>

			<div id="real_name_row" <?php if ( $this->data['userealname'] ) { ?>style="display:none;"<? } ?>>
				<label for='wpRealName' class="userlogin_label"><?php $this->msg( 'yourrealname' ); ?></label>
				<input type='text' class='mw-input loginText input_med' name="wpRealName" id="wpRealName"
					tabindex="2"
					value="<?php $this->text( 'realname' ); ?>" size='20' />
				<div class="mw-info" id="wpRealName_info">
						<?php $this->msgWiki('info_realname') ?>
				</div>
			</div>

			<div class="mw-row-password">
				<div id="wpPassword2_mark" class="wpMark" />
				<label for='wpPassword2' class="userlogin_label"><?php $this->msg( 'userlogin-yourpassword' ); ?></label>
				<?php
				echo Html::input( 'wpPassword', null, 'password', array(
					'class' => 'mw-input loginPassword input_med',
					'id' => 'wpPassword2',
					'tabindex' => '3',
					'size' => '20',
					'required',
					//'placeholder' => $this->getMsg( 'createacct-yourpassword-ph' )->text()
				) + User::passwordChangeInputAttribs() );
				?>
				<div class="mw-error" id="wpPassword2_error" <?php if ( !isset($this->data['errorlist']['password'])) echo 'style="display:none;"' ?>>
					<? if (isset($this->data['errorlist']['password'])): ?>
					<?php foreach ( $this->data['errorlist']['password'] as $error): ?>
						<?= wfMessage($error[0])->params(array_splice($error,1))->plain()  ?>
					<?php endforeach;
						  endif; ?>
				</div>
				<input type="hidden" id="wpPassword2_showhide" />
			</div>

			<div class="mw-row-password">
				<div id="wpRetype_mark" class="wpMark" />
				<label for='wpRetype' class="userlogin_label"><?php $this->msg( 'createacct-yourpasswordagain' ); ?></label>
				<?php
				echo Html::input( 'wpRetype', null, 'password', array(
					'class' => 'mw-input loginPassword input_med',
					'id' => 'wpRetype',
					'tabindex' => '5',
					'size' => '20',
					'required',
					//'placeholder' => $this->getMsg( 'createacct-yourpasswordagain-ph' )->text()
					) + User::passwordChangeInputAttribs() );
				?>
				<div class="mw-error" id="wpRetype_error" style="display:none;" ?>
				</div>
			</div>

			<div>
				<?php if ( $this->data['useemail'] ) { ?>
					<label for='wpEmail' class="userlogin_label">
						<?php
							$this->msg( $this->data['emailrequired'] ?
								'createacct-emailrequired' :
								'createacct-emailoptional'
							);
						?>
					</label>
					<?php
						echo Html::input( 'wpEmail', $this->data['email'], 'email', array(
							'class' => 'mw-input loginText input_med',
							'id' => 'wpEmail',
							'tabindex' => '6',
							'size' => '20',
							'required' => $this->data['emailrequired'],
							// 'placeholder' => $this->getMsg( $this->data['loggedin'] ?
								// 'createacct-another-email-ph' : 'createacct-email-ph' )->text()
						) );
					?>
				<?php } ?>
				<div class="mw-info" id="wpEmail_info">
					<?php $this->msgHtml('info_email') ?>
				</div>
			</div>

			<?php
			$tabIndex = 9;
			if ( isset( $this->data['extraInput'] ) && is_array( $this->data['extraInput'] ) ) {
				foreach ( $this->data['extraInput'] as $inputItem ) { ?>
					<div>
						<?php
						// If it's a checkbox, output the whole thing (assume it has a msg).
						if ( $inputItem['type'] == 'checkbox' ) {
						?>
							<label class="mw-ui-checkbox-label">
								<input
									name="<?= htmlspecialchars( $inputItem['name'] ) ?>"
									id="<?= htmlspecialchars( $inputItem['name'] ) ?>"
									type="checkbox" value="1"
									tabindex="<?= $tabIndex++ ?>"
									<?php if ( !empty( $inputItem['value'] ) ) {
										echo 'checked="checked"';
									} ?>
								>
								<?php $this->msg( $inputItem['msg'] ); ?>
							</label>
						<?php
						} else {
							// Not a checkbox.
							// TODO (bug 31909) support other input types, e.g. select boxes.
						?>
							<?php if ( !empty( $inputItem['msg'] ) ) { ?>
								<label for="<?= htmlspecialchars( $inputItem['name'] ) ?>">
									<?php $this->msgWiki( $inputItem['msg'] ); ?>
								</label>
							<?php } ?>
							<input
								type="<?= htmlspecialchars( $inputItem['type'] ) ?>"
								class="mw-input"
								name="<?= htmlspecialchars( $inputItem['name'] ) ?>"
								tabindex="<?= $tabIndex++ ?>"
								value="<?= htmlspecialchars( $inputItem['value'] ) ?>"
								id="<?= htmlspecialchars( $inputItem['name'] ) ?>"
							/>
						<?php } ?>
						<?php if ( $inputItem['helptext'] !== false ) { ?>
							<div class="prefsectiontip">
								<?php $this->msgWiki( $inputItem['helptext'] ); ?>
							</div>
						<?php } ?>
					</div>
				<?php
				}
			}

			// JS attempts to move the image CAPTCHA below this part of the form,
			// so skip one index.
			$tabIndex++;
			?>
			<section class="mw-form-header">
				<?php $this->html( 'header' ); /* extensions such as ConfirmEdit add form HTML here */ ?>

				<div class="mw-info" id="wpCaptchaWord_info">
					<?= wfMessage('info_captcha')->text() ?>
				</div>
				<?php if (isset($this->data['errorlist']['captcha'])): ?>
				<div class="mw-error" id="wpCaptchaWord_error" style="display: block">
					<?php foreach ( $this->data['errorlist']['captcha'] as $error): ?>
						<?= wfMessage($error[0])->params(array_splice($error,1))->plain() ?>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

			</section>
			<div class="remember_pwd">
				<?php if ( $this->data['canremember'] ) { ?>
					<label class="mw-ui-checkbox-label">
						<input name="wpRemember" type="checkbox" value="1" id="wpRemember" tabindex="9"
							<?php if ( $this->data['remember'] ) {
								echo 'checked="checked"';
							} ?>
						>
						<?= $this->getMsg( 'rememberme' )->numParams( $expirationDays )->escaped() ?>
					</label>
				<?php } ?>
			</div>

			<div class="mw-submit">
				<?php
				echo Html::input(
					'wpCreateaccount',
					$this->getMsg( 'createaccount' ),
					'submit',
					array(
						'class' => "mw-ui-button mw-ui-big mw-ui-block button primary submit_button",
						'id' => 'wpCreateaccount',
						'tabindex' => $tabIndex++
					)
				);
				?>
			</div>
			<?php if ( $this->haveData( 'uselang' ) ) { ?><input type="hidden" name="uselang" value="<?php $this->text( 'uselang' ); ?>" /><?php } ?>
			<?php if ( $this->haveData( 'token' ) ) { ?><input type="hidden" name="wpCreateaccountToken" value="<?php $this->text( 'token' ); ?>" /><?php } ?>
			<?php if ( $this->data['cansecurelogin'] ) {?><input type="hidden" name="wpForceHttps" value="<?php $this->text( 'stickhttps' ); ?>" /><?php } ?>
		</form>
		<?php if ( !wfMessage( 'signupend' )->isDisabled() ) { ?>
			<div id="signupend"><?php $this->html( 'signupend' ); ?></div>
		<?php } ?>
	</div>
</div>
<div class="captcha_fineprint"><?php $this->msgWiki( 'fancycaptcha-createaccount' ) ?></div>
<?php
	}
}
