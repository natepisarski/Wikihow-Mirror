<div id='gpl'>
	<div id='cl_header'>
		<div><span class="cl_logo_small"></span><?= $isApiSignup ? wfMessage('sl_header_update_details') : wfMessage('cl_savetime') ?></div>
	</div>
	<div id='cl_error'><?=$error?></div>
	<form method='POST' id='cl_form' action='<?= $formUrl ?>'>
		<input name='proposed_username' class='cl_readonly cl_username' type='hidden' value='<?=$username?>'/>
		<input name='original_username' class='cl_readonly cl_username' type='hidden' value='<?=$origname?>'/>
		<input name='avatar_url' class='cl_readonly cl_username' type='hidden' value='<?=$avatar?>'/>
		<input type='hidden' name='action' value='updateDetails'/>
		<input type='hidden' name='token' value='<?= $token ?>'/>
		<input type='hidden' name='returnTo' value='<?= $returnTo ?>'/>
		<input type='hidden' name='isSignup' value='<?= $isSignup ?>'/>
<? if ($isMobile): ?>
	<label class='cl_label first'><?= wfMessage("cl_username") ?></label>
	<? if ($username): ?>
		<div id='cl_faux_username' class='cl_readonly'>
			<span id='cl_x'></span>
			<img class='cl_user_avatar' src='<?= ($avatar ?: Avatar::getDefaultProfile()) ?>' style='width:auto' />
			<div id='cl_user_text'><div id='cl_user_text_username'><?=$username?></div></div>
		</div>
		<input class="input_med" type='text' name='requested_username' id='cl_requested_username' style='display:none'/>
	<? else: ?>
		<input class="input_med" type='text' name='requested_username' id='cl_requested_username'/>
	<? endif; ?>
	<label class='cl_label'><?= wfMessage("cl_email") ?></label>
	<input name='email' class='cl_readonly input_med' type='text' value='<?=$email?>' readonly='readonly'/>
	<input type='submit' id='cl_submit' class='button primary' value='<?= $isApiSignup ? wfMessage('sl_submit_save_details') : wfMessage('sl_submit_register') ?>'/>
<? else: ?>
		<table>
			<tr>
				<td class='cl_label'><?= wfMessage("cl_username") ?></td>
				<td>
	<? if ($username): ?>
					<div id='cl_faux_username' class='cl_readonly'>
						<span id='cl_x'></span>
						<img class='cl_user_avatar' src='<?= ($avatar ?: Avatar::getDefaultProfile()) ?>' style='width:auto' />
						<div id='cl_user_text'><div id='cl_user_text_username'><?=$username?></div></div>
					</div>
					<input class="input_med" type='text' name='requested_username' id='cl_requested_username' style='display:none'/>
	<? else: ?>
					<input class="input_med" type='text' name='requested_username' id='cl_requested_username'/>
	<? endif; ?>
				</td>
			</tr>
			<tr>
				<td class='cl_label'><?= wfMessage("cl_email") ?></td>
				<td><input name='email' class='cl_readonly input_med' type='text' value='<?=$email?>' readonly='readonly'/></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' id='cl_submit' class='button primary' value='<?= $isApiSignup ? wfMessage('sl_submit_save_details') : wfMessage('sl_submit_register') ?>'/></td>
			</tr>
		</table>
<? endif; ?>
	</form>
</div>
