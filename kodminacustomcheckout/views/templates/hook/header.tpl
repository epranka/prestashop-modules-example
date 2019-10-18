<script>
    var kodminaCustomCheckout_omniva_terminal_carrier_references = {Tools::jsonEncode($omniva_terminal_carrier_references)};
    var kodminaCustomCheckout_lp_express_terminal_carrier_references = {Tools::jsonEncode($lp_express_terminal_carrier_references)};

    function kodminaCustomCheckout_isOmniva(carrier_reference) {
        return !!kodminaCustomCheckout_omniva_terminal_carrier_references.find(cid => cid == carrier_reference);
    }

    function kodminaCustomCheckout_isLpExpress(carrier_reference) {
        return !!kodminaCustomCheckout_lp_express_terminal_carrier_references.find(cid => cid == carrier_reference);
    }

</script>