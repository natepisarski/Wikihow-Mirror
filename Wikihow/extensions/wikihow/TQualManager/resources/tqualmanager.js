
$(document).ready(function()
{
    // This functions gets the existing qualifications for a worker
    // populates checkboxes dynamically -- new qualifications can be added
    // anytime
    $('#workerid-submit').click(function()
    {
        // send the workerid
        var jdata = {'worker_id':$('#workerid').val()};
        jdata['action'] = 'get_qual_list';

        var promise = doAjax(jdata);
        promise.then(data => {
            // populate new textboxes based on resutls
            // clear old checkboxes
            $("#cblist :checkbox").parent().remove();
            $("#checkbox-container").append('<div id="cblist"></div>');

            var quallist = $.parseJSON(data);
            Object.keys(quallist).forEach(function(key) {
                console.log('Key : ' + key + ', Value : ' + quallist[key])
                addCheckbox(key,quallist[key]);
            });
        }).catch(error =>
        {
            console.log("The following error occurred while retrieving qualifications: "+ JSON.stringify(error));
        });
    });


    // Save any changes made to quals
    $('#qual-submit').click(function()
    {
        if ($.isEmptyObject(checkboxes_changed)) {
            alert ("Nothing to save; did you want to add or remove qualification for a worker?");
        } else {
            var answer = confirm("Do you want to make changes to the qualifications for the selected worker");
            if (answer) {
                // collect data
                var jdata = {'worker_id':$('#workerid').val()};
                jdata['action'] = 'save_quals';
                var qual_changes = {};
                $.each( checkboxes_changed, function( key, value ) {
                    qual_changes[key] = value;
                });

                jdata['qual_changes'] =  JSON.stringify(qual_changes);
                console.log(jdata);

                var promise = doAjax(jdata);
                promise.then(data => {
                    $("#m_status").html('Update:' + JSON.stringify(data));
                });
                promise.catch(error => {
                    console.log("The following error occurred: "+ JSON.stringify(error));
                });
            } else {
                console.log('Save canceled');
            }
        }
    });


    //Send an email to a worker
    $('#message-submit').click(function()
    {
        if ($.trim($('#email-workerid').val()) == "") {
            alert ("No worker id provided, enter a worker id in the textbox below 'Message Worker'");
        } else if ($.trim($('#email-message').val()) == "") {
            alert ("No message to send, write your message in the big text area under 'Message Worker'");
        } else {
            var jdata = {'worker_id':$('#email-workerid').val()};
            jdata['email_message'] = $('#email-message').val();
            jdata['action'] = 'send_message';

            console.log(jdata);
            var promise = doAjax(jdata);

            promise.then(data => {
                console.log('return data ' + data);
                $("#m_status").html('Update:' + JSON.stringify(data));
            });
            promise.catch(error => {
                console.log("The following error occurred: "+ JSON.stringify(error));
            });
        }
    });
});

// keeps track of any changes to qual in checkbox_changed
//
var checkboxes_changed = {};
$(document).on('change', '[type=checkbox]', function (e)
{
    checkboxes_changed[$(this).next('label').text()] = this.checked;
});

// generalized ajax calls posts data to the main page
// returns a promise
function doAjax(jdata) {
    return Promise.resolve($.ajax(
    {
        type: "POST",
        url: '/Special:TQualManager',
        data:jdata
    }));
}

//add checkboxes
// name - value and label of the checkbox
//checked - if the checkbox is checked or not
function addCheckbox(name, checked) {
    var container = $('#cblist');
    var inputs = container.find('input');
    var id = inputs.length+1;

    if (checked) {
        $('<input />', { type: 'checkbox', id: 'cb'+id, value: name , checked}).appendTo(container);
    }
    else {
        $('<input />', { type: 'checkbox', id: 'cb'+id, value: name }).appendTo(container);
    }
    $('<label />', { 'for': 'cb'+id, text: name }).appendTo(container);
}
