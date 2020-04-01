<?=$css?>
<?=$js?>
<div id='fl_overview'>
	<div class='fl_acct_name'>Linking your account to Facebook:</div>
	<ul>
		<li>Allows you to login to wikiHow using Facebook</li>
		<li>Enables future social features on wikiHow</li>
		<li>Doesn't change your wikiHow account profile</li>
		<li>Is permanent, but that's a good thing!</li>
	</ul>
</div>
<div id='fl_fb_acct' class='fl_border'>
	<div><img class='fl_pic' src="<?=$fbPicUrl?>"></img></div>
	<div class='fl_acct_name fl_acct_name_fb'>Your Facebook Account</div>
	<div><b><?=$fbName?></b></div>
	<?
	if ($fbLocation) echo "<div>$fbLocation</div>";
	if ($fbEmployer) echo "<div>$fbEmployer</div>";
	//if ($fbSchool) echo "<div>$fbSchool</div>";
	?>
</div>
<? if ($showWarning) { ?>
<div id='fl_warning'><b>Warning:</b> You previously connected your Facebook account to wikiHow (<?=$oldAcct?>).  If you continue and link your current account (<?=$newAcct?>) to Facebook, then the other account will be deactivated.</div>
<? } ?>
<div id="fl_options">
	<a href="#" class="button primary" id="fl_button_save">Link Accounts</a>
	<a class='button fl_button_cancel' href="#">Cancel</a>
</div>
<input type="hidden" id="edit_token" value="<?= $editToken ?>">
