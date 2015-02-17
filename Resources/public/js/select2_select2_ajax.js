function campaignchainDependentSelect2(parent, child, route){
    $parentSelect = $("#form_" + parent);
    $childSelect = $("#form_" + child);
    $childLabel = $('label[for="form_' + child + '"]');

    $childSelect.hide();
    $childLabel.hide();

    var $previousSelect = $currentSelect = $parentSelect.attr('value');

    $parentSelect.select2("val", "");

    $parentSelect.change(function(){
        if ($(this).val() != '') {
            var $route = Routing.generate(route, { id: $parentSelect.val() });
            $.getJSON( $route )
                .done(function( json ) {
                    function format(item) { return item.display_name; }

                    $childSelect.select2({
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
            $childSelect.show();
            $childLabel.show();

            if($previousSelect != $parentSelect.val()){
                $childSelect.val('');
            }

            $previousSelect = $parentSelect.val();
        }
    });

    $parentSelect.on("select2-opening", function() {
        $childSelect.select2('destroy');
        $childSelect.hide();
        $childLabel.hide();
    });
}