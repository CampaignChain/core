$(document).ready(function(){
    $("#form_activity").hide();
    $('label[for="form_activity"]').hide();

    $locationSelect = $("#form_location");

    var $previousSelect = $currentSelect = $locationSelect.attr('value');

    $locationSelect.select2("val", "");

    $locationSelect.change(function(){
        if ($(this).val() != '') {
            var $route = Routing.generate('campaignchain_core_location_list_activities_api', { id: $locationSelect.val() });
            $.getJSON( $route )
                .done(function( json ) {
                    function format(item) { return item.display_name; }

                    $("#form_activity").select2({
                        minimumResultsForSearch: 5,
                        dataType: 'json',
                        data: { results: json, text: 'display_name' },
                            formatSelection: format,
                            formatResult: format
                    });
                })
                .fail(function( jqxhr, textStatus, error ) {
                    var err = textStatus + ", " + error;
                    console.log( "Request Failed: " + err );
                });

            $(this).focus();
            $("#form_activity").show();
            $('label[for="form_activity"]').show();

            if($previousSelect != $locationSelect.val()){
                $("#form_activity").val('');
            }

            $previousSelect = $locationSelect.val();
        }
    });

    $locationSelect.on("select2-opening", function() {
        $("#form_activity").select2('destroy');
        $("#form_activity").hide();
        $('label[for="form_activity"]').hide();
    });
});