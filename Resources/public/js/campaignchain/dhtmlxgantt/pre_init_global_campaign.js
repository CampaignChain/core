/*
 This file is part of the CampaignChain package.

 (c) CampaignChain, Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

// Show the channel icon in the left column
gantt.templates.grid_blank = function(item) {
    switch(item.type){
        case 'campaign':
            return item.tpl_teaser;
            break;
        case 'milestone':
            return "<img src='" + item.icon_path_16px + "' class='campaignchain_gantt_icon_column' />";
            break;
        case 'activity':
            return item.tpl_teaser;
            break;
    }

    return "<div class='gantt_tree_icon gantt_blank'><span class='fa fa-exclamation-triangle'></span></div>";
};