<?php
require_once dirname(__FILE__) . '../../../../config/config.inc.php';
require_once dirname(__FILE__) . '../../../../init.php';

require_once dirname(__FILE__) . '/../classes/KodminaCustomerData.php';
require_once dirname(__FILE__) . '/../classes/KodminaValidation.php';
require_once dirname(__FILE__) . '/../classes/KodminaInvoiceValidation.php';
require_once dirname(__FILE__) . '/../classes/KodminaCustomCheckoutHelpers.php';

useCustomErrorHandler();

if (!defined('_PS_VERSION_')) {
    exit;
}

$id_cart = (int) Context::getContext()->cart->id;
$id_address_delivery = (int) Context::getContext()->cart->id_address_delivery;

if (!$id_cart) {
    return die();
}

$logged = Context::getContext()->customer->isLogged();
$isGuest = Context::getContext()->customer->is_guest;
$id_customer = (int) Context::getContext()->customer->id;

if (isset($_POST['phone_mobile'])) {
    $_POST['phone_mobile'] = preg_replace('/\s+/', '', $_POST['phone_mobile']);
}
if (isset($_POST['postcode'])) {
    $_POST['postcode'] = preg_replace('/\s+/', '', $_POST['postcode']);
}
if (isset($_POST['postcode_invoice'])) {
    $_POST['postcode_invoice'] = preg_replace('/\s+/', '', $_POST['postcode_invoice']);
}
if (isset($_POST['dni_invoice'])) {
    $_POST['dni_invoice'] = preg_replace('/\s+/', '', $_POST['dni_invoice']);
}
if (isset($_POST['vat_number_invoice'])) {
    $_POST['vat_number_invoice'] = preg_replace('/\s+/', '', $_POST['vat_number_invoice']);
}

if ($logged) {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->firstname = Tools::getValue('firstname');
    $customerData->lastname = Tools::getValue('lastname');
    $customerData->phone_mobile = Tools::getValue('phone_mobile');
    $customerData->address1 = Tools::getValue('address1');
    $customerData->postcode = Tools::getValue('postcode');
    $customerData->city = Tools::getValue('city');
    $customerData->id_country = Tools::getValue('id_country');

    /** TRUNCATED */

    $hasErrors = count($errors) > 0;
    if (Tools::getValue('form_type') == 'small') {
        unset($errors['address1']);
        unset($errors['postcode']);
        unset($errors['city']);
        unset($errors['id_country']);
        $hasErrors = count($errors) > 0;
    }
    return die(Tools::jsonEncode(array(
        'errors' => $errors,
        'hasErrors' => $hasErrors,
    )));
} else {
    $customerData = KodminaCustomerData::getByCartId($id_cart);
    $customerData->email = Tools::getValue('email');
    $customerData->firstname = Tools::getValue('firstname');
    $customerData->lastname = Tools::getValue('lastname');
    $customerData->phone_mobile = Tools::getValue('phone_mobile');
    $customerData->address1 = Tools::getValue('address1');
    $customerData->postcode = Tools::getValue('postcode');
    $customerData->city = Tools::getValue('city');
    $customerData->id_country = Tools::getValue('id_country');

    $customerData->need_invoice = Tools::getValue('need_invoice') === 'on';
    $customerData->company_invoice = Tools::getValue('company_invoice');
    $customerData->address1_invoice = Tools::getValue('address1_invoice');
    $customerData->city_invoice = Tools::getValue('city_invoice');
    $customerData->postcode_invoice = Tools::getValue('postcode_invoice');
    $customerData->dni_invoice = Tools::getValue('dni_invoice');
    $customerData->vat_number_invoice = Tools::getValue('vat_number_invoice');

    $customerData->newsletter = Tools::getValue('newsletter') === 'on';

    if (Tools::getValue('form_type') === 'full') {
        $customerData->edited = true;
    }
    $customerData->validate();
    $validation = new KodminaValidation();
    $errors = $validation->validateController(false);
    if ($customerData->need_invoice) {
        $invoiceValidation = new KodminaInvoiceValidation();
        $invoiceErrors = $invoiceValidation->validateController();
        $errors = array_merge($errors, $invoiceErrors);
    }
    $hasErrors = count($errors) > 0;
    if (Tools::getValue('form_type') == 'small') {
        unset($errors['address1']);
        unset($errors['postcode']);
        unset($errors['city']);
        unset($errors['id_country']);
        $hasErrors = count($errors) > 0;
    }
    return die(Tools::jsonEncode(array(
        'errors' => $errors,
        'hasErrors' => $hasErrors,
    )));
}
