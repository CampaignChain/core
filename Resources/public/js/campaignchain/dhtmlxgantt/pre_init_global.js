/*
 This file is part of the CampaignChain package.

 (c) CampaignChain, Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

/*
 Definition of global vars.
 */
var today = campaignchainGetUserDateTime(moment());

/*
 Configuration of DHTMLXGantt properties.
 */
gantt.config.work_time = false;
gantt.config.correct_work_time = false;
gantt.config.duration_unit = 'minute'; // 5 minutes
gantt.config.duration_step = window.campaignchainSchedulerInterval;
// Allow to resize tasks?
gantt.config.drag_resize = false;
// Allow to create dependency links?
gantt.config.drag_links = false;
// Define task type for activity
gantt.config.types["activity"] = "type_id";
gantt.locale.labels['type_' + "activity"] = "Activity";
// Define task type for milestone
gantt.config.types["milestone"] = "type_id";
gantt.locale.labels['type_' + "milestone"] = "Milestone";
// TODO: Make left column collapsible.
gantt.config.progress = false;
gantt.config.grid_width = 240;
gantt.config.row_height = 40;
//gantt.config.autosize = true;

if(window.campaignchainGanttShowButtons == true) {
    gantt.config.columns = [
        {name: "text", label: "Campaigns", tree: true, width: 200},
        {
            name: "buttons",
            label: "",
            tree: false,
            width: 80,
            template: campaignchainGanttColButtons
        }
    ];
} else {
    gantt.config.columns = [
        {name: "text", label: "Campaigns", tree: true, width: 200}
    ];
}

function campaignchainGanttColButtons(task){
    if(task.type == 'campaign') {
        return '<a href="' + Routing.generate(task.route_plan_detail, { id: task.campaignchain_id }) + '" class="btn btn-primary btn-xs">'
            + '<span class="fa fa-pencil"></span>'
            + '</a>';
    }

    return "";
};

gantt.config.touch =  true;
// Disable that dragged task snaps to grid.
gantt.config.round_dnd_dates = false;
gantt.config.time_step = 5;
// Tooltip config.
// Important to ensure smooth movement during onTaskDrag event.
gantt.config.tooltip_timeout = 0;

gantt.attachEvent("onTaskOpened", function(id){
    $("#campaignchain_gantt_today").css("height", $(".gantt_task_bg").innerHeight());
    return true;
});

gantt.attachEvent("onTaskClosed", function(id){
    if($(".gantt_task_bg").innerHeight() > $(".gantt_data_area").innerHeight()){
        var height = $(".gantt_task_bg").innerHeight();
    } else {
        var height = $(".gantt_data_area").innerHeight();
    }
    $("#campaignchain_gantt_today").css("height", height);
    return true;
});

/*
 Normalize the date, i.e. calculate the difference between the browser's
 and the provided date's timezone offset and adjust the date accordingly.

 For example, the browser might be in +01:00 (Europe/Berlin), while
 the timezone configured for CampaignChain is -08:00 (USA/Los Angeles).

 In that case the moment.js date would have to be adjusted by +9:00 hours
 in relation to the UTC date provided by moment.js.
 */
function campaignchainGanttNormalizeDate(date)
{
    var browserOffset = moment().zone();
    var userTimezoneOffset = moment().zone(window.campaignchainTimezoneOffset).zone();

    date = campaignchainGetUserDateTime(date);
    date.utc()
    date.subtract(browserOffset, 'minutes');
    date.add(userTimezoneOffset, 'minutes');
    return date;
}

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
    if(task.type == 'campaign'){
        return task.tpl_teaser + ' ' + task.text;
    }
    return "";
};

gantt.templates.task_class = function(st,end,item){
    return gantt.getChildren(item.id).length ? "gantt_project" : "";
};

// Activities and milestones cannot be resized.
gantt.attachEvent("onBeforeTaskDrag", function(id, mode, e){
    var drag = gantt.config.drag_mode;
    var task = gantt.getTask(id);

    window.campaignchainGanttBeforeTaskDragStartDate = task.start_date;

    // if(mode == drag.resize){
    //     if(task.type == 'activity' || task.type == 'milestone'){
    //         return false;
    //     }
    // }

    return true;
});

// Add a class per type of Action to the column row
gantt.templates.grid_row_class = function(start, end, task){
    return "campaignchain_gantt_" + task.type;
};

