/*
Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

(function() {
    var externalLinkPattern = new RegExp(
        // Should have a protocol or is a protocol relative
        "^(https?:)?//" +
        // Should not be the current pages' host
        "(?!"+ regexEscape(location.host) +")"
    );

    // Mark all external links inside the given element(s) as such. Should be called once
    // in $(document).ready() and after content has been updated via AJAX.
    $.fn.markExternalLinks = function() {
        this.find('a').addClass(function() {
            var classes = [];
            var $link = $(this);
            var isExternal = ($link.attr('href') || "").match(externalLinkPattern);
            if (isExternal) {
                classes.push("external");
                // Link contains image, so don't add the external link indicator
                if ($link.find('img').length > 0) {
                    classes.push("external-noicon");
                }
            }

            return classes.join(" ");
        });
        return this;
    };
}());

function regexEscape(string){
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

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

function campaignchainShowEditModal(type, id, api_route, action, successFunction){

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
        $('.modal').on('submit', 'form', function(e) {
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
                        var response = $.parseJSON(data);
                        console.log(data);
                        if(response["success"] === false){
                            // Remove existing warning
                            $('#remoteModal .modal-body .alert').remove();
                            // Create new warning
                            $('#remoteModal .modal-body').prepend(
                                '<div class="alert alert-warning alert-dismissable">' +
                                    '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                                    '<h4><i class="icon fa fa-warning"></i> Warning</h4>'
                                    + response["message"] +
                                '</div>'
                                );
                            $('html, body').animate({
                                scrollTop: $('#remoteModal').offset().top
                            }, 1000);
                        } else {
                            if (successFunction !== undefined) {
                                window[successFunction](action, response);
                            }

                            if(type == "campaign" || type == "milestone"){
                                $('#remoteModal').modal('hide');
                            } else {
                                // Avoid that previous form gets submitted again.
                                $('.modal').off('submit');

                                if (response["status"] == 'closed') {
                                    $('#remoteModal .modal-content').load(modalForm);
                                } else {
                                    $('#remoteModal').modal('hide');
                                }
                            }
                        }
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

function campaignchainShowReadModal(route){

    $('#remoteModal').on('hidden.bs.modal', function () {
        // Clean up submitted data before showing this modal.
        $(this).removeData('bs.modal');
        // Make sure we always show the actual remote content
        // and not always the same remote modal content.
        $(this).find(".modal-content").empty();
    });

    $('#remoteModal').modal({
        show: true,
        remote: route
    });
}

function campaignchainMoveAction(type, requestData, task, successFunction){

    requestData['timezone'] = window.campaignchainTimezone;

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
        data: requestData,
        dataType: "json",
        cache: false,
        success: function(responseData, status) {
            // TODO: Show success message in Browser.
            if(successFunction !== undefined){
                window[successFunction](task, responseData);
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

function campaignchainToggleStatus(url, data, element, id, dependencies) {
    $this = $(element);
    $this.prop('disabled', true);
    var $icon = $this.find("i");

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        dataType: "json",
        success: function (response) {
            $this.tooltip("hide");
            if(response[0]["status"] == "inactive"){
                $this.attr("data-original-title", "Inactive");
                $icon.removeClass("fa-toggle-on").addClass("fa-toggle-off");
            } else {
                $this.attr("data-original-title", "Active");
                $icon.removeClass("fa-toggle-off").addClass("fa-toggle-on");
            }
            if(dependencies.constructor === Array){
                dependencies.forEach(function(entry) {
                    switch (entry){
                        case 'connect-location':
                            campaignchainToggleConnectLocation(id, response[0]["status"])
                            break;
                    }
                });
            }
            $this.prop('disabled', false);
            $this.tooltip("toggle");
            $this.mouseleave(function( event ) {
                $this.tooltip("hide");
            });
        }
    })
}

function campaignchainToggleConnectLocation(id, status)
{
    var $connect = $('[data-campaignchain-id="' + id +'"][data-campaignchain-toggle="connect-location"]');

    if(status == "inactive"){
        $connect.attr('disabled', 'disabled');
    } else {
        $connect.removeAttr('disabled');
    }
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

var fullWindow = false;

function campaignchainFullWindow(element, callback){
    // expanding the gantt to full screen
    $(element).toggleClass('campaignchain-fullwindow');
    $('.modal').css('z-index', '99999');
    if(!window.fullWindow){
        window.fullWindow = true;
        $('.fa-expand').removeClass('fa-expand').addClass('fa-compress');
        if(callback) {
            window[callback](window.fullWindow);
        }
    } else {
        window.fullWindow = false;
        $('.fa-compress').removeClass('fa-compress').addClass('fa-expand');
        if(callback) {
            window[callback](window.fullWindow);
        }
    }
}

$(document).ready(function() {
    $(document.body)
        .markExternalLinks()
        // Register on the external link handler on <body> so clicks on elements added
        // later can be intercepted. Also, when one of the click handlers further down
        // calls preventDefault(), we respect that and do nothing.
        .on("click", ".external", function(e) {
            if (!e.isDefaultPrevented()) {
                e.preventDefault();
                window.open(this.href, '_blank');
            }
        });

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

    // Bootstrap tooltip
    $(function () {
        $('[data-tooltip="true"]').tooltip();
    })
});