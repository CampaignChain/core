/*
 This file is part of the CampaignChain package.

 (c) CampaignChain, Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

// Mark today with a line
gantt.attachEvent("onGanttRender", onGanttRender_todayLine(today));

function onGanttRender_todayLine(today) {
    return function f() {
        var $today = $("#campaignchain_gantt_today");
        if (!$today.length) {
            var elem = document.createElement("div");
            elem.id = "campaignchain_gantt_today";
            elem.setAttribute('data-tooltip', 'true');
            elem.setAttribute('data-placement', 'right');
            elem.setAttribute('data-original-title', 'Today');

            gantt.$task_data.appendChild(elem);
            $today = $(elem);
        }

        var x_start = gantt.posFromDate(today);
        var x_end = gantt.posFromDate(today.add(1, 'minute'));

        // Show the today line only if start date of time scale is prior to today.
        if(x_end != 0){
            $today.css("left", Math.floor(x_start + 0.5 * (x_end - x_start)) + "px");
            if($(".gantt_data_area").innerHeight() > $(".gantt_task_bg").innerHeight()){
                var css_height = $(".gantt_data_area").innerHeight();
            } else {
                var css_height = $(".gantt_task_bg").innerHeight();
            }
            $today.css("height", css_height);
        }
    };
}

gantt.templates.task_class = function(start, end, task){
    if(+campaignchainGetUserDateTime(start) < +today.subtract(1, "minute") && +campaignchainGetUserDateTime(end) > +today){
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