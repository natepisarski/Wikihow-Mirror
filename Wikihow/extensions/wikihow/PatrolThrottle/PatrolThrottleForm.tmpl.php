<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not a valid entry point.' );
}

/**
 * Main User Interface template for Special:PatrolThrottle
 *
 * @ingroup Templates
 */
class PatrolThrottleUITemplate extends QuickTemplate {

	public function execute() {
?>
<form class="visualClear" name="frmThrottle" method="post" action="<?php echo htmlspecialchars( $this->data['specialpage']->getPageTitle()->getFullURL() ) ?>">
	<fieldset>
		<table id="ptentry">
			<tbody>
				<tr class="mw-htmlform-field-HTMLTextField">
					<td class="mw-label"><label for="mw-input-wpPatroller"><?php echo wfMessage( 'patrolthrottle-input-patroller' )->text() ?></label></td>
					<td class="mw-input"><input type="text" id="mw-input-wpPatroller" name="wpPatroller" size="45" tabindex="1" /></td>
				</tr>
				<tr class="mw-htmlform-field-HTMLTextField">
					<td class="mw-label"><label for="mw-input-wpLimit"><?php echo wfMessage( 'patrolthrottle-input-limit' )->text() ?></label></td>
					<td class="mw-input"><input type="number" min="0" max="9999" id="mw-input-wpLimit" name="wpLimit" size="5" tabindex="2"></td>
				</tr>
			</tbody>
		</table>
		<input id="wpEditToken" value="<?php echo $this->data['edittoken'] ?>" name="wpEditToken" type="hidden">
			<span class="mw-htmlform-submit-buttons">
				<input value="<?php echo $this->data['submit_label'] ?>" class="mw-htmlform-submit primary button buttonright" type="submit" />
			</span>
	</fieldset>
</form>

<?php

		if ( count( $this->data['errors'] ) > 0 ) {
			echo '<p>' . wfMessage( 'patrolthrottle-correct-errors' )->text() . '</p>';
			echo '<ul>';
			foreach ( $this->data['errors'] as $error ) {
				echo '<li>' . $error . '</li>';
			}
			echo '</ul>';
		}

		if ( count( $this->data['patrollers'] ) > 0 ) {

?>

<br />
<br />
<div id="linkprev">
<?php

			if ( $this->data['prev'] !== false && $this->data['current'] != 0 ) {
				$link_prev = Linker::link( SpecialPage::getTitleFor( 'PatrolThrottle' ), wfMessage( 'patrolthrottle-link-prev' ), array(),
					array(
						'ptfrom' => $this->data['prev']
					) );
			} else {
				$link_prev = wfMessage( 'patrolthrottle-link-prev' );
			}

			echo $link_prev;
?>
</div>
<div id="linknext">
<?php

			if ( $this->data['next'] !== false ) {
				$link_next = Linker::link( SpecialPage::getTitleFor( 'PatrolThrottle' ), wfMessage( 'patrolthrottle-link-next' ), array(),
					array(
						'ptfrom' => $this->data['next']
					) );
			} else {
				$link_next = wfMessage( 'patrolthrottle-link-next' );
			}

			echo $link_next;
}
?>
</div>
<div class="minor_section">
<div id="notes"><a href="#" id="shownotes">+ Show Notes</a></div>
<div id="notesblock" hidden="true">
<p>
	<ul>
		<li>To remove an entry, set the patroller's limit to 0.</li>
		<li>Patrollers may exceed their limit if the last diff they patrol has intermediate revisions.</li>
		<li>Manual recent changes patrol and autopatrols are not throttled.</li>
		<li>Counts are based on the timezone the user has set in their preferences (or GMT if none is set).</li>
<?php

$user = RequestContext::getMain()->getUser();

if( in_array( 'staff', $user->getGroups() ) ) {
		echo '		<li> Staff: ' . Linker::link( Title::newFromText( 'patrolthrottle-hit-message', NS_MEDIAWIKI ), 'Configure throttle message') . '</li>';
		echo '		<li> Staff: ' . Linker::link( Title::newFromText( 'patrolthrottle-auto-expiry-age', NS_MEDIAWIKI ), 'Configure auto expiry timeout' ) . '</li>';
}

?>
</ul>
</p></div>
<?php

if ( count( $this->data['patrollers'] ) > 0 ) {

?>
<br />
<br />
<fieldset>
	<div id="PatrolThrottle">
		<table id="pttable">
			<tbody>
				<tr>
					<td></td>
					<td><?php echo wfMessage( 'patrolthrottle-column-user' )->text() ?></td>
					<td><?php echo wfMessage( 'patrolthrottle-column-limit' )->text() ?></td>
					<td><?php echo wfMessage( 'patrolthrottle-column-today' )->text() ?></td>
					<td><?php echo wfMessage( 'patrolthrottle-column-alltime' )->text() ?></td>
					<td><?php echo wfMessage( 'patrolthrottle-column-changed' )->text() ?></td>
				</tr>
<?php
			$i = $this->data['current'] + 1;
			foreach ( $this->data['patrollers'] as $patroller ) {
				echo "<tr>
					<td>$i</td>
					<td>{$patroller['name']}</td>
					<td>{$patroller['limit']}</td>
					<td>{$patroller['today']}</td>
					<td>{$patroller['total']}</td>
					<td>{$patroller['added']}</td>
				</tr>";

				$i++;
			}

?>
	</tbody>
		</table>
	</div>
</fieldset>
<?php
		} else {
			echo '<p>' . wfMessage( 'patrolthrottle-no-limited-users' ) . '</p>';
		}
	}
}
?>
</div>
