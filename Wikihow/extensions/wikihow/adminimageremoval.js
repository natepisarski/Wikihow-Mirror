
$(document).ready(function(){
	$("#imageremoval").submit(function(e){
		e.preventDefault();

		$("#imageremoval input").addClass("disabled");
		$("#imageremoval_results").html("");

		$.ajax({
			type: "POST",
			url: "/Special:AdminImageRemoval",
			dataType: "json",
			data: {
				urls: $("#imageremoval #urls").val()
			},
			success: function( data ) {
				$("#imageremoval input").removeClass("disabled");

				if(data.success) {
					$("#imageremoval_results").html("The urls have had images removed from them.");
				}
				else {
					results = "We were not able to process the following urls:<br />";
					for (var i=0; i<data['errors'].length; i++) {
						results += data['errors'][i] + "<br />";
					}

					$("#imageremoval_results").html(results);
				}
			}
		});
	});
});