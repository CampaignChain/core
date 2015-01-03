/*
 This file is part of the CampaignChain package.

 (c) Sandro Groganz <sandro@campaignchain.com>

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
//gantt.config.autosize = true;
gantt.config.columns = [
    {name:"text", label:"Campaigns, Activities, Milestones", tree:true, width:100 },
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

// Mark today with a line
gantt.attachEvent("onGanttRender", onGanttRender_todayLine(today));


// TODO: Seems like the calculated time of the day is not correct.
function onGanttRender_todayLine(today) {
    return function f() {
        var $today = $("#campaignchain_gantt_today");
        if (!$today.length) {
            var elem = document.createElement("div");
            elem.id = "campaignchain_gantt_today";
            gantt.$task_data.appendChild(elem);
            $today = $(elem);
        }

        var x_start = gantt.posFromDate(today);
        var x_end = gantt.posFromDate(today.add(1, 'minute'));
        $today.css("left", Math.floor(x_start + 0.5 * (x_end - x_start)) + "px");
    };
}