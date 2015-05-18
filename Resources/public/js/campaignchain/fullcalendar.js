/*
 This file is part of the CampaignChain package.

 (c) Sandro Groganz <sandro@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

function campaignchainCalendarTaskDblClickSuccess(event, data){
    switch(event.type){
        case 'campaign':
            var form_root_name = "campaignchain_core_campaign";
            break;
        case 'milestone':
            var form_root_name = "campaignchain_core_milestone";
            break;
        case 'activity':
            var form_root_name = "campaignchain_core_activity";
            break;
    }

    var eventId = event.campaignchain_id + '_' + event.type;

    event.title =
        $('input[name="' + form_root_name + '[name]"]').val();
    event.start = campaignchainGetUserDateTime(
        moment(data.start_date, moment.ISO_8601)
    );
    event.end = campaignchainGetUserDateTime(
        moment(data.end_date, moment.ISO_8601)
    );

    $('#calendar').fullCalendar('updateEvent', event);
}

/*
The calendar dates on the client side should be regarded as UTC minus the
user's timezone offset and adjusted accordingly before storing them directly
into the database, e.g. via AJAX.
 */
function campaignchainCalendarNormalizeDate(date)
{
    // Clone the event object, because we don't want below changes
    // to the date to influence it.
    var date = moment(date);

    var browserOffset = moment().zone();
    var userTimezoneOffset = moment().zone(window.campaignchainTimezoneOffset).zone();

    date.subtract(browserOffset, 'minutes');
    return date.add(userTimezoneOffset, 'minutes');
}