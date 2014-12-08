/*
 This file is part of the CampaignChain package.

 (c) Sandro Groganz <sandro@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

$(document).ready(function() {
    $('#blockui-wait-button').click(function() {
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

        $.blockUI({ message: $('#blockui-wait-message') });
    });
});