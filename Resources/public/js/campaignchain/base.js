/*
This file is part of the CampaignChain package.

(c) Sandro Groganz <sandro@campaignchain.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

function popupwindow(url, title, w, h) {
    var left = (screen.width/2)-(w/2);
    //var top = (screen.height/2)-(h/2);
    var top = 200;
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
}

// Round start date to 5 minute increments, because that's the minimum time interval of the scheduler.
function campaignchainRoundMinutes(date){
    m = (Math.round(moment(date).minutes()/5) * 5) % 60; // This did not do the same rounding as DHTMLXGantt does: (((moment(date).minutes() + 7.5)/15 | 0) * 15) % 60;
    h = ((((moment(date).minutes()/105) + .5) | 0) + moment(date).hours()) % 24;
    return moment(date).hours(h).minutes(m);
}

//function campaignchainUserDatetimeRefresh(){
//    var refresh=1000; // Refresh rate in milli seconds
//    mytime=setTimeout('campaignchainDisplayUserDatetime()',refresh)
//}
//
//function campaignchainDisplayUserDatetime() {
//    var strcount;
//    var x = moment().zone(window.campaignchainTimezoneOffset).toString();
//    document.getElementById('campaignchain_user_datetime').innerHTML = x;
//    tt=campaignchainUserDatetimeRefresh();
//}

$(document).ready(function() {
//    campaignchainDisplayUserDatetime();

    var summaries = $('.campaignchain-sticky-heading');
    summaries.each(function(i) {
        var summary = $(summaries[i]);
        var next = summaries[i + 1];

        summary.scrollToFixed({
            marginTop: $('.navbar-fixed-top').outerHeight(),
            limit: function() {
                var limit = 0;
                if (next) {
                    limit = $(next).offset().top  - $(this).outerHeight(true);
                }
                return limit;
            },
            zIndex: 999,
        });
    });

    // Wrap content of sticky heading into a div that allows us to stretch it across the whole screen width.
//    $('.scroll-to-fixed-fixed').wrapInner( "<div class='campaignchain-sticky-heading-inside'></div>" );

    $(".campaignchain-tooltip-top li").tooltip({
        placement : 'top'
    });
});