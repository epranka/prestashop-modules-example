<?php
require_once dirname(__FILE__) . '../../../../config/config.inc.php';
require_once dirname(__FILE__) . '../../../../init.php';

require_once dirname(__FILE__) . '/../classes/KodminaCustomerData.php';
require_once dirname(__FILE__) . '/../classes/KodminaCustomCheckoutHelpers.php';

useCustomErrorHandler();

if (!defined('_PS_VERSION_')) {
    exit;
}

$response = null;

$id_cart = (int) Context::getContext()->cart->id;
$id_address_delivery = (int) Context::getContext()->cart->id_address_delivery;

if (!$id_cart) {
    return die();
}

$logged = Context::getContext()->customer->isLogged();

if($logged) {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerAddress = KodminaCustomCheckoutHelpers::getCustomerAddress();
    if($customerAddress && !$customerData->edited) {
        $customerData->address1 = $customerAddress['address1'];
        $customerData->postcode = $customerAddress['postcode'];
        $customerData->city = $customerAddress['city'];
        $customerData->id_country = $customerAddress['id_country'];
        $customerData->validate();
    }else if(!$customerData->edited) {
        $customerData->clearAddress();
    }
}else{
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    if(!$customerData->edited) {
        $customerData->clearAddress();
    }
}

$carrier = new Carrier((int) Context::getContext()->cart->id_carrier);

die(Tools::jsonEncode(array(
    'success' => true,
    'carrier' => $carrier,
    'HOOK_CARRIER_FORM' => Hook::exec('displayCarrierForm'),
)));
