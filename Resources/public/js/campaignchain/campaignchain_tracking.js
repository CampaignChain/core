/*
 Tracks calls to action.

 Usage:

 Include the tracking.js file by adding the below tracking code right before the
 closing body element (i.e. </body> element).

 Replace [CAMPAIGNCHAIN INSTALLATION] with the URL of the root of your CampaignChain
 installation, e.g. http://www.example.com/bundles/campaignchaincore/js/campaignchain/campaignchain_tracking.js.

 Next, replace [CAMPAIGNCHAIN CHANNEL TRACKING ID] with the ID generated by CampaignChain for your
 channel.

 <script type="text/javascript" src="[CAMPAIGNCHAIN INSTALLATION]/bundles/campaignchaincore/js/campaignchain/campaignchain_tracking.js"></script>
 <script type="text/javascript">
 var campaignchainChannel = '[CAMPAIGNCHAIN CHANNEL TRACKING ID]';
 </script>
 */

/*
 Includes JQuery if not yet available.
 */
if(typeof jQuery == 'undefined'){
    document.write('<script type="text/javascript" src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.1.min.js"></'+'script>');
}

/*
 Include JQuery Cookies library.
 */
document.write('<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></'+'script>');

/*
 *  Initiate the tracking functionality.
 */
jQuery(document).ready(function() {
    var campaignchain = new CampaignChain();

    // If Tracking ID is not in cookie, then this is a first-time visit and
    // we want to report that.
    if(campaignchain.newVisit() == true){
        /*
         We pass this page as the target. The tracking API will then
         detect that the source equals the target and will understand
         that the CTA is the actual source.
         */
        campaignchain.sendUrlReport(window.location.href);
    }

    // Disable clicks if dev-stay mode.
    if(campaignchain.mode == 'dev-stay'){
        jQuery('a').on('click', function(event) {
            event.preventDefault();
        });
    }

    // Has a link been clicked?
    jQuery('a').click(function(){
        campaignchain.sendUrlReport(jQuery(this).attr("href"));
        return false;
    })
    // TODO: Has a form been submitted?
});

/**
 *  Define the CampaignChain class.
 *
 * @constructor
 */
function CampaignChain(){
    /**
     * The name of the CTA Tracking ID, e.g. 'campaignchain-id'.
     *
     * @type {string}
     */
    this.idName = 'campaignchain-id';

    /**
     * Value of the Tracking ID.
     *
     * @type {string|bool}
     */
    this.idValue = null;

    /**
     * The Channel Tracking ID.
     *
     * @type {string}
     */
    this.channel = window.campaignchainChannel;

    /**
     * The URL of the source (i.e. the current) page.
     *
     * @type {string}
     */
    this.source = window.location.href;

    /**
     * Development or production mode.
     *
     * Possible values:
     * - 'prod': Production mode
     * - 'dev': Development mode
     * - 'dev-stay': Click will not be executed and you will stay on the Web page.
     *
     * @type {string} prod|dev|dev-stay
     */
    this.mode = 'prod';
}

/**
 * Sends a CTA report to CampaignChain.
 *
 * @param target
 */
