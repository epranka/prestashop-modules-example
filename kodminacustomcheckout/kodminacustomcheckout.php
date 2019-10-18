<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once 'classes/KodminaCustomCheckoutHelpers.php';
require_once 'classes/KodminaCustomerData.php';

class KodminaCustomCheckout extends Module
{
    public function __construct()
    {
        $this->name = 'kodminacustomcheckout';
        $this->tab = 'front_office_features';
        $this->version = '0.9.0';
        $this->author = 'Kodmina. Edvinas Pranka';
        $this->need_instance = 0;
        $this->ps_version_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Custom Checkout');
        $this->description = $this->l('Checkout depend on carrier');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        // $this->omniva_terminal_carrier_references = array(277);
        $this->omniva_terminal_carrier_references = array(282); // array(130);
        // $this->lp_express_terminal_carrier_references = array(279, 114, 62, 63);
        $this->lp_express_terminal_carrier_references = array(60, 61, 62, 63);
        $this->default_need_invoice_shops = array(3, 4);

        if (!$this->isRegisteredInHook('actionCustomerAccountAdd')) {
            $this->registerHook('actionCustomerAccountAdd');
        }
    }

    public function hookHeader($params)
    {
        if (in_array(Context::getContext()->controller->php_self, array('order-opc', 'order'))) {
            $this->context->controller->addJS(array($this->_path . 'views/js/customCheckout.js'));
            $this->context->controller->addCss(array($this->_path . 'views/css/form.css'));
        }

        $this->context->smarty->assign(array(
            'omniva_terminal_carrier_references' => $this->omniva_terminal_carrier_references,
            'lp_express_terminal_carrier_references' => $this->lp_express_terminal_carrier_references,
        ));

        return $this->display(__FILE__, 'header.tpl');
    }

    private function createCustomerData()
    {
        if (!$this->context->cart) {
            return;
        }

        $id_cart = (int) $this->context->cart->id;
        $id_customer = (int) $this->context->customer->id;
        $is_guest = (int) $this->context->customer->is_guest;

        if (!$id_cart) {
            return;
        }
        $customerData = KodminaCustomerData::getByCartId($id_cart);
        if (!$customerData && $id_customer && !$is_guest) {
            $customerData = KodminaCustomerData::createCustomer($id_cart, $id_customer);
            $customerData->need_invoice = $this->needInvoiceByDefault();
            $customerData->save();
            $customerData->validate();
        } else if (!$customerData) {
            $customerData = KodminaCustomerData::createGuest($id_cart);
            $customerData->save();
            $customerData->validate();
        }
        return $customerData;
    }

    private function needInvoiceByDefault()
    {
        return array_search($this->context->shop->id, $this->default_need_invoice_shops) !== false;
    }

    public function hookActionCreateCustomerData()
    {
        $this->createCustomerData();
    }

    public function hookActionCartUpdate()
    {
        if (!$this->context->cart) {
            return;
        }

        $this->createCustomerData();
    }

    public function hookActionCartSave($params)
    {
        if ($this->context->cart) {
            $this->createCustomerData();
        }
    }

    public function isLpExpress($id_reference)
    {
        return array_search($id_reference, $this->lp_express_terminal_carrier_references) !== false;
    }

    public function isOmniva($id_reference)
    {
        return array_search($id_reference, $this->omniva_terminal_carrier_references) !== false;
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $id_cart = (int) $this->context->cart->id;
        if (!$id_cart) {
            return;
        }
        $customerData = KodminaCustomerData::getByCartId($id_cart);
        if ($customerData) {
            $newCustomer = $params['newCustomer'];
            $customerData->email = $newCustomer->email;
            $customerData->firstname = $newCustomer->firstname;
            $customerData->lastname = $newCustomer->lastname;
            $customerAddress = KodminaCustomCheckoutHelpers::getCustomerAddress($newCustomer->id);
            if ($customerAddress) {
                $customerData->phone_mobile = $customerAddress['phone_mobile'];
            }
            $customerData->update();
            $customerData->validate();
        }
    }

