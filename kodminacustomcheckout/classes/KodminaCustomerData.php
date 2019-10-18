<?php

require_once dirname(__FILE__) . '/KodminaValidation.php';
require_once dirname(__FILE__) . '/KodminaInvoiceValidation.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class KodminaCustomerData extends ObjectModel
{
    public $id_cart;
    public $id_address;
    public $id_address_invoice;
    public $id_customer;

    public $email;
    public $firstname;
    public $lastname;
    public $phone_mobile;
    public $address1;
    public $postcode;
    public $city;
    public $id_country;
    public $edited;
    public $is_valid;
    public $type;

    public $need_invoice;
    public $company_invoice;
    public $address1_invoice;
    public $city_invoice;
    public $postcode_invoice;
    public $dni_invoice;
    public $vat_number_invoice;

    public $newsletter;

    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'kodmina_customer_data',
        'primary' => 'id_customer_data',
        'fields' => array(
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'size' => 11),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false, 'size' => 11),
            'email' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
            'firstname' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
            'lastname' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
            
            /** TRUNCATED */

            'vat_number_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),

            'newsletter' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),

            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public function isValid()
    {
        return $this->is_valid;
    }

    public static function getByCartId($cart_id)
    {
        if (empty($cart_id)) {
            return false;
        }
        $sql = new DbQuery();
        $sql = $sql->select('id_customer_data')->from(self::getTableName(false))->where('id_cart = ' . $cart_id);
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new KodminaCustomerData($id);
        } else {
            return false;
        }
    }

    public function clearAddress()
    {
        $this->address1 = '';
        $this->postcode = '';
        $this->city = '';
        $this->id_country = 0;
        $this->is_valid = false;
        $this->update();
    }

    public function validate()
    {

        $kodminaValidation = new KodminaValidation();
        $kodminaValidation->email = $this->email;
        $kodminaValidation->firstname = $this->firstname;
        $kodminaValidation->lastname = $this->lastname;
        $kodminaValidation->phone_mobile = $this->phone_mobile;
        $kodminaValidation->address1 = $this->address1;
        $kodminaValidation->postcode = $this->postcode;
        $kodminaValidation->city = $this->city;
        $kodminaValidation->id_country = $this->id_country;

        $is_valid = $kodminaValidation->validateFields(false);

        if ($is_valid && $this->need_invoice) {
            $kodminaInvoiceValidation = new KodminaInvoiceValidation();
            $kodminaInvoiceValidation->company_invoice = $this->company_invoice;
            $kodminaInvoiceValidation->address1_invoice = $this->address1_invoice;
            $kodminaInvoiceValidation->city_invoice = $this->city_invoice;
            $kodminaInvoiceValidation->postcode_invoice = $this->postcode_invoice;
            $kodminaInvoiceValidation->dni_invoice = $this->dni_invoice;
            $kodminaInvoiceValidation->vat_number_invoice = $this->vat_number_invoice;
            $is_valid = $kodminaInvoiceValidation->validateFields(false);
        }

        if ($is_valid) {
            $this->is_valid = true;
        } else {
            $this->is_valid = false;
        }

        $this->update();

        return $is_valid;
    }

    public function isGuest()
    {
        return $this->type == 'guest';
    }

    public static function getLatestByCustomerId($id_customer)
    {
        if (empty($id_customer)) {
            return false;
        }
        $sql = new DbQuery();
        $sql = $sql->select('id_customer_data')->from(self::getTableName(false))->where('id_customer = ' . $id_customer)->orderBy('`date_upd` DESC');
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new KodminaCustomerData($id);
        } else {
            return false;
        }
    }

    public function createAddress()
    {
        if (!$this->is_valid) {
            return false;
        }
        if ($this->id_address) {
            $deliveryAddress = new Address($this->id_address);
            $deliveryAddress->alias = 'Customer Address';
        } else {
            $deliveryAddress = new Address();
            $deliveryAddress->alias = 'Customer Address';
        }
        $deliveryAddress->firstname = $this->firstname;
        $deliveryAddress->lastname = $this->lastname;
        $deliveryAddress->phone_mobile = $this->phone_mobile;
        $deliveryAddress->address1 = $this->address1;
        $deliveryAddress->postcode = $this->postcode;
        $deliveryAddress->city = $this->city;
        $deliveryAddress->id_country = $this->id_country;
        if ($this->id_address) {
            $deliveryAddress->update();
        } else {
            $deliveryAddress->save();
            $this->id_address = $deliveryAddress->id;
            $this->update();
        }
        return $deliveryAddress;
    }

    public function createInvoiceAddress()
    {
        if (!$this->is_valid) {
            return false;
        }
        if ($this->id_address_invoice) {
            $invoiceAddress = new Address($this->id_address_invoice);
            $invoiceAddress->alias = 'Customer Address Invoice';
        } else {
            $invoiceAddress = new Address();
            $invoiceAddress->alias = 'Customer Address Invoice';
        }
        $invoiceAddress->firstname = $this->firstname;
        $invoiceAddress->lastname = $this->lastname;
        $invoiceAddress->phone_mobile = $this->phone_mobile;
        $invoiceAddress->id_country = $this->id_country;
        $invoiceAddress->company = $this->company_invoice;
        $invoiceAddress->address1 = $this->address1_invoice;
        $invoiceAddress->city = $this->city_invoice;
        $invoiceAddress->postcode = $this->postcode_invoice;
        $invoiceAddress->dni = $this->dni_invoice;
        $invoiceAddress->vat_number = $this->vat_number_invoice;
        if ($this->id_address_invoice) {
            $invoiceAddress->update();
        } else {
            $invoiceAddress->save();
            $this->id_address_invoice = $invoiceAddress->id;
            $this->update();
        }
        return $invoiceAddress;
    }

    public function convertToCustomer($id_customer)
    {
        if (empty($id_customer)) {
            return;
        }
        $existantData = KodminaCustomerData::getLatestByCustomerId($id_customer);
        if ($existantData) {
            if (!$this->company_invoice) {
                $this->company_invoice = $existantData->company_invoice;
            }
            if (!$this->address1_invoice) {
                $this->address1_invoice = $existantData->address1_invoice;
            }
            if (!$this->city_invoice) {
                $this->city_invoice = $existantData->city_invoice;
            }
            if (!$this->postcode_invoice) {
                $this->postcode_invoice = $existantData->postcode_invoice;
            }
            if (!$this->dni_invoice) {
                $this->dni_invoice = $existantData->dni_invoice;
            }
            if (!$this->vat_number_invoice) {
                $this->vat_number_invoice = $existantData->vat_number_invoice;
            }
        }
        $customer = new Customer($id_customer);
        if (!$this->email) {
            $this->email = $customer->email;
        }
        if (!$this->firstname) {
            $this->firstname = $customer->firstname;
        }
        if (!$this->lastname) {
            $this->lastname = $customer->lastname;
        }
        $customerAddress = KodminaCustomCheckoutHelpers::getCustomerAddress();
        if ($customerAddress && !$this->phone_mobile) {
            $this->phone_mobile = $customerAddress['phone_mobile'];
        }
        $this->type = 'customer';
        $this->update();
    }

    public static function createCustomer($id_cart, $id_customer)
    {
       /** TRUNCATED */
    }

    public static function createGuest($id_cart)
    {
        /** TRUNCATED */
    }

    public function update($null_values = false)
    {
        if (Context::getContext()->customer->id) {
            $this->id_customer = (int) Context::getContext()->customer->id;
        }
        return parent::update($null_values);
    }

    public static function getTableName($prefix = true)
    {
        return ($prefix ? _DB_PREFIX_ : '') . self::$definition['table'];
    }

    public static function createTable()
    {
        $sql =
        'CREATE TABLE IF NOT EXISTS `' . self::getTableName() . '` (
			`id_customer_data` int(11)      NOT NULL AUTO_INCREMENT,
			`id_cart`                    int(11)      NOT NULL,
			`id_customer`                    int(11)      DEFAULT NULL,
			`email`                  varchar(255) DEFAULT NULL,
			`firstname`            varchar(255) DEFAULT NULL,
			`lastname`         varchar(255) DEFAULT NULL,
			`phone_mobile`         varchar(255) DEFAULT NULL,
            `address1`           varchar(255) DEFAULT NULL,
            /** TRUNCATED */
            `date_add`          datetime NOT NULL,
            `date_upd`          datetime NOT NULL,
			`type`         varchar(255) NOT NULL,
			PRIMARY KEY (`id_customer_data`, `id_cart`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function dropTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . self::getTableName() . '`;');
    }
}