CampaignChain.prototype.sendUrlReport = function(target)
{
    this.target = target;

    // Check if the CampaignChain Tracking ID exists.
    if(this.getTrackingId()){
        if(this.mode == 'dev' || this.mode == 'dev-stay'){
            console.log('Tracking ID does exist.');
        }

        // Use Symfony dev environment if dev mode for API URL.
        if(this.mode == 'dev' || this.mode == 'dev-stay'){
            var ajaxUrlMode = 'app_dev.php/';
        } else {
            var ajaxUrlMode = '';
        }
        // Compose the API URL.
        var ajaxUrl = jQuery('script[src$="campaignchain_tracking.js"]').
            attr('src').replace(
                'bundles/campaignchaincore/js/campaignchain/campaignchain_tracking.js',
                ajaxUrlMode + 'api/v1/report/cta/new/' + this.channel);

        if(this.mode == 'dev' || this.mode == 'dev-stay'){
            console.log('API URL: ' + ajaxUrl);
        }

        // Pass the tracking data to the CampaignChain API.
        jQuery.ajax({
            url: ajaxUrl,
            data: { id_name: this.idName, id_value: this.idValue, source: this.source, target: this.target },
            dataType: 'jsonp',
            cache: false,
            context: this,

            success: function(data, status) {
                console.log(data);
                /*
                 If an external target URL, then append the Tracking ID if it is not
                 already appended.
                 */
                this.continueTracking(data.target_affiliation);

                if(this.mode != 'dev-stay'){
                    window.location.href = this.target;
                } else {
                    console.log('AJAX success: ' + status);
                    console.log('Would redirect to: ' + this.target);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                if(this.mode == 'dev-stay'){
                    console.log(
                        'AJAX error: URL: ' + ajaxUrl + ', status: ' + xhr.status +
                            ', message: ' +thrownError
                    );
                }
            }
        });
    } else {
        if(this.mode == 'dev' || this.mode == 'dev-stay'){
            console.log('No Tracking ID exists.');
        }
        window.location.href = this.target;
    }
}

/**
 * Checks whether Tracking ID exists as parameter in URL of this page.
 *
 * @returns {boolean}
 */
CampaignChain.prototype.getTrackingId = function()
{
    var logMsg = 'CTA Tracking ID with name "' + this.idName + '"';

    // If not in URL, check if it's in a Cookie.
    if(this.source.toLowerCase().indexOf(this.idName) < 0){
        logMsg =  logMsg + 'is NOT in URL';

        this.idValue = this.getCookie();

        if(this.idValue){
            logMsg = logMsg + ', but is in Cookie.';
        } else {
            logMsg = logMsg + ' and is NOT in Cookie.';
        }
    } else {
        this.idValue = decodeURIComponent((new RegExp('[?|&]' + this.idName + '=' + '([^&;]+?)(&|#|;|$)').exec(this.source)||[,""])[1].replace(/\+/g, '%20'))||null;
        logMsg = logMsg + ' is in URL.';
    }

    if(this.mode == 'dev' || this.mode == 'dev-stay'){
        console.log(logMsg);
        console.log('Tracking ID value: ' + this.idValue);
    }

    return this.idValue;
}

/**
 * Appends the Tracking ID to the target URL.
 *
 * @returns {CampaignChain.target}
 */
CampaignChain.prototype.continueTracking = function(affiliation)
{
    // If Tracking ID is already in URL, then return as is.
    if(this.target.toLowerCase().indexOf(this.idName) >= 0){
        return true;
    }

    /*
     No Tracking ID yet, so proceed depending on the target's affiliation:

     1. 'current': If target is within the current channel, then store the Tracking ID
     in a cookie.

     2. 'connected': If target is outside the current channel, but within another
     Channel registered with CampaignChain, then append the Tracking ID to the URL.

     3. 'unknown': If target is outside the current channel and not within a Channel
     connected with CampaignChain, then keep the target URL as is.
     */
    switch (affiliation){
        case 'current':
            jQuery.cookie(this.idName, this.idValue);

            if(this.mode == 'dev' || this.mode == 'dev-stay'){
                console.log('Stored in cookie: Tracking ID with affiliation "' + affiliation + '", name "' + this.idName + '" and value "' + this.idValue + '".' );
            }

            break
        case 'connected':
            if (this.target.indexOf(this.idName + "=") >= 0)
            {
                var prefix = this.target.substring(0, this.target.indexOf(this.idName));
                var suffix = this.target.substring(this.target.indexOf(this.idName));
                suffix = suffix.substring(suffix.indexOf("=") + 1);
                suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
                this.target = prefix + this.idName + "=" + this.idValue + suffix;
            }
            else
            {
                if (this.target.indexOf("?") < 0)
                    this.target += "?" + this.idName + "=" + this.idValue;
                else
                    this.target += "&" + this.idName + "=" + this.idValue;
            }

            if(this.mode == 'dev' || this.mode == 'dev-stay'){
                console.log('Appended to URL: Tracking ID with affiliation "' + affiliation + '", name "' + this.idName + '" and value "' + this.idValue + '".' );
            }

            break;
        case 'unknown':
            if(this.mode == 'dev' || this.mode == 'dev-stay'){
                console.log('Untouched target URL "' + this.target + '" due to affiliation "' + affiliation + '".' );
            }

            break;
    }
}

/**
 * Returns the Tracking ID value from the Cookie.
 *
 * @returns {string}
 */
CampaignChain.prototype.getCookie = function() {
    return jQuery.cookie(this.idName);
}

/**
 * Is this a new visit to this page from the Activity with the CTA?
 *
 * @returns {boolean}
 */
CampaignChain.prototype.newVisit = function()
{
    // Is the Tracking ID in the URL?
    if(this.source.toLowerCase().indexOf(this.idName) >= 0){
        // Is the visitor from a page outside of this pages domain?
        if(document.referrer.indexOf(location.protocol + "//" + location.host) !== 0){
            // Delete existing cookie.
            jQuery.removeCookie(this.idName);
            if(this.mode == 'dev' || this.mode == 'dev-stay'){
                console.log('New visit.');
                console.log('Cookie deleted.');
            }
            return true;
        }
    }

    if(this.mode == 'dev' || this.mode == 'dev-stay'){
        console.log('Not a new visit.');
        console.log('Referrer: ' + document.referrer);
    }

    return false;
}