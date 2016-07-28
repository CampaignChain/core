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
gantt.config.duration_step = 5;
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
gantt.config.columns = [
    {name:"text", label:"Campaigns, Activities, Milestones", tree:true, width:200 },
//        {name:"start_date", label:"Start Date", align: "center", width:100 },
//        {name:"end_date", label:"End Date", align: "center", width:100 },
];
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
    if(mode == drag.resize){
        if(task.type == 'activity' || task.type == 'milestone'){
            return false;
        }
    }
    return true;
});

// Add a class per type of Action to the column row
gantt.templates.grid_row_class = function(start, end, task){
    return "campaignchain_gantt_" + task.type;
};

gantt.templates.grid_folder = function(item) {
    switch(item.type){
        case 'campaign':
            //return '<img src="/bundles/campaignchaincampaignscheduledcampaign/images/icons/24x24/scheduled_campaign.png" class="campaignchain_gantt_icon_column_campaign" />';
            return item.tpl_teaser;
            break;
    }
};

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

    gantt.getTask(taskId).start_date = campaignchainGetUserDateTime(
        moment(data.start_date, moment.ISO_8601)
    );
    gantt.getTask(taskId).end_date = campaignchainGetUserDateTime(
        moment(data.end_date, moment.ISO_8601)
    );

    if(gantt.getTask(taskId).type == 'campaign'){
        campaign_end_date = gantt.getTask(taskId).end_date;
    }

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

gantt.attachEvent("onMouseMove", function (){
    $(".gantt_tooltip").fadeIn();
});

gantt.attachEvent("onMouseLeave", function (t) {
    gantt._is_tooltip(t) || this._hide_tooltip()
})
