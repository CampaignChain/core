/*
 This file is part of the CampaignChain package.

 (c) CampaignChain, Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

$(document).ready(function() {
    // change message border
    $.blockUI.defaults.css = {
        padding:        20,
        width:          '30%',
        top:            '40%',
        left:           '35%',
        textAlign:      'center',
        color:          '#000',
        border:         '3px solid #aaa',
        backgroundColor:'#fff',
        cursor:         'wait',
    };
    $.blockUI.defaults.baseZ = 2000;
    $.blockUI.defaults.ignoreIfBlocked = true;
    $.blockUI.defaults.message = 'Please wait...';

    $('#blockui-wait-button').click(function() {
        $.blockUI({ message: $('#blockui-wait-message') });
    });
});