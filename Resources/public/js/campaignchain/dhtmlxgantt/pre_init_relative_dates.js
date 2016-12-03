/*
 This file is part of the CampaignChain package.

 (c) CampaignChain, Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

// Make sure that activities and milestones cannot be moved
// beyond the campaign duration.
// Furthermore, make sure that they move together with a campaign
// if a campaign is being moved.

function limitMoveLeft(task, limit){
    var dur = task.end_date - task.start_date;
    task.end_date = new Date(limit.end_date);
    task.start_date = new Date(+task.end_date - dur);
}
function limitMoveRight(task, limit){
    var dur = task.end_date - task.start_date;
    task.start_date = new Date(limit.start_date);
    task.end_date = new Date(+task.start_date + dur);
}

function limitResizeLeft(task, limit){
    task.end_date = new Date(limit.end_date);
}
function limitResizeRight(task, limit){
    task.start_date = new Date(limit.start_date)
}

// When dragging a campaign, move the activities and milestones with it.
gantt.attachEvent("onTaskDrag", function(id, mode, task, original, e){
    var parent = task.parent ? gantt.getTask(task.parent) : null,
        children = gantt.getChildren(id),
        modes = gantt.config.drag_mode;

    var limitLeft = null,
        limitRight = null;

    if(!(mode == modes.move || mode == modes.resize)) return;

        if(mode == modes.move){
            limitLeft = limitMoveLeft;
            limitRight = limitMoveRight;
        }else if(mode == modes.resize){
            limitLeft = limitResizeLeft;
            limitRight = limitResizeRight;
        }

        //check parents constraints
        if(parent && +parent.end_date < +task.end_date){
            limitLeft(task, parent);
        }
        if(parent && +parent.start_date > +task.start_date){
            limitRight(task, parent);
        }

        if(mode == modes.move){
            var diff = task.start_date - original.start_date;
            gantt.eachTask(function(child){
                child.start_date = new Date(+child.start_date + diff);
                child.end_date = new Date(+child.end_date + diff);
                gantt.refreshTask(child.id, true);
            },id );
        }

    // If moving an activity or milestone, make sure it does not move beyond the campaign's start or end date.
    if(parent && mode == modes.move){
        if(+task.start_date < +parent.start_date){
            task.start_date = parent.start_date;
        }
    }

//    console.log('End date while dragging: ' + task.end_date);
});

// Campaigns where the start date is in the past cannot be moved.
gantt.attachEvent("onBeforeTaskDrag", function(id, mode, e){
    var task = gantt.getTask(id);

    if(task.type == 'campaign'){
        return false;
    }

    return true;
});

// Custom tooltip text
gantt.templates.tooltip_text = function (start_date, end_date, e) {
    start_date = campaignchainRoundMinutes(start_date);
    // Adjust to user timezone.
//    start_date = campaignchainGetUserDateTime(start_date);

    switch (e.type){
        case 'campaign':
            var ms = moment(campaign_end_date).diff(campaign_start_date);
            var days = Math.floor(moment.duration(ms).asDays());
            return "<b>Duration:</b> " + days + " days";
            break;
        case 'milestone':
        case 'activity':
            var ms = moment(start_date).diff(campaign_start_date);
            var days = Math.floor(moment.duration(ms).asDays());
            var days = +days+1;
            return "<b>Due:</b> Day " + ('00' + days).slice(-3) + ", " + start_date.format(window.campaignchainTimeFormat);
            break;
    }
};

// Show the edit dialogue when someone clicks on a task.
gantt.attachEvent("onTaskDblClick", function(id,e){
    // Fade out tooltip while modal for editing task is visible.
    $(".gantt_tooltip").fadeOut();

    var task = gantt.getTask(id);

    campaignchainShowEditModal(
        task.type, task.campaignchain_id, task.route_edit_api,
        task, 'campaignchainGanttTaskDblClickSuccess'
    );

    return false;
});

// TODO: Disable that parent links between activity/milestone and campaign can be deleted or changed.
// TODO: Move increments: 5 mins for day view, 30 mins for week view
// TODO: Allow resizing of campaign only to first and last activity or milestone.