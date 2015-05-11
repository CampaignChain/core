/*
 This file is part of the CampaignChain package.

 (c) Sandro Groganz <sandro@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

gantt.templates.task_class = function(start, end, task){
    if(+campaignchainGetUserDateTime(start) < +today && +campaignchainGetUserDateTime(end) > +today){
        // Give running campaigns a different color.
        var class_name = "campaignchain_gantt_ongoing";
    }
    if (+campaignchainGetUserDateTime(start) < +today && +campaignchainGetUserDateTime(end) < +today){
        // Give done campaigns a different color.
        var class_name = "campaignchain_gantt_done";
    }
    if (+campaignchainGetUserDateTime(start) > +today && +campaignchainGetUserDateTime(end) > +today){
        // Give upcoming campaigns a different color.
        var class_name = "campaignchain_gantt_upcoming";
    }
    if(task.type == 'campaign'){
        var class_name = class_name + " campaignchain_gantt_campaign";
    }
    if(task.type == 'activity'){
        var class_name = class_name + " campaignchain_gantt_activity";
    }
    if(task.type == 'milestone'){
        var class_name = class_name + " campaignchain_gantt_milestone";
    }

    return class_name;
}