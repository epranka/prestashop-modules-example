<?php

class KodminaInvoiceValidation extends ObjectModel
{
    public $company_invoice;
    public $address1_invoice;
    public $city_invoice;
    public $postcode_invoice;
    public $dni_invoice;
    public $vat_number_invoice;

    public static $definition = array(
        'table' => 'kodmina_validation',
        'primary' => 'id_kodmina_validation',
        'fields' => array(
            'company_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'address1_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 255),
            'postcode_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'required' => true, 'size' => 255),
            'city_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 255),
            'dni_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'required' => true, 'size' => 255),
            'vat_number_invoice' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
        ),
    );
}
