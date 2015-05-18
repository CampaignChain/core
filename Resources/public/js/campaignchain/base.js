/*
This file is part of the CampaignChain package.

(c) Sandro Groganz <sandro@campaignchain.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

function popupwindow(url, title, w, h) {
    var left = (screen.width/2)-(w/2);
    //var top = (screen.height/2)-(h/2);
    var top = 200;
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
}

// Round start date to 5 minute increments, because that's the minimum time interval of the scheduler.
function campaignchainRoundMinutes(date){
    m = (Math.round(moment(date).minutes()/5) * 5) % 60; // This did not do the same rounding as DHTMLXGantt does: (((moment(date).minutes() + 7.5)/15 | 0) * 15) % 60;
    h = ((((moment(date).minutes()/105) + .5) | 0) + moment(date).hours()) % 24;
    return moment(date).hours(h).minutes(m);
}

function campaignchainGetUserDateTime(datetime){
    return moment.tz(datetime, window.campaignchainTimezone);
}

function campaignchainShowModal(type, id, api_route, action, successFunction){

    $('#remoteModal').on('hidden.bs.modal', function () {
        // Clean up submitted data before showing this modal.
        $(this).removeData('bs.modal');
        // Make sure we always show the actual remote content
        // and not always the same remote modal content.
        $(this).find(".modal-content").empty();
    });

    switch(type) {
        // window.apiUrl makes it a global variable that can later be used in the function for posting
        // from the modal's form.
        case 'campaign':
            var modalForm = Routing.generate('campaignchain_core_campaign_edit_modal', { id: id });
            break;
        case 'milestone':
            var modalForm = Routing.generate('campaignchain_core_milestone_edit_modal', { id: id });
            break;
        case 'activity':
            var modalForm = Routing.generate('campaignchain_core_activity_edit_modal', { id: id });
            break;
    }

    $('#remoteModal').modal({
        show: true,
        remote: modalForm
    });

    if(api_route){
        window.apiUrl = Routing.generate(api_route, { id: id });

        // Note that we use .one instead of .on here, to make sure the event is fired only once per modal.
        $('.modal').one('submit', 'form', function(e) {
            var $form = $(this);
            var enctype = $form.attr('id')
            var taskId = id + '_' + type;

            if(enctype == 'multipart') {
                var formData = new FormData(this);

                $.ajax({
                    type: $form.attr('method'),
                    url: window.apiUrl,
                    data: formData,
                    mimeType: "multipart/form-data",
                    contentType: false,
                    cache: false,
                    processData: false,

                    success: function(data, status) {
                        $('#remoteModal .modal-content').html(data);
                    }
                });
            }
            else {
                var submitButton = $("input[type='submit'][clicked=true], button[type='submit'][clicked=true]", $form);
                var formData = $form.serializeArray();

                if(submitButton.size() === 1) {
                    formData.push({ name: $(submitButton[0]).attr("name"), value: "1" });
                }
                else if(submitButton.size() !== 0) {
                    console.log("Error: Multiple submit buttons pressed.");
                }

                //console.log(formData);
                $.ajax({
                    type: $form.attr('method'),
                    url: window.apiUrl,
                    data: formData,
                    cache: false,

                    success: function(data, status) {
                        if(successFunction !== undefined){
                            window[successFunction](action, $.parseJSON(data));
                        }

                        $('#remoteModal').modal('hide');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert('URL: ' + apiUrl + ', status: ' + xhr.status + ', message: ' +thrownError);
                    }
                });
            }

            e.preventDefault();
        });

        $('.modal').on("click", 'input[type="submit"], button[type="submit"]', function() {
            $('form[data-async] input[type=submit], form[data-async] button[type=submit]', $(this).parents("form")).removeAttr("clicked");
            $(this).attr("clicked", "true");
        });
    }
}

function campaignchainMoveAction(id, start, type, action, successFunction){
    var postData = { id: id, start_date: start.format(), timezone: window.campaignchainTimezone };

    switch(type){
        case 'campaign':
            var apiUrl = Routing.generate('campaignchain_core_campaign_move_api');
            break;
        case 'milestone':
            var apiUrl = Routing.generate('campaignchain_core_milestone_move_api');
            break;
        case 'activity':
            var apiUrl = Routing.generate('campaignchain_core_activity_move_api');
            break;
    }

    // Post data.
    // TODO: Show spinning icon while saving.
    $.ajax({
        type: 'POST',
        url: apiUrl,
        data: postData,
        dataType: "json",
        cache: false,
        success: function(data, status) {
            // TODO: Show success message in Browser.
            if(successFunction !== undefined){
                window[successFunction](action, $.parseJSON(data));
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            alert('URL: ' + apiUrl + ', status: ' + xhr.status + ', message: ' +thrownError);
        }
    });
}

function campaignchainTplMedium(iconPath, contextIconPath, text){
    if(iconPath == '/'){
        iconPath = contextIconPath.replace('16x16', '32x32');
        contextIconPath = null;
    }

    var image = "<div class='campaignchain-teaser'>";

    image = image + "<img class='icon' src='" + iconPath + "'/>";
    if(contextIconPath && contextIconPath != '/'){
        image = image + "<img class='context-icon' src='" + contextIconPath + "' style='background-image: url(\"" + contextIconPath + "\");'/>"
    }
    return image + "<span class='text'>" + text + "</span></div>";
}

//function campaignchainUserDatetimeRefresh(){
//    var refresh=1000; // Refresh rate in milli seconds
//    mytime=setTimeout('campaignchainDisplayUserDatetime()',refresh)
//}
//
//function campaignchainDisplayUserDatetime() {
//    var strcount;
//    var x = moment().zone(window.campaignchainTimezoneOffset).toString();
//    document.getElementById('campaignchain_user_datetime').innerHTML = x;
//    tt=campaignchainUserDatetimeRefresh();
//}

$(document).ready(function() {
//    campaignchainDisplayUserDatetime();

    var summaries = $('.campaignchain-sticky-heading');
    summaries.each(function(i) {
        var summary = $(summaries[i]);
        var next = summaries[i + 1];

        summary.scrollToFixed({
            marginTop: $('.navbar-fixed-top').outerHeight(),
            limit: function() {
                var limit = 0;
                if (next) {
                    limit = $(next).offset().top  - $(this).outerHeight(true);
                }
                return limit;
            },
            zIndex: 999,
        });
    });

    // Wrap content of sticky heading into a div that allows us to stretch it across the whole screen width.
//    $('.scroll-to-fixed-fixed').wrapInner( "<div class='campaignchain-sticky-heading-inside'></div>" );

    $(".campaignchain-tooltip-top li").tooltip({
        placement : 'top'
    });
});