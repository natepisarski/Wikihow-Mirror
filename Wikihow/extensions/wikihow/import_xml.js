function delete_xml(id, title) {
	if (confirm("Are you sure you want to delete '" + title + "'?")) {
		$('#magicbox').load('/Special:ImportXML?delete=' + id);
		$('#row_' +id).fadeOut();
	}
	return false;
}
function edit_xml(id) {
    //jQuery('#dialog-box').load(url);
    $('#magicbox').dialog({
        width: 620,
        height: 450,
        modal: true,
        title: 'Edit Article',
        show: 'slide',
        closeOnEscape: true,
        position: 'center'
		closeText: 'Close',
    });
	$('#magicbox').load('/Special:ImportXML?view=' + id);
}

function save_xml (id) {

	$.post('/Special:ImportXML?update=' + id,
			{
				text: $('#xml_input').val()
			},
		function (data) {
				$("#magicbox").dialog('close');
		}
	);
}

function preview_xml(id) {
    //jQuery('#dialog-box').load(url);
    $('#magicbox').dialog({
        width: 1024,
        height: 800,
        modal: true,
        title: 'Preview Article',
        show: 'slide',
        closeOnEscape: true,
        position: 'center'
    });
    $('#magicbox').load('/Special:ImportXML?preview=' + id);
}

var hidden = false;
function hidePublished() {
	if (hidden) {
		$(".pub").fadeOut();
		$("#hide_btn").html("Show Published");		
		hidden = false;	
	} else {
		$(".pub").fadeIn();
		$("#hide_btn").html("Hide Published");		
		hidden = true;	
	}
}
