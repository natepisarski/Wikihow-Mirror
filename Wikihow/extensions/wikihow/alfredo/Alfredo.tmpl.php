

<script type="text/javascript">
(function($) {
	$(document).ready(function(){
		$("#btn").click(function(){
			var langs = "";
			var urls = $("#urls").val();
			$('.lang_checkbox').each(function(e) {
				if(this.checked) {
					if(langs != "") {
						langs = langs + ',';	
					}
					langs = langs + this.id;
				}
			});
			$.download('/' + wgPageName, {'langs':langs,'urls':urls});

		});
	});
})(jQuery);
</script>
<form>
<p>Enter a list of the English URLs and/or article IDs you wish to take images from, and select the languages you wish to add these images to. You will get back a spreadsheet of which image transfers are valid. A batch process will then transfer the images, and you will receive an email when it is complete at your wiki user email address.</p>

<h3>English URLs or Article Ids (one per line):</h3>
<textarea id="urls" rows="20" cols="90">
</textarea>
<?php foreach($langs as $lg) { ?>
    <input class="lang_checkbox" name="urls" id="<?php print $lg['languageCode']?>" type="checkbox" name="lang-<?php print $lg['languageCode'] ?>" value="<?php print $lg['languageCode']?>"> <?php print $lg['languageName'] ?>
<?php } ?><br/><br/>
<input id="btn" type="button" value="Add Images"/>
</form>

