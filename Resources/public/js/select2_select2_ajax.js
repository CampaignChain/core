/**
 * Shows one select2 drop-down list in relation to another one and populates
 * the child drop-down with data from an AJAX call.
 *
 * @param parent The id of the parent select element.
 * @param child  The id of the child select element.
 * @param route  The Symfony route of the API returning a JSON object to populate
 *               the child select.
 */
function campaignchainDependentSelect2(parent, child, route){
    $parentSelect = $("select#" + parent);
    $childSelect = $("#" + child);
    $childLabel = $('label[for="' + child + '"]');

    $childSelect.hide();
    $childLabel.hide();

    var $previousSelect = $currentSelect = $parentSelect.attr('value');

    $parentSelect.select2("val", "");

    $parentSelect.change(function(){
        if ($(this).val() != '') {
            $childSelect.select2('destroy');
            $childSelect.hide();
            $childLabel.hide();

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
                .fail(function( jqXHR, textStatus, error ) {
                    var exception = new CampaignChain.Exception();
                    exception.http(jqXHR.status);
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
}