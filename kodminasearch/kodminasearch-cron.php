<?php
require_once dirname(__FILE__) . '../../../config/config.inc.php';
require_once dirname(__FILE__) . '../../../init.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

//8f65b00552

// /* Check to security tocken */
if (substr(Tools::encrypt('kodminasearch/cron'), 0, 10) != Tools::getValue('token') || !Module::isInstalled('kodminasearch')) {
    die('Bad token');
}

$module = Module::getInstanceByName('kodminasearch');
$module->index();

die('OK');
