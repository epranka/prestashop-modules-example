<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once 'classes/KodminaProductStats.php';

class KodminaStats extends Module
{
    public function __construct()
    {
        $this->name = 'kodminastats';
        $this->tab = 'back_office_features';
        $this->version = '0.9.0';
        $this->author = 'Kodmina. Edvinas Pranka';
        $this->need_instance = 1;
        $this->ps_version_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Kodmina Stats');
        $this->description = $this->l('Addon for sbreports. Displays total products count and total whosale price');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function getContent()
    {

        $token = Tools::getAdminTokenLite('AdminModules');
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
        $this->context->smarty->assign('url_index', $url_index);

        if (Tools::isSubmit('ajax')) {
            return $this->handleAjaxCall();
        }

        $this->assignCurrentStats();
        $this->assignLastStats();

        return $this->display(__FILE__, '/views/templates/stats.tpl');
    }

    private function handleAjaxCall()
    {
        if (Tools::getValue('action') === 'updateStats') {
            KodminaProductStats::saveCurrentStats();
            $this->assignLastStats();
            return die(Tools::jsonEncode(
                array(
                    'success' => 1,
                    'result' => $this->display(__FILE__, '/views/templates/last_stats.tpl'),
                )
            ));

            /** TRUNCATED */

        } else if (Tools::getValue('action') === 'clearStats') {
            return die(Tools::jsonEncode(
                array(
                    'success' => 1,
                    'result' => $this->display(__FILE__, '/views/templates/filtered_stats.tpl'),
                )
            ));
        }
        http_response_code(404);
        return die();
    }

    public function date_add_callback($value)
    {
        return date_format(date_create($value), 'Y m d H:i');
    }

    public function total_quantity_callback($value)
    {
        return number_format($value, 0, ".", " ");
    }

    public function total_wholesale_price($value)
    {
        return number_format($value, 2, ".", " ");
    }

    protected function assignStatsFilter()
    {
        $filteredStats = KodminaProductStats::getBetween(Tools::getValue('date_from'), Tools::getValue('date_to'));
        $this->fields_list = array(
            'total_quantity' => array(
                'title' => $this->l('Products quantity'),
                'width' => 140,
                'type' => 'text',
                'callback_object' => $this,
                'callback' => 'total_quantity_callback',
                'remove_onclick' => true,
            ),
            /** TRUNCATED */
            'date_add' => array(
                'title' => $this->l('Date'),
                'callback_object' => $this,
                'callback' => 'date_add_callback',
                'remove_onclick' => true,
            ),
        );
        $helper = new HelperList();

        $helper->shopLinkType = '';

        $helper->simple_header = true;
        $helper->show_toolbar = true;

        $helper->actions = array();

        $helper->title = $this->l('Filtered Totals');

        $list = $helper->generateList($filteredStats, $this->fields_list);
        $this->context->smarty->assign('filteredStats', $list);
        if (Tools::isSubmit('date_from')) {
            $this->context->smarty->assign('date_from', Tools::getValue('date_from'));
        }
        if (Tools::isSubmit('date_to')) {
            $this->context->smarty->assign('date_to', Tools::getValue('date_to'));
        }
    }

    protected function assignCurrentStats()
    {
        $currentStats = KodminaProductStats::getCurrentStats();
        $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
        $cronJobLink = $shop->getBaseURL(true);
        $this->context->smarty->assign('currentStats', $currentStats);
        $this->context->smarty->assign('cronJobLink', $cronJobLink);
    }

    protected function assignLastStats()
    {
        $lastStats = KodminaProductStats::getLast();
        $this->context->smarty->assign('lastStats', $lastStats);
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        /** TRUNCATED */

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        /** TRUNCATED */

        return true;
    }
}
