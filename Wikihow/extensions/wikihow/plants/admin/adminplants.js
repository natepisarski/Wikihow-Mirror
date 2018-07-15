window.WH = window.WH || {};

window.WH.AdminPlants = function() {

	toolUrl = "/Special:AdminPlants";

	$( ".ui-sortable" ).sortable().disableSelection();

	$("#tool_select").on("change", function(e){
		$("#tool_select_form").attr("action", toolUrl + "/" + $(this).val())
		$("#tool_select_form").submit();
	});

	$(".plant_save").on("click", function(e){
		e.preventDefault();
		plants = [];
		displayOrder = 0;
		$(".ui-sortable li").each(function(index){
			groupActive = null;
			activeElem = $(this).children("input");
			if ($(activeElem).length > 0) {
				groupActive = $(activeElem)[0].checked;
			}

			$(this).find(".plant").each(function(){
				var plant = {};
				plant.id = $(this).find(".plant_id").html();
				active = false;
				if (groupActive == null) {
					active = $(this).find("input")[0].checked;
				}
				if (groupActive || active) {
					plant.display = displayOrder;
				} else {
					plant.display = -1;
				}
				if (groupActive == null && active == true) {
					displayOrder++;
				}
				plants.push(plant);
			})
			if (groupActive == true) {
				displayOrder++;
			}

		});

		$.post(window.href, {plants: plants, action: "save"});
	});
}

$(document).ready(function() {
	WH.AdminPlants();
});