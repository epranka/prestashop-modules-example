<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class KodminaValidation extends ObjectModel
{

    public $email;
    public $firstname;
    public $lastname;
    public $phone_mobile;
    public $address1;
    public $postcode;
    public $city;
    public $id_country;

    public static $definition = array(
        'table' => 'kodmina_validation',
        'primary' => 'id_kodmina_validation',
        'fields' => array(
            'email' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255),
            'firstname' => array('type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255),
            'lastname' => array('type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 255),
            'phone_mobile' => array('type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'required' => true, 'size' => 255),
            'address1' => array('type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 255),
            'postcode' => array('type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'required' => true, 'size' => 255),
            'city' => array('type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 255),
            'id_country' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'size' => 11),

            // 'need_invoice' => array('type' => self::TYPE_BOOL, 'required' => false),
            // 'company_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
            // 'address1_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => false, 'size' => 255),
            // 'postcode_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'required' => false, 'size' => 255),
            // 'city_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => false, 'size' => 255),
            // 'dni_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'required' => true, 'size' => 255),
            // 'vat_number_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
        ),
    );

    public function validateController($htmlentities = true)
    {
        $errors = parent::validateController($htmlentities);
        // if ($this->need_invoice) {
        //     if (empty($this->company_invoice)) {
        //         $errors['company_invoice'] = '<b>' . self::displayFieldName('company_invoice', get_class($this), $htmlentities) . '</b> ' . Tools::displayError('is required.');
        //     }
        //     if (empty($this->address1_invoice)) {
        //         $errors['address1_invoice'] = '<b>' . self::displayFieldName('address1_invoice', get_class($this), $htmlentities) . '</b> ' . Tools::displayError('is required.');
        //     }
        //     if (empty($this->postcode_invoice)) {
        //         $errors['postcode_invoice'] = '<b>' . self::displayFieldName('postcode_invoice', get_class($this), $htmlentities) . '</b> ' . Tools::displayError('is required.');
        //     }
        //     if (empty($this->city_invoice)) {
        //         $errors['city_invoice'] = '<b>' . self::displayFieldName('city_invoice', get_class($this), $htmlentities) . '</b> ' . Tools::displayError('is required.');
        //     }
        //     if (empty($this->dni_invoice)) {
        //         $errors['dni_invoice'] = '<b>' . self::displayFieldName('dni_invoice', get_class($this), $htmlentities) . '</b> ' . Tools::displayError('is required.');
        //     }
        // }
        return $errors;
    }

    public function validateFields($die = true, $error_return = false)
    {
        $is_valid = parent::validateFields($die, $error_return);
        if (!$is_valid) {
            return false;
        }

        // if ($this->need_invoice) {
        //     if (empty($this->company_invoice)) {
        //         return false;
        //     }
        //     if (empty($this->address1_invoice)) {
        //         return false;
        //     }
        //     if (empty($this->dni_invoice)) {
        //         return false;
        //     }
        //     if (empty($this->city_invoice)) {
        //         return false;
        //     }
        //     if (empty($this->postcode_invoice)) {
        //         return false;
        //     }
        // }
        return $is_valid;
    }
}
