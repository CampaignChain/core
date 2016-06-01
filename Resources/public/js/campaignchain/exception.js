/*
 *
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

var CampaignChain = CampaignChain || {};

CampaignChain.Exception = (function () {

    var http = function (status) {
        switch (status) {
            /*
            In the case of unauthorized access, we ask the user to resume
            an idle session.
             */
            case 401:
                var modal = new CampaignChain.Modal();
                modal.init({
                    closeable: false,
                });
                modal.showContent('campaignchain_core_security_resume');
                
                break;
        }
    };

    /**
     * This is our public interface.
     */
    return {
        http: http
    };

});