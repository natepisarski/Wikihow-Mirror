<div id='gpl'>
	<div id='gpl_header'>
		<div><span class="gpl_logo_small"></span><?= $isApiSignup ? wfMessage('sl_header_update_details') : wfMessage('gplus-savetime') ?></div>
	</div>
	<div id='gpl_error'><?=$error?></div>
	<form method='POST' id='gpl_form' action='<?= $formUrl ?>'>
		<input name='proposed_username' class='gpl_readonly gpl_username' type='hidden' value='<?=$username?>'/>
		<input name='original_username' class='gpl_readonly gpl_username' type='hidden' value='<?=$origname?>'/>
		<input name='avatar_url' class='gpl_readonly gpl_username' type='hidden' value='<?=$avatar?>'/>
		<input type='hidden' name='action' value='updateDetails'/>
		<input type='hidden' name='token' value='<?= $token ?>'/>
		<input type='hidden' name='returnTo' value='<?= $returnTo ?>'/>
		<input type='hidden' name='isSignup' value='<?= $isSignup ?>'/>
<? if ($isMobile): ?>
	<label class='gpl_label first'><?= wfMessage("gplus-username") ?></label>
	<? if ($username): ?>
		<div id='gpl_faux_username' class='gpl_readonly'>
			<span id='gpl_x'></span>
			<img class='gpl_user_avatar' src='<?= ($avatar ?: Avatar::getDefaultProfile()) ?>' style='width:auto' />
			<div id='gpl_user_text'><div id='gpl_user_text_username'><?=$username?></div></div>
		</div>
		<input class="input_med" type='text' name='requested_username' id='gpl_requested_username' style='display:none'/>
	<? else: ?>
		<input class="input_med" type='text' name='requested_username' id='gpl_requested_username'/>
	<? endif; ?>
	<label class='gpl_label'><?= wfMessage("gplus-email") ?></label>
	<input name='email' class='gpl_readonly input_med' type='text' value='<?=$email?>' readonly='readonly'/>
	<input type='submit' id='gpl_submit' class='button primary' value='<?= $isApiSignup ? wfMessage('sl_submit_save_details') : wfMessage('sl_submit_register') ?>'/>
<? else: ?>
		<table>
			<tr>
				<td class='gpl_label'><?= wfMessage("gplus-username") ?></td>
				<td>
	<? if ($username): ?>
					<div id='gpl_faux_username' class='gpl_readonly'>
						<span id='gpl_x'></span>
						<img class='gpl_user_avatar' src='<?= ($avatar ?: Avatar::getDefaultProfile()) ?>' style='width:auto' />
						<div id='gpl_user_text'><div id='gpl_user_text_username'><?=$username?></div></div>
					</div>
					<input class="input_med" type='text' name='requested_username' id='gpl_requested_username' style='display:none'/>
	<? else: ?>
					<input class="input_med" type='text' name='requested_username' id='gpl_requested_username'/>
	<? endif; ?>
				</td>
			</tr>
			<tr>
				<td class='gpl_label'><?= wfMessage("gplus-email") ?></td>
				<td><input name='email' class='gpl_readonly input_med' type='text' value='<?=$email?>' readonly='readonly'/></td>
			</tr>
			<!--tr>
				<td colspan="2" class='gpl_check'><label for='show_authorship'>Link your Google profile to the content you post. <input name='show_authorship' id='show_authorship' type='checkbox' checked='checked' /></td>
			</tr-->
			<tr>
				<td></td>
				<td><input type='submit' id='gpl_submit' class='button primary' value='<?= $isApiSignup ? wfMessage('sl_submit_save_details') : wfMessage('sl_submit_register') ?>'/></td>
			</tr>
		</table>
<? endif; ?>
	</form>
</div>
