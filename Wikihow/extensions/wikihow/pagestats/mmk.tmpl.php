<form id="mmk_form">
	Match query to page? Yes <input type="radio" name="match" value="yes" checked="checked" />
	No <input type="radio" name="match" value="no" />
	<textarea id="mmk_why" style="display: none; width:90%;"></textarea>
	<input type="hidden" id="mmk_rank" value="<?= $rank ?>" />
	<a href="#" id="mmk_submit">Submit</a>
</form>
<script type="text/javascript">
	$("input:radio[name=match]").on("change", function(){
		if ($(this).val() == "yes") {
			$("#mmk_why").hide();
		} else {
			$("#mmk_why").show();
		}
	});

	$("#mmk_submit").on("click", function(e){
		e.preventDefault();

		if ($("input:radio[name=match]:checked").val() == "yes") {
			$reason = "";
			$answer = 1;
		} else {
			$reason = $("#mmk_why").val();
			$answer = 0;
		}

		$.ajax({
			url: "/Special:AdminMMKQueries",
			type: 'POST',
			data: {
				answer: $answer,
				reason: $reason,
				action: 'match',
				rank: $("#mmk_rank").val(),
				page: mw.config.get('wgArticleId')
			},
			success: function() {
				$("#mmk_form").hide();
			}
		})
	});
</script>
