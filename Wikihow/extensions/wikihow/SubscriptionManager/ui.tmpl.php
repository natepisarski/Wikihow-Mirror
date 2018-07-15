<?php
/**
 * @file
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not a valid entry point.' );
}

/**
 * Main User Interface template for Special:SubscriptionManager.
 *
 * @ingroup Templates
 */
class SubscriptionManagerUITemplate extends QuickTemplate {
	public function execute() {
?>
	<form name="frmUnsubscribe" action="<?php echo htmlspecialchars( $this->data['specialpage']->getTitle()->getFullURL() ) ?>" method="post">
		<table colspan="1" cellspacing="15" cellpadding="15">
			<tbody>
				<tr class="mw-htmlform-field-HTMLCheckField">
					<td class="mw-input">
						<div class="mw-htmlform-flatlist-item">
							<input type="hidden" name="token" value="<?php echo $this->data['token'] ?>"/>
							<input type="hidden" name="unsubtype" value="<?php echo $this->data['type'] ?>"/>
							<input type="hidden" name="identifier" value="<?php echo $this->data['id'] ?>"/>
							<input type="hidden" name="optin" value="<?php echo $this->data['isOptIn'] ?>"/>
							<input type="hidden" name="edittoken" value="<?php echo $this->data['edittoken'] ?>"/>
							<?php echo $this->data['checkbox_html'] ?>
						</div>
						<div class="mw-htmlform-flatlist-item" style="margin-top:15px">
							<input name="submit" type="submit" class="button primary" value="<?php echo $this->data['submit_label'] ?>"/>
						</div>
						<tr>
							<td><?php echo $this->data['change_prefs'] ?></td>
						</tr>
						<tr>
							<td><?php echo $this->data['community_desc'] ?></td>
						</tr>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
<?php
	} // execute()
}