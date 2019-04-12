<?php

class FollowWidget extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'FollowWidget' );
	}

	// whether to show a special larger "download on the app store" button
	// in the follow widget
	private static function showAppleCTA( $isMainPage, $title ) {
		if ( $isMainPage ) {
			return true;
		}

		// if it's this specific page: Use-the-wikiHow-iPhone-and-iPad-Application
		if ( $title && $title->getArticleID() == 570299 ) {
			return true;
		}

		return false;
	}

	private static function getFollowTableHtml() {
		$html = <<<HTML
<div id="follow_table">
	<a href="https://www.facebook.com/wikiHow" target="_blank" id="gatFollowFacebook" class='fw_cell' role="button" aria-label="Facebook"></a>
	<a href="https://www.twitter.com/wikihow" target="_blank" id="gatFollowTwitter" class='fw_cell' role="button" aria-label="Twitter"></a>
	<a href="https://www.wikihow.com/feed.rss" target="_blank" id="gatFollowRss" class='fw_cell' role="button" aria-label="RSS"></a>
	<a href="https://wikihow.us13.list-manage.com/subscribe?u=7d3ca675de04729b75ecca92b&id=2e1c5aad27" target="_blank" id="gatFollowNewsletter" class='fw_cell' role="button" aria-label="Newsletter Sign-up"></a>
	<a href="https://market.android.com/details?id=com.wikihow.wikihowapp" target="_blank" id="gatFollowAndroid" class='fw_cell' role="button" aria-label="Android App"></a>
	<a href="https://pinterest.com/wikihow/" target="_blank" id="gatFollowPinterest" class='fw_cell' role="button" aria-label="Pinterest"></a>
	<a href="https://wikihow.tumblr.com/" target="_blank" id="gatFollowTumblr" class='fw_cell' role="button" aria-label="Tumblr"></a>
	<a href="https://instagram.com/wikihow/" target="_blank" id="gatFollowInstagram" class='fw_cell' role="button" aria-label="Instagram"></a>
	<a href="http://itunes.apple.com/us/app/wikihow-how-to-diy-survival/id309209200?mt=8" target="_blank" id="gatFollowApp" class='fw_cell' role="button" aria-label="Apple App Store"></a>
</div>
HTML;
		return $html;
	}

	public static function getWidgetHtml( $isMainPage, $title ) {
		global $wgLanguageCode;
		$showAppleCTA = self::showAppleCTA( $isMainPage, $title );
		$fwTable = wfMessage('fw-table', wfGetPad())->text();

		$headerMessage = wfMessage('fw-header')->text();
		$headerHtml = Html::element( 'h3', array(), $headerMessage );

		if ( $wgLanguageCode == 'en' || !$fwTable ) {
			$followTableHtml = self::getFollowTableHtml();
			if ( $showAppleCTA ) {
				$followTableHtml = Html::rawElement( 'div', ['id' => 'follow_table_cta'], $followTableHtml );
			}
		} else {
			$followTableHtml = $fwTable;
		}

		$html = $headerHtml . $followTableHtml;

		return $html;
	}

	public function getForm() {
?>
		<h3 style="margin-bottom:10px"><?= wfMessage('fw-title')->text() ?></h3>
		<?php
	}

	public function execute($par) {
		global $wgOut, $wgRequest;

		$wgOut->setArticleBodyOnly(true);

/*
		$email = $wgRequest->getVal('getEmailForm');
		if ($email == "1") {
			$form = '<form id="ccsfg" name="ccsfg" method="post" action="/extensions/wikihow/common/CCSFG/signup/index.php" style="display:none;">

		<h4>'.wfMessage('fw-head')->text().'</h4>
		<p style="width:220px; margin-bottom: 23px; font-size:14px;">'.wfMessage('fw-blurb')->text().'</p>
		<img src="' . wfGetPad('/skins/WikiHow/images/kiwi-small.png') . '" nopin="nopin" style="position:absolute; right:90px; top:68px;" />';

		$form .= <<<EOHTML
		<table>

			<tr><td colspan="2">
					<!-- ########## Email Address ########## -->
					<label for="EmailAddress">Email Address</label><br />
					<input type="text" name="EmailAddress" value="" id="EmailAddress" style="width:350px; height:25px; font-size:13px;" /><br /><br />
				</td>
			</tr>
			<tr>
				<td styel="padding-right:4px;">
					<!-- ########## First Name ########## -->
					<label for="FirstName">First Name (optional):</label><br />
					<input type="text" name="FirstName" value="" id="FirstName" style="width:215px; height:25px; margin-right:10px; font-size:13px;" /><br />
				</td>
				<td>
					<!-- ########## Last Name ########## -->
					<label for="LastName">Last Name (optional):</label><br />
					<input type="text" name="LastName" value="" id="LastName" style="width:215px; height:25px; font-size:13px;" /><br />
				</td>
			<tr>
			<tr><td colspan="2">
				<!-- ########## Contact Lists ########## -->
				<input type="hidden"  checked="checked"  value="General Interest" name="Lists[]" id="list_General Interest" />

				<input type="submit" name="signup" id="signup" value="Join" class="button primary" />
			</td></tr>
		</table>

		</form>
EOHTML;
		echo $form;
		}
		else
*/
			$wgOut->addHTML($this->getForm());

	}

}
