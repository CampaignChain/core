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
    var diff = task.start_date - original.start_date;

    // If these are instances of a campaign with an interval, then don't move
    // the children if the parent campaign touches the today line.
    if(parent && parent.interval && parent.type == task.type && +parent.start_date <= +today.subtract(1, "minute") && +diff <= 0 ){
        task.start_date = original.start_date;
        task.end_date = original.end_date;
        gantt.refreshTask(task.id);
        return true;
    }

    var limitLeft = null,
        limitRight = null;

    var parentId = task.id;

    if(parent && parent.type == task.type){
        var parentId = parent.id;
    }

    if(!(mode == modes.move || mode == modes.resize)) return;

    if(+campaignchainGetUserDateTime(task.start_date) >= +today){
        if(mode == modes.move){
            limitLeft = limitMoveLeft;
            limitRight = limitMoveRight;
        } else if(mode == modes.resize){
            limitLeft = limitResizeLeft;
            limitRight = limitResizeRight;
        }

        // Check parents constraints if child task is within parent duration
        if(parent && parent.type != task.type && +parent.end_date < +task.end_date){
            limitLeft(task, parent);
        }
        if(parent && parent.type != task.type && +parent.start_date > +task.start_date){
            limitRight(task, parent);
        }

        if(mode == modes.move){
            if(parent && parent.interval && parent.type == task.type){
                parent.start_date = new Date(+parent.start_date + diff);
                parent.end_date = new Date(+parent.end_date + diff);
                gantt.refreshTask(parent.id, true);
            }
            gantt.eachTask(function (child) {
                child.start_date = new Date(+child.start_date + diff);
                child.end_date = new Date(+child.end_date + diff);
                gantt.refreshTask(child.id, true);
            }, parentId);
        }
    }

    // If moving an activity or milestone, make sure it does not move beyond the campaign's start or end date.
    if(parent && parent.interval && parent.type != task.type && mode == modes.move){
        if(+task.start_date < +parent.start_date){
            task.start_date = parent.start_date;
        }
//        if(+task.start_date > +parent.end_date){
//            task.start_date = parent.end_date;
//            console.log(task.start_date);
//        }
    }

//    console.log('End date while dragging: ' + task.end_date);
});

// Avoid that campaigns that have not
// started yet can be moved beyond today's date.

gantt.attachEvent("onTaskDrag", function(id, mode, task, original, e){
    var modes = gantt.config.drag_mode;
    if(mode == modes.move || mode == modes.resize){

        if(+campaignchainGetUserDateTime(task.start_date) <= +today){
            task.start_date = original.start_date;
            if(mode == modes.move){
                task.end_date = original.end_date;
            }
        }
    }
    return true;
});

// Campaigns where the start date is in the past cannot be moved.
gantt.attachEvent("onBeforeTaskDrag", function(id, mode, e){
    var task = gantt.getTask(id);
    if(+campaignchainGetUserDateTime(task.start_date) < +today){
        return false;      //denies dragging
    }
    return true;           //allows dragging
});




// If an activity or milestone is in the past, don't allow
// to create a dependency from or to it
gantt.attachEvent("onBeforeLinkAdd", function(id,link){
    var task_source = gantt.getTask(link.source);
    var task_target = gantt.getTask(link.target);

    if (
        +campaignchainGetUserDateTime(task_source.start_date) < +today ||
        +campaignchainGetUserDateTime(task_source.end_date) < +today ||
        +campaignchainGetUserDateTime(task_target.start_date) < +today ||
        +campaignchainGetUserDateTime(task_target.end_date) < +today
        ){
        return false;
    }

    // TODO: Do not allow to create a dependency between an activity or milestone to a campaign.
    // TODO: Allow dependencies only between campaigns or between activities and milestones.
    return true;
});

// Custom tooltip text
gantt.templates.tooltip_text = function (start_date, end_date, e) {
    start_date = campaignchainRoundMinutes(start_date);
    // Adjust to user timezone.
//    start_date = campaignchainGetUserDateTime(start_date);

    switch (e.type){
        case 'campaign':
            end_date = campaignchainRoundMinutes(end_date);

            var tooltip = '';

            if(e.interval){
                tooltip = "<b>Interval:</b> " + e.interval + "</br>";
            }

            tooltip = tooltip + "<b>Start:</b> " + start_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ") <br/><span class='campaignchain_dhxmlxgantt_tooltip_end_date'><b>End:</b> " + end_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ")</span>";

            return tooltip;
            break;
        case 'milestone':
        case 'activity':
            return "<b>Due:</b> " + start_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ")";
            break;
    }
};

// Show the edit dialogue when someone clicks on a task.
gantt.attachEvent("onTaskDblClick", function(id,e){
    // Fade out tooltip while modal for editing task is visible.
    $(".gantt_tooltip").fadeOut();

    var task = gantt.getTask(id);

    // Done tasks cannot be edited.
    var today = campaignchainGetUserDateTime(moment());
    if(+campaignchainGetUserDateTime(task.start_date) < +today && +campaignchainGetUserDateTime(task.end_date) < +today){
        return false;
    }

    campaignchainShowModal(
        task.type, task.campaignchain_id, task.route_edit_api,
        task, 'campaignchainGanttTaskDblClickSuccess'
    );

    return false;
});

// TODO: Disable that parent links between activity/milestone and campaign can be deleted or changed.
// TODO: Move increments: 5 mins for day view, 30 mins for week view
// TODO: Allow resizing of campaign only to first and last activity or milestone.