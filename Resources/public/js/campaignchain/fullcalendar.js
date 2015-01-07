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
    event.start =
        moment(
            $('input[name="' + form_root_name + '[campaignchain_hook_' + event.trigger_identifier + '][' + event.start_date_identifier + ']"]').val()
        );
    event.end =
        moment(
            $('input[name="' + form_root_name + '[campaignchain_hook_' + event.trigger_identifier + '][' + event.end_date_identifier + ']"]').val()
        );

    $('#calendar').fullCalendar('updateEvent', event);
}