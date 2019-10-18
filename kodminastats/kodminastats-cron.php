<?php
require_once dirname(__FILE__) . '../../../config/config.inc.php';
require_once dirname(__FILE__) . '../../../init.php';

require_once dirname(__FILE__) . '/classes/KodminaProductStats.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

// /* Check to security tocken */
if (substr(Tools::encrypt('kodminastats/cron'), 0, 10) != Tools::getValue('token') || !Module::isInstalled('kodminastats'))
{
    die('Bad token');
}
    
$stats = KodminaProductStats::saveCurrentStats();

die('OK');