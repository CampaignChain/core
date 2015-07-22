/*
 This file is part of the CampaignChain package.

 (c) Sandro Groganz <sandro@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

gantt.templates.task_text = function(start, end, task){
    switch(task.type){
        case 'campaign':
            return '';
            break;
        case 'milestone':
            return "<img src='" + task.icon_path_24px + "' class='campaignchain_gantt_icon_timeline' />"
            break;
        case 'activity':
            return task.tpl_teaser;
            break;
    }
    return task.text;
};