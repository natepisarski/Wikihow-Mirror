$(document).ready(function(){
	$("#mmk_clear").on("click", function(){
		$articles = $("#mmk_articles").val();
		if ($articles != "") {
			$.ajax({
				url: "/Special:AdminMMKQueries",
				type: 'POST',
				data: {
					articles: $articles,
					action: 'clear'
				},
				success: function() {
					$("#mmk_response").html("Articles cleared");
				}
			})
		}
	});
});