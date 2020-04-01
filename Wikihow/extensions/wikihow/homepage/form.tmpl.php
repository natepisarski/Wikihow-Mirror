<div class="hp_admin_form">

<p>Add a new image to the homepage:</p>
<form id='hp_form' action='/Special:WikihowHomepageAdmin' method="POST" enctype="multipart/form-data">

	<div class="row">
		Article Url:
		<input type="text" id="articleName" name="articleName" value="<?= $articleName ?>" />
		<span class='error'><?= $errorTitle ?></span>
	</div>

	<div class="row">
		Article Image:
		<input type='file' id='ImageUploadFile' name='wpUploadFile' size='30'>
		<span class='error'><?= $errorFile ?></span>
	</div>

	<div class="row">
		Image Destination Name:
		<input tabindex="2" type="text" name="wpDestFile" id="wpDestFile" size="40" value="<?= $destFile ?>">
	</div>

	<input type='submit' id='upload_btn' value='Upload' class="button" />

	<input type='hidden' name='uploadform1' value='1'/>
	<input type='hidden' name='src' value='upload'/>
</form>

<div id='wpDestFile-warning'>&nbsp;</div>

</div>
