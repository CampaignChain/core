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

CampaignChain.Modal = (function () {

    var _options = {
        target: '#campaignchain-default-modal',

        // override to define a spinner
        spinner_start: function () {},
        spinner_stop: function () {},
        closeable: true,
        close_event: function () {},
    };

    var _modal;

    /**
     * Send form via ajax
     * @param form
     * @param form_action
     * @private
     */
    function _processSubmit(form, form_action) {

        $(document).trigger('campaignchain:form:before_submit');
        _options.spinner_start();

        $.ajax({
                type: form.attr('method'),
                url: Routing.generate(form_action),
                data: form.serialize(),
                dataType: 'json'
            })
            .done(function (data) {
                $(document).trigger('campaignchain:form:submit:success', data);
            })
            .fail(function (data) {
                $(document).trigger('campaignchain:form:submit:fail', data);
            })
    }

    /**
     * load form and show modal
     * @param route route name to get the form
     * @param form_action route to submit the data to
     * @return jQuery modal object
     */
    var showForm = function (route, form_action) {

        // default action is the same route
        if (form_action === undefined) {
            form_action = route;
        }

        _options.spinner_start();

        // retrieve form
        $.get(
            Routing.generate(route))

            // and update modal if successful
            .done(function (data) {
                _modal.find('.modal-content').html(data);
                
                // bind event to submit button
                _modal.find('form').submit(function (e) {
                    _processSubmit($(this), form_action);
                    e.preventDefault();
                });

                _isCloseable();

                _modal.modal('show');
                
                _options.spinner_stop();
            })
    };

    /**
     * Loads the page content.
     * 
     * @param route
     */
    var showContent = function (route) {
        _options.spinner_start();

        // retrieve form
        $.get(
            Routing.generate(route))

        // and update modal if successful
            .done(function (data) {
                _modal.find('.modal-content').html(data);

                _isCloseable();

                // show modal
                _modal.modal('show');

                _options.spinner_stop();
            })
    };

    /**
     * Can the modal be closed?
     *
     * @private
     */
    var _isCloseable = function () {
        if(!_options.closeable){
            /*
             Don't allow the modal to be closed by clicking on the background
             or hitting ESC.
             */
            _modal.modal({
                backdrop: 'static',
                keyboard: false
            });
            // Hide the close button.
            _modal.find('.close').remove();
            // Call a custom function.
            _options.close_event();
        }
    };
    
    var init = function (options) {
        /**
         * user options overwrite default options
         * @param options
         */
        for (var prop in options) {
            if (options.hasOwnProperty(prop)) {
                _options[prop] = options[prop];
            }
        }

        _modal = $(_options.target);
    };

    /**
     * this is our public interface
     */
    return {
        init: init,
        showForm: showForm,
        showContent: showContent
    };

});