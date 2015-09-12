/*
 This file is part of the CampaignChain package.

 (c) CampaignChain Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

function display_weekend_highlight(){
    gantt.templates.scale_cell_class = function(date){
        if(date.getDay()==0||date.getDay()==6){
            return "campaignchain_gantt_weekend";
        }
    };
    gantt.templates.task_cell_class = function(item,date){
        if(date.getDay()==0||date.getDay()==6){
            return "campaignchain_gantt_weekend" ;
        }
    };
}
function hide_weekend_highlight(){
    gantt.templates.scale_cell_class = function(){
        return "";
    };
    gantt.templates.task_cell_class = function(){
        return "" ;
    };
}

// Make chart jump to today's date
function scrollToToday(){
    var scrollX = gantt.posFromDate(+campaignchainGetUserDateTime(moment()));
    gantt.scrollTo(scrollX-200, 0);
}

var gantt_filter = 0;
function filter_tasks(node){
    gantt_filter = node.value;
    gantt.refreshData();
}

function set_scale_units(mode){
    switch (mode){
        case "work_hours":
            gantt.config.subscales = [
                {unit:"hour", step:1, date:"%H:%i"}
            ];
            gantt.ignore_time = function(date){
                if(date.getDay() == 0 || date.getDay() == 6 || date.getHours() < 9 || date.getHours() > 18){
                    return true;
                }else{
                    return false;
                }
            };
            break;
        case "full_day":
            gantt.config.subscales = [
                {unit:"hour", step:1, date:"%H:%i"}
            ];
            gantt.ignore_time = null;
            break;
        case "work_week":
            gantt.ignore_time = function(date){
                if(date.getDay() == 0 || date.getDay() == 6){
                    return true;
                }else{
                    return false;
                }
            };

            break;
        default:
            gantt.ignore_time = null;
            break;
    }
    gantt.render();
}


function zoom_tasks(node, scale){
    switch(node){
        case "week":
            gantt.config.scale_unit = "day";
            gantt.config.date_scale = "%l, %F %d";

            gantt.config.scale_height = 60;
            gantt.config.min_column_width = 40;
            gantt.config.subscales = [
                {unit:"hour", step:1, date:"%H:%i"}
            ];
            set_scale_units(scale);
            display_weekend_highlight();
            break;
        case "trplweek":
            gantt.config.min_column_width = 70;
            gantt.config.scale_unit = "day";
            gantt.config.date_scale = "%D";
            gantt.config.subscales = [
                {unit:"day", step:1, date:"%d %M"}
            ];
            gantt.config.scale_height = 50;
            set_scale_units(scale);
            display_weekend_highlight();
            break;
        case "month":
            gantt.config.min_column_width = 70;
            gantt.config.scale_unit = "week";
            gantt.config.date_scale = "Week #%W";
            gantt.config.subscales = [
                {unit:"day", step:1, date:"%D"}
            ];
            gantt.config.scale_height = 60;
            set_scale_units(scale);
            display_weekend_highlight();
            break;
        case "year":
            gantt.config.min_column_width = 70;
            gantt.config.scale_unit = "month";
            gantt.config.date_scale = "%M";
            gantt.config.scale_height = 60;
            gantt.config.subscales = [
                {unit:"week", step:1, date:"#%W"}
            ];
            hide_weekend_highlight();
            break;
        case "fullyear":
            gantt.config.scale_unit = "year";
            gantt.config.date_scale = "%Y";
            gantt.config.min_column_width = 50;
            gantt.config.scale_height = 90;
            gantt.config.subscales = [
                {unit:"month", step:1, date:"%M" }
            ];
            hide_weekend_highlight();
    }
    gantt.render();
    scrollToToday();
}

gantt.config.details_on_create = true;

gantt.templates.task_class = function(start, end, obj){
    return obj.project ? "project" : "";
}

gantt.attachEvent("onTaskCreated", function(obj){
    obj.duration = 4;
    obj.progress = 0.25;
})
display_weekend_highlight();
zoom_tasks('fullyear', 'fullyear');