/*
 This file is part of the CampaignChain package.

 (c) Sandro Groganz <sandro@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

function modToolbarHeight(){
    var headHeight = $('#campaignchain-fullscreen-header').outerHeight(true)+$('.btn-toolbar').outerHeight(true);
    var sch = document.getElementById("gantt_here");

    sch.style.height = (parseInt(document.body.offsetHeight)-headHeight-30)+"px";
//            var contbox = document.getElementById("contbox");
//            contbox.style.width = (parseInt(document.body.offsetWidth)-300)+"px";
    gantt.setSizes();
}

// Make sure the vertical scroll bar still shows up if window is being resized in full screen view.
$( window ).resize(function() {
    modToolbarHeight();
});

// Keep the tooltip close to the cursor.
window.onmousemove = function (e) {
    var x = e.clientX,
        y = e.clientY;
    $(".gantt_tooltip").css('top', (y + 90) + 'px');
    $(".gantt_tooltip").css('left', (x + 10) + 'px');
};

//gantt.attachEvent("onTaskLoading", function(task){
//    task.start_date = campaignchainGetUserDateTime(task.start_date);
//    task.end_date = campaignchainGetUserDateTime(task.end_date);
//
//    return true;
//});

// Position text at right side
gantt.templates.rightside_text = function(start, end, task){
    if(task.type == 'milestone' || task.type == 'activity'){
        return task.text;
    }
    return "";
};

gantt.templates.task_class = function(st,end,item){
    return gantt.getChildren(item.id).length ? "gantt_project" : "";
};

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

// Activities and milestones cannot be resized.
gantt.attachEvent("onBeforeTaskDrag", function(id, mode, e){
    var drag = gantt.config.drag_mode;
    var task = gantt.getTask(id);
    if(mode == drag.resize){
        if(task.type == 'activity' || task.type == 'milestone'){
            return false;
        }
    }
    return true;
});

// When dragging a campaign, move the activities and milestones with it.
// TODO: When campaign hits today line several times, children move further to the right.
gantt.attachEvent("onTaskDrag", function(id, mode, task, original, e){
    var parent = task.parent ? gantt.getTask(task.parent) : null,
        children = gantt.getChildren(id),
        modes = gantt.config.drag_mode;

    var limitLeft = null,
        limitRight = null;

    if(!(mode == modes.move || mode == modes.resize)) return;

    if(+campaignchainGetUserDateTime(task.start_date) >= +today){
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
    }

    // If moving an activity or milestone, make sure it does not move beyond the campaign's start or end date.
    if(parent && mode == modes.move){
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

        if(+campaignchainGetUserDateTime(task.start_date) == +today || +campaignchainGetUserDateTime(task.start_date) < +today){
            task.start_date = campaignchainGetUserDateTime(moment());
            if(mode == modes.move){
                task.end_date = moment(new Date(+task.start_date + campaignchainGetUserDateTime(original.duration*(1000*60*60*24))));
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

// Show the channel icon in the left column
gantt.templates.grid_blank = function(item) {
    switch(item.type){
        case 'milestone':
            return "<img src='" + item.icon_path_16px + "' class='campaignchain_gantt_icon_column' />";
            break;
        case 'activity':
            return item.location_tpl;
            break;
    }

    return "<div class='gantt_tree_icon gantt_blank'><span class='fa fa-exclamation-triangle'></span></div>";
};

gantt.templates.grid_folder = function(item) {
    switch(item.type){
        case 'campaign':
            return '<img src="/bundles/campaignchaincampaignscheduledcampaign/images/icons/24x24/scheduled_campaign.png" class="campaignchain_gantt_icon_column_campaign" />';
            break;
    }
};

// Show the edit dialogue when someone clicks on a task.
gantt.attachEvent("onTaskDblClick", function(id,e){
    // TODO: Fade in/out tooltip while modal for editing task is visible.
    var task = gantt.getTask(id);

    campaignchainShowModal(
        campaignchainGetUserDateTime(task.start_date),
        campaignchainGetUserDateTime(task.end_date),
        task.type, task.campaignchain_id, task.route_edit_api,
        task, 'campaignchainGanttTaskDblClickSuccess'
    );

    return false;
});

function campaignchainGanttTaskDblClickSuccess(task, data) {
    // Update changes in GANTT data as well.
    switch(task.type){
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

    var taskId = task.campaignchain_id + '_' + task.type;

    gantt.getTask(taskId).text =
        $('input[name="' + form_root_name + '[name]"]').val();
    gantt.getTask(taskId).start_date =
        moment(
            $('input[name="' + form_root_name + '[campaignchain_hook_' + task.trigger_identifier + '][' + task.start_date_identifier + ']"]').val()
        );
    gantt.getTask(taskId).end_date =
        moment(
            $('input[name="' + form_root_name + '[campaignchain_hook_' + task.trigger_identifier + '][' + task.end_date_identifier + ']"]').val()
        );

    gantt.updateTask(taskId);
    gantt.render();
}

// Persist onTaskDrag changes.

// TODO: The end date after dragging is not the same as the last end date _while_ dragging.
// TODO: Perhaps the task id should be part of the route to be consistent?
gantt.attachEvent("onAfterTaskDrag", function(id, mode, e){
    var task = gantt.getTask(id);
    var modes = gantt.config.drag_mode;
    // Adjust to user timezone and ISO8601 format.
    var start_date = campaignchainGanttNormalizeDate(task.start_date);
//    console.log('End date after dragging: ' + task.end_date);

    if(mode == modes.move){
        campaignchainMoveAction(task.campaignchain_id, start_date, task.type, task, 'campaignchainOnAfterTaskDragSuccess');
    }
});

function campaignchainOnAfterTaskDragSuccess(task, data){
    if(task.type == 'campaign'){
        // Explicitly set end_date of task based on the response data,
        // because DHTMLXGantt seems to adjust the end_date in a strange way.
        var new_end_date = campaignchainGetUserDateTime(data.campaign.new_end_date);
        gantt.getTask(task.id).end_date = new_end_date;
        gantt.updateTask(task.id);
        // Overwrite tooltip's end date info, which is a hack :)
        $(".campaignchain_dhxmlxgantt_tooltip_end_date").html("<b>End:</b> " + new_end_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ")");
    }
}

gantt.attachEvent("onTaskDrag", function (t) {
    gantt._is_tooltip(t) || this._hide_tooltip()
})

gantt.attachEvent("onMouseLeave", function (t) {
    gantt._is_tooltip(t) || this._hide_tooltip()
})

// Custom tooltip text
gantt.templates.tooltip_text = function (start_date, end_date, e) {
    start_date = campaignchainRoundMinutes(start_date);
    // Adjust to user timezone.
//    start_date = campaignchainGetUserDateTime(start_date);

    switch (e.type){
        case 'campaign':
            end_date = campaignchainRoundMinutes(end_date);

            // Adjust to user timezone.
//            end_date = campaignchainGetUserDateTime(end_date);

            return "<b>Start:</b> " + start_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ") <br/><span class='campaignchain_dhxmlxgantt_tooltip_end_date'><b>End:</b> " + end_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ")</span>";
            break;
        case 'milestone':
        case 'activity':
            return "<b>Due:</b> " + start_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ")";
            break;
    }
};

// TODO: Disable that parent links between activity/milestone and campaign can be deleted or changed.
// TODO: Move increments: 5 mins for day view, 30 mins for week view
// TODO: Allow resizing of campaign only to first and last activity or milestone.