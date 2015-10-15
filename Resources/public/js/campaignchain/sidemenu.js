/*
 This file is part of the CampaignChain package.

 (c) CampaignChain Inc. <info@campaignchain.com>

 For the full copyright and license information, please view the LICENSE
 file that was distributed with this source code.
 */

$(document).ready(function () {
    var $body = $(document.body);
    // Do something after the next transition is finished. If the browser
    // doesn't support CSS transitions, the given function is executed
    // immediately via `setTimeout()` with `0`
    var transitionFinished;
    if(typeof window.TransitionEvent === "undefined") {
        // No CSS transition support, execute immediately
        transitionFinished = function(el, fn) {
            window.setTimeout(fn, 0);
        }
    } else {
        // CSS transition support available, add an once event handler
        transitionFinished = function(el, fn) {
            $(el).one("transitionend", fn);
        }
    }

    $('[data-toggle="sidemenu"]').on("click", function(e) {
        // Enable transitions and hide scroll bar
        $body.addClass('sidemenu-transition');

        // Defer triggering of transition to give the browser a chance to
        // apply CSS changes due to our adding `sidemenu-transition`
        setTimeout(function() {
            $body.addClass('sidemenu-show');
        }, 0);

        e.preventDefault();
    });

    $('#sidemenu-drape').on("click", function(e) {
        transitionFinished("#sidemenu", function(e) {
            $body.removeClass("sidemenu-transition");
        });

        $body.removeClass("sidemenu-show");
    });

    $('#sidemenu-btn-close').on("click", function(e) {
        transitionFinished("#sidemenu", function(e) {
            $body.removeClass("sidemenu-transition");
        });
        $body.removeClass("sidemenu-show");
    });
});