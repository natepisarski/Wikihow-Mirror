<p>Add a new image to the homepage:</p>
<form id='hp_form' action='/Special:WikihowHomepageAdmin' method="POST"
		enctype="multipart/form-data" onsubmit="return checkHpInputs();">

	Article Url:
	<input type="text" name="articleName" value="<?= $articleName ?>" />
	<span class='error'><?= $errorTitle ?></span>

	<br><br>

	Article Image:
	<input type='file' id='ImageUploadFile' name='wpUploadFile' size='30'>
	<span class='error'><?= $errorFile ?></span>

	<br><br>

	Image Destination Name:
	<input tabindex="2" type="text" name="wpDestFile" id="wpDestFile" size="40"
			value="<?= $destFile ?>" onkeyup="wgUploadWarningObj.keypress();">

	<br><br>

	<input type='submit' id='upload_btn' value='Upload' class="button" />

	<input type='hidden' name='uploadform1' value='1'/>
	<input type='hidden' name='src' value='upload'/>
</form>

<div id='wpDestFile-warning'>&nbsp;</div>
