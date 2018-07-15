function checkHpInputs() {
	if($('#articleName').val() == "") {
		alert("You must give an article name.");
		return false;
	}
	if ( !$('#ImageUploadFile').val() ) {
		alert("You must upload a file");
		return false;
	}
    if($('#wpDestFile').val().substr(-4) != ".jpg") {
        alert("You must name your image with a .jpg at the end.");
        return false;
    }
}

$(document).ready(function(){
	$('.hp_admin_box').sortable();
	
	$('.hp_delete').click(function() {
		var url = '/Special:WikihowHomepageAdmin?delete='+this.id;
		$.get(url,function(data) {
			if (data == 1) {
				//success!
				location.reload();
			}
			else {
				alert('Your request to delete this specific item was denied for reasons unknown. I blame unicorns.');
			}
		});
	});
});