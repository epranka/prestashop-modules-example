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

if (!Tools::isSubmit('terminalId')) {
    return die();
}

$id_cart = (int) Context::getContext()->cart->id;
$id_address_delivery = (int) Context::getContext()->cart->id_address_delivery;

if (!$id_cart) {
    trigger_error('No id_cart', E_USER_ERROR);
    return die();
}

$terminalId = Tools::getValue('terminalId');

$logged = Context::getContext()->customer->isLogged();

if (!$terminalId) {
    // Context::getContext()->cart->id_address_delivery = 0;
    // Context::getContext()->cart->id_address_invoice = 0;
    Context::getContext()->cart->update();
    Db::getInstance()->update('cart', array('omnivalt_terminal' => null), 'id_cart = ' . (int) $id_cart);
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->clearAddress();
    return die(Tools::jsonEncode(array(
        'success' => true,
        'HOOK_CARRIER_FORM' => Hook::exec('displayCarrierForm'),
    )));
}

$terminals_json_file_dir = dirname(__file__) . "/../../omnivaltshipping/locations.json";
$terminals_file = fopen($terminals_json_file_dir, "r");
$terminals = fread($terminals_file, filesize($terminals_json_file_dir) + 10);
fclose($terminals_file);
$terminals = json_decode($terminals, true);
$terminalIndex = array_search($terminalId, array_column($terminals, 'ZIP'));
$terminal = $terminals[$terminalIndex];

if ($logged) {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->id_country = Country::getByIso($terminal['A0_NAME']);
    $customerData->city = $terminal['A1_NAME'];
    $customerData->address1 = $terminal['A2_NAME'];
    $customerData->postcode = $terminal['ZIP'];
    $customerData->edited = false;
    $customerData->validate();
} else {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->id_country = Country::getByIso($terminal['A0_NAME']);
    $customerData->city = $terminal['A1_NAME'];
    $customerData->address1 = $terminal['A2_NAME'];
    $customerData->postcode = $terminal['ZIP'];
    $customerData->edited = false;
    $customerData->validate();
}

$carrier = new Carrier((int) Context::getContext()->cart->id_carrier);

die(Tools::jsonEncode(array(
    'success' => true,
    'carrier' => $carrier,
    'HOOK_CARRIER_FORM' => Hook::exec('displayCarrierForm'),
)));
