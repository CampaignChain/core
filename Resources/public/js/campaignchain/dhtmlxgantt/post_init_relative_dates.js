/*
 This file is part of the CampaignChain package.

 (c) CampaignChain Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

gantt.templates.task_class = function(start, end, task){
    if(task.type == 'campaign'){
        var class_name = "campaignchain_gantt_upcoming campaignchain_gantt_campaign";
    }
    if(task.type == 'activity'){
        var class_name = class_name + " campaignchain_gantt_activity";
    }
    if(task.type == 'milestone'){
        var class_name = class_name + " campaignchain_gantt_milestone";
    }

    return class_name;
}