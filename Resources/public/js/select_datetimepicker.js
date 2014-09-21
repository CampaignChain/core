$(document).ready(function(){
    var $dueElement = $("#campaignchain_core_milestone_campaignchain_hook_campaignchain_due_date");
//    $dueElement.parent().hide();
    $dueElement.prop('readonly', true);
    $dueElement.prop('disabled', true);
    $dueElement.prop('placeholder', 'Select a campaign first');
//    $('label[for="campaignchain_core_milestone_campaignchain_hook_campaignchain_due_date"]').hide();

    $campaignSelect = $("#campaignchain_core_milestone_campaign");

    var $previousSelect = $currentSelect = $campaignSelect.attr('value');

    $campaignSelect.select2("val", "");

    $campaignSelect.change(function(){
        if ($(this).val() != '') {
            // If the campaign's start date is after now, then use that as the start date.
            var $campaignStartDate = moment($campaigns_dates[$(this).val()]['startDate']);
            var $now = moment().zone(window.campaignchainTimezoneOffset);
            if($campaignStartDate < $now){
                $campaignStartDate = $now;
            }
            $dueElement.datetimepicker('setStartDate', $campaignStartDate.format(window.campaignchainDatetimeFormat));
            $dueElement.datetimepicker({'initialDate': $campaignStartDate.format(window.campaignchainDatetimeFormat)});
            $dueElement.datetimepicker('setEndDate', $campaigns_dates[$(this).val()]['endDate']);
            // Add help text with start/end date info of campaign.
            $dueElement.parent().parent().append('<span class="help-block campaignchain-appended">Campaign starts ' + $campaignStartDate.format(window.campaignchainDatetimeFormat) + ' and ends ' + $campaigns_dates[$(this).val()]['endDate'] + '.</span>');
            $(this).focus();
            $dueElement.prop('readonly', false);
            $dueElement.prop('disabled', false);
            $dueElement.prop('placeholder', '');
//            $dueElement.datetimepicker('show');
//            $dueElement.datetimepicker({autoclose: 'true'});
//            $dueElement.parent().show();
//            $('label[for="campaignchain_core_milestone_campaignchain_hook_campaignchain_due_date"]').show();

            if($previousSelect != $campaignSelect.val()){
                $dueElement.val('');
            }

            $previousSelect = $campaignSelect.val();
        }
    });

    $campaignSelect.on("select2-opening", function() {
        $dueElement.datetimepicker('hide');
        $('.campaignchain-appended').remove();
    });
});