    public function hookActionValidateOrder($params)
    {
        $cart = $params['cart'];
        $order = $params['order'];
        $customerData = KodminaCustomerData::getByCartId($cart->id);
        $order->id_address_delivery = $cart->id_address_delivery;
        $order->id_address_invoice = $cart->id_address_invoice;
        $order->update();
    }

    public function hookActionAuthentication($params)
    {
        $id_cart = (int) $this->context->cart->id;
        if (!$id_cart) {
            return;
        }
        if ($this->context->customer->isLogged()) {
            $customerData = KodminaCustomerData::getByCartId($id_cart);
            if ($customerData->isGuest()) {
                $customerData->convertToCustomer((int) $this->context->customer->id);
                $customerData->validate();
            }
        }
    }

    public function hookDisplayPaymentMethodsError($params)
    {
        $id_cart = (int) $this->context->cart->id;
        if (!$id_cart) {
            return;
        }

        $customerData = KodminaCustomerData::getByCartId($id_cart);
        if (!$customerData) {
            $customerData = $this->createCustomerData();
        }

        $customerData->validate();
        if ($customerData->isValid()) {
            // if entered info is correct
            if (!$this->context->customer->isLogged()) {
                // if user is not logged, try to register it as guest or update guest info
                KodminaCustomCheckoutHelpers::createGuestCustomer();
            }
            $cart = $this->context->cart;
            // create delivery address from customer data
            $address = $customerData->createAddress();

            if ($this->context->customer->is_guest) {
                // if user is guest, assign address to it
                $address->id_customer = $this->context->customer->id;
                $address->update();
            }

            if ($customerData->need_invoice) {
                // if invoice is needed, create it from customer data
                $invoiceAddress = $customerData->createInvoiceAddress();
                if ($this->context->customer->is_guest) {
                    // if user is guest, assign address to ti
                    $invoiceAddress->id_customer = $this->context->customer->id;
                    $invoiceAddress->update();
                }
                // assign invoice address to cart
                $cart->id_address_invoice = $invoiceAddress->id;
            } else {
                // if invoice is not needed, assign same address as delivery
                $cart->id_address_invoice = $address->id;
            }
            // assign customer id to cart
            $cart->id_customer = (int) $this->context->customer->id;
            // assign delivery address to cart
            $cart->id_address_delivery = $address->id;
            $cart->secure_key = $this->context->customer->secure_key;
            $cart->update();
        } else {
            return '<p class="warning">' . Tools::displayError('Error: This address or account info is invalid.') . '</p>';
        }
    }

    public function hookDisplayCheckoutLoadingOverlay($params)
    {
        return $this->display(__FILE__, 'loadingOverlay.tpl');
    }

    public function hookDisplayCarrierForm($params)
    {
        $id_carrier = (int) Context::getContext()->cart->id_carrier;
        $id_cart = (int) Context::getContext()->cart->id;
        $carrier = new Carrier($id_carrier);
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }

        $formType = 'full';
        if ($this->isLpExpress($carrier->id_reference)) {
            $formType = 'small';
        } else if ($this->isOmniva($carrier->id_reference)) {
            $formType = 'small';
        } else {
            $formType = 'full';
        }
        $customer = $this->context->customer;
        $customerData = KodminaCustomerData::getByCartId($id_cart);
        $this->context->smarty->assign(array(
            'formType' => $formType,
            'customer' => $customer,
            'customerData' => $customerData,
            'countries' => $countries,
            'default_country' => $this->context->country->id,
        ));
        return $this->display(__FILE__, 'form.tpl');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (
            !$this->registerHook('actionCustomerAccountAdd') ||
            !$this->registerHook('displayCarrierForm') ||
            !$this->registerHook('displayPaymentMethodsError') ||
            !$this->registerHook('displayCheckoutLoadingOverlay') ||
            !$this->registerHook('header') ||
            !$this->registerHook('actionCartSave') ||
            !$this->registerHook('actionCartUpdate') ||
            !$this->registerHook('actionCreateCustomerData') ||
            !$this->registerHook('actionValidateOrder') ||
            !$this->registerHook('actionAuthentication')
        ) {
            $this->uninstall();
            return false;
        }

        if (!KodminaCustomerData::createTable()) {
            $this->uninstall();
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        if (!KodminaCustomerData::dropTable()) {
            return false;
        }

        return true;
    }
}
