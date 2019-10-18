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

// if (!Tools::isSubmit('machineId')) {
//     return die();
// }

$id_cart = (int) Context::getContext()->cart->id;
$id_address_delivery = (int) Context::getContext()->cart->id_address_delivery;

if (!$id_cart) {
    return die();
}

$logged = Context::getContext()->customer->isLogged();

$machineId = (int) Tools::getValue('machineId');

if (!$machineId) {
    // Context::getContext()->cart->id_address_delivery = 0;
    // Context::getContext()->cart->id_address_invoice = 0;
    Context::getContext()->cart->update();
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->clearAddress();
    return die(Tools::jsonEncode(array(
        'success' => true,
        'HOOK_CARRIER_FORM' => Hook::exec('displayCarrierForm'),
    )));
}

$sql = new DbQuery();
$sql->select('*')->from('lp_express_terminal')->where('machineid = ' . $machineId);
$terminal = Db::getInstance()->getRow($sql);

if ($logged) {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->id_country = Country::getByIso('LT');
    $customerData->city = $terminal['city'];
    $customerData->address1 = $terminal['address'];
    $customerData->postcode = $terminal['zip'];
    $customerData->edited = false;
    $customerData->validate();
} else {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->id_country = Country::getByIso('LT');
    $customerData->city = $terminal['city'];
    $customerData->address1 = $terminal['address'];
    $customerData->postcode = $terminal['zip'];
    $customerData->edited = false;
    $customerData->validate();
}

$carrier = new Carrier((int) Context::getContext()->cart->id_carrier);

die(Tools::jsonEncode(array(
    'success' => true,
    'carrier' => $carrier,
    'HOOK_CARRIER_FORM' => Hook::exec('displayCarrierForm'),
)));
