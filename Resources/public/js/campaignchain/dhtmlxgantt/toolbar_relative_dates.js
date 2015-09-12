/*
 This file is part of the CampaignChain package.

 (c) CampaignChain Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

var gantt_filter = 0;
function filter_tasks(node){
    gantt_filter = node.value;
    gantt.refreshData();
}

function zoom_tasks(node, scale_count){

    scale_count = typeof scale_count !== 'undefined' ? scale_count : 1;

    switch(node){
        case "hours":
            gantt.config.scale_unit = "day";
            gantt.config.date_scale = "";
            gantt.config.scale_height = 60;
            gantt.config.min_column_width = 40;

            // Main scale
            var scale_string = '';
            gantt.templates.date_scale = function(date) {
                if(+date < +moment(campaign_start_date).subtract(1, 'hours')){
                    scale_count = 1;
                    return '';
                }

                scale_string = "Day " + ('00' + scale_count).slice(-3);
                scale_count++;

                return scale_string;
            };

            gantt.config.subscales = [
                {unit:"hour", step:1, date:"%H:%i"}
            ];
            break;
        case "days":
            gantt.config.min_column_width = 70;
            gantt.config.scale_unit = "week";
            gantt.config.date_scale = "";
            gantt.config.scale_height = 50;

            // Main scale
            var scale_string = '';
            gantt.templates.date_scale = function(date) {
                if(+date < +moment(campaign_start_date).subtract(1, 'weeks')){
                    scale_count = 1;
                    return '';
                }

                scale_string = "Week " + ('00' + scale_count).slice(-3);
                scale_count++;

                return scale_string;
            };

            // Subscale
            var subscale_count = 1;
            var subscale_string = '';
            var dayScaleTemplate = function(date){
                if(+date < +moment(campaign_start_date).subtract(1, 'days')){
                    subscale_count = 1;
                    return '';
                }

                subscale_string = "Day " + ('00' + subscale_count).slice(-3);
                subscale_count++;

                return subscale_string;
            };
            gantt.config.subscales = [
                {unit:"day", step:1, template:dayScaleTemplate }
            ];

            break;
        case "weeks":
            gantt.config.min_column_width = 70;
            gantt.config.scale_unit = "month";
            gantt.config.date_scale = "";
            gantt.config.scale_height = 60;

            // Main scale
            var scale_string = '';
            gantt.templates.date_scale = function(date) {
                if(+date < +moment(campaign_start_date).subtract(1, 'days')){
                    scale_count = 1;
                    return '';
                }

                scale_string = "Month " + ('0' + scale_count).slice(-2);
                scale_count++;

                return scale_string;
            };

            // Subscale
            var subscale_count = 1;
            var subscale_string = '';
            var weekScaleTemplate = function(date){
                if(+date < +moment(campaign_start_date).subtract(1, 'weeks')){
                    subscale_count = 1;
                    return '';
                }

                subscale_string = "Week " + ('00' + subscale_count).slice(-3);
                subscale_count++;

                return subscale_string;
            };
            gantt.config.subscales = [
                {unit:"week", step:1, template:weekScaleTemplate }
            ];

            break;
        case "months":
            gantt.config.scale_unit = "year";
            gantt.config.date_scale = "";
            gantt.config.min_column_width = 50;
            gantt.config.scale_height = 90;

            // Main scale
            var scale_string = '';
            gantt.templates.date_scale = function(date) {
                if(+date < +moment(campaign_start_date).subtract(1, 'months')){
                    scale_count = 1;
                    return '';
                }

                scale_string = "Year " + ('0' + scale_count).slice(-2);
                scale_count++;

                return scale_string;
            };

            // Subscale
            var subscale_count = 1;
            var subscale_string = '';
            var monthScaleTemplate = function(date){
                if(+date < +moment(campaign_start_date).subtract(1, 'weeks')){
                    subscale_count = 1;
                    return '';
                }

                subscale_string = "Month " + ('0' + subscale_count).slice(-2);
                subscale_count++;

                return subscale_string;
            };
            gantt.config.subscales = [
                {unit:"month", step:1, template:monthScaleTemplate }
            ];
    }
    gantt.render();
}

gantt.config.details_on_create = true;

gantt.templates.task_class = function(start, end, obj){
    return obj.project ? "project" : "";
}

gantt.attachEvent("onTaskCreated", function(obj){
    obj.duration = 4;
    obj.progress = 0.25;
})

zoom_tasks('months', 0)