gantt.templates.grid_folder = function(item) {
    switch(item.type){
        case 'campaign':
            return item.tpl_teaser;
            break;
    }
};

function campaignchainGetParent(task){
    // Make sure that we operate on the parent task.
    if(gantt.getParent(task.id) != 0){
        task = gantt.getTask(gantt.getParent(task.id));
    }

    return task;
}

/*
 If the task is a Campaign and it does have task children, which are
 campaigns as well, then we know this is a repeating campaign. In that case,
 we dynamically load the data about the child campaigns from the server.
 */
function campaignchainNestedCampaigns(task) {
    if(task.type != 'campaign'){
        return false;
    }

    task = campaignchainGetParent(task);

    if(
        gantt.hasChild(task.id) &&
        gantt.getTask(gantt.getChildren(task.id)[0]).type == 'campaign'
    ){
        // Get the data from the database.
        var route = Routing.generate('campaignchain_core_plan_timeline_nested_campaigns_api', { id: task.campaignchain_id });
        $.getJSON( route, function( data ) {
            // Delete the existing task including its children.
            gantt.deleteTask(task.id);

            // Create the updated task along with its children from scratch.
            var items = [];
            $.each( data, function( key, taskData ) {
                gantt.addTask(taskData);
            });
        });

        return true;
    } else {
        return false;
    }
}

function campaignchainGanttTaskDblClickSuccess(task, data) {
    // Update changes in GANTT data as well.
    if(!campaignchainNestedCampaigns(task)){
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

        // Update the task without children of type "campaign".
        gantt.getTask(task.id).text =
            $('input[name="' + form_root_name + '[name]"]').val();

        gantt.getTask(task.id).start_date = campaignchainGetUserDateTime(
            moment(data.start_date, moment.ISO_8601)
        );
        gantt.getTask(task.id).end_date = campaignchainGetUserDateTime(
            moment(data.end_date, moment.ISO_8601)
        );

        if (gantt.getTask(task.id).type == 'campaign') {
            campaign_end_date = gantt.getTask(task.id).end_date;
        }

        gantt.updateTask(task.id);
    }

    gantt.render();
    gantt.showTask(task.id);
}

// TODO: The end date after dragging is not the same as the last end date _while_ dragging.
// TODO: Perhaps the task id should be part of the route to be consistent?
gantt.attachEvent("onAfterTaskDrag", function(id, mode, e){
    var task = gantt.getTask(id);

    /*
    If this is a campaign which is the child of another campaign (e.g. instances
    of a repeating campaign), then calculate the start date of the parent
    campaign.
     */
    if(task.type == 'campaign' && task.interval){
        var parent = campaignchainGetParent(task);

        if(parent.id != task.id && parent.interval) {
            var start_date = campaignchainGanttNormalizeDate(
                parent.start_date
            );
        } else {
            var start_date = campaignchainGanttNormalizeDate(
                task.start_date
            );
        }
    } else {
        var start_date = campaignchainGanttNormalizeDate(task.start_date);
    }

    var requestData = { id: task.campaignchain_id, start_date: start_date.format() };

    var modes = gantt.config.drag_mode;
    if(mode == modes.move){
        campaignchainMoveAction(task.type, requestData, task, 'campaignchainOnAfterTaskDragSuccess');
    }
});

function campaignchainOnAfterTaskDragSuccess(task, data){
    if(task.type == 'campaign' && !campaignchainNestedCampaigns(task)){
        // Explicitly set end_date of task based on the response data,
        // because DHTMLXGantt seems to adjust the end_date in a strange way.
        var new_end_date = campaignchainGetUserDateTime(data.campaign.new_end_date);
        gantt.getTask(task.id).end_date = new_end_date;
        gantt.updateTask(task.id);
        // Overwrite tooltip's end date info, which is a hack :)
        $(".campaignchain_dhxmlxgantt_tooltip_end_date").html("<b>End:</b> " + new_end_date.format(window.campaignchainDatetimeFormat) + " (" + window.campaignchainTimezoneAbbreviation + ")");
    }

    // gantt.render();
    // gantt.showTask(task.id);
}

gantt.attachEvent("onTaskDrag", function (t) {
    gantt._is_tooltip(t) || this._hide_tooltip()
})

gantt.attachEvent("onMouseMove", function (){
    $(".gantt_tooltip").fadeIn();
});

gantt.attachEvent("onMouseLeave", function (t) {
    gantt._is_tooltip(t) || this._hide_tooltip()
})