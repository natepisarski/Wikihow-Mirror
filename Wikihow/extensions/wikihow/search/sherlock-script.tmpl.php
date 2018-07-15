<script>
<? //  This script submits data to Sherlock ?>
    $(".result_link").click(function(e) {
		<? // Create the shs input element and add it to the hidden form ?>
		var shs_key = "<input name='shs_key' value='<?=$shs_key?>'>";
		var form = $(this).parent().find(".sherlock-form");
		if (!form.find("input[name = 'shs_key']").length) {
			form.append(shs_key);
			<? // Submit the form to create a new DB entry ?>
			$.post("/Special:SherlockController", form.serialize());
		}
	});
</script>
