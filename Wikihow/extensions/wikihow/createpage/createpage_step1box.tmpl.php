<script type="text/javascript">
<!--
$('.cp_search_input').keypress(function (evt) {
  if (evt.which == 13) {
	 $('.cp_search_articles').click();
	 return false;
  }
});
//-->
</script>

<form action='/Special:CreatePage'  method='POST'>
<div class="wh_block">
	<input type='hidden' name='create_redirects' value='1'/>
	<h3><?=wfMessage('createpage_enter_title')?>:</h3>
	<div>
		<br />
		<?=wfMessage('howto','')?> <input type='text' id='createpage_title' value="<?=$step1_title?>" name='createpage_title' class='search_input' />
		<input type='button' value='<?=wfMessage('createpage_search_again')?>' onclick='document.getElementById("cp_next").disabled = true; searchTopics();' class='button createpage_button secondary' />
	</div>

	<div id='createpage_search_results'>
		<?=$related_block?>
	</div>

	<div id="createpage_buttons">
		<input type='submit' value='<?=wfMessage('createpage_next')?>' id='cp_next' class='button primary' />
		<input type='button' onclick='window.location="/Special:CreatePage";' value='<?=wfMessage('createpage_cancel')?>' class='button secondary' />
	</div>
	<br class="clearall" />
</div>
</form>
