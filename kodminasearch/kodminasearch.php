<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class KodminaSearch extends Module
{
    public function __construct()
    {
        $this->name = 'kodminasearch';
        $this->tab = 'front_office_features';
        $this->version = '0.9.0';
        $this->author = 'Kodmina. Edvinas Pranka';
        $this->need_instance = 0;
        $this->ps_version_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Kodmina Search');
        $this->description = $this->l('Improve default Prestashop search');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        try {
            $this->exactValues = array_map(function ($exact) {
                return $exact['word'];
            }, Db::getInstance()->executeS('SELECT word FROM ps_kodminasearch_exact'));
        } catch (Exception $e) {

        }
    }

    public function hookActionProductSave($params)
    {
        $product = $params['product'];
        $this->indexProduct($product->id);
    }

    public function hookActionProductDelete($params)
    {
        try {
            $id_product = $params['id_product'];
            $this->removeProductFromIndex($id_product);
        } catch (Exception $ex) {

        }
    }

    public static function sanitize($string, $id_lang, $indexation = false, $iso_code = false)
    {
        $string = trim($string);
        if (empty($string)) {
            return '';
        }

        $string = Tools::strtolower(strip_tags($string));
        $string = html_entity_decode($string, ENT_NOQUOTES, 'utf-8');

        // $string = preg_replace('/(['.PREG_CLASS_NUMBERS.']+)['.PREG_CLASS_PUNCTUATION.']+(?=['.PREG_CLASS_NUMBERS.'])/u', '\1', $string);
        // $string = preg_replace('/['.PREG_CLASS_SEARCH_EXCLUDE.']+/u', ' ', $string);

        if ($indexation) {
            $string = preg_replace('/[._-]+/', ' ', $string);
        } else {
            // $words = explode(' ', $string);
            // $processed_words = array();
            // // // search for aliases for each word of the query
            // // foreach ($words as $word) {
            // //     $alias = new Alias(null, $word);
            // //     if (Validate::isLoadedObject($alias)) {
            // //         $processed_words[] = $alias->search;
            // //     } else {
            // //         $processed_words[] = $word;
            // //     }
            // // }
            // $string = implode(' ', $processed_words);
            $string = preg_replace('/[._]+/', '', $string);
            $string = ltrim(preg_replace('/([^ ])-/', '$1 ', ' ' . $string));
            $string = preg_replace('/[._]+/', '', $string);
            $string = preg_replace('/[^\s]-+/', '', $string);
        }

        $blacklist = Tools::strtolower(Configuration::get('PS_SEARCH_BLACKLIST', $id_lang));
        if (!empty($blacklist)) {
            $string = preg_replace('/(?<=\s)(' . $blacklist . ')(?=\s)/Su', '', $string);
            $string = preg_replace('/^(' . $blacklist . ')(?=\s)/Su', '', $string);
            $string = preg_replace('/(?<=\s)(' . $blacklist . ')$/Su', '', $string);
            $string = preg_replace('/^(' . $blacklist . ')$/Su', '', $string);
        }

        $string = Tools::replaceAccentedChars(trim(preg_replace('/\s+/', ' ', $string)));

        return $string;
    }

    public function find($id_lang, $expr, $page_number = 1, $page_size = 1, $order_by = 'custom1',
        $order_way = 'desc', $ajax = false, $use_cookie = true, Context $context = null) {
        if (!$context) {
            $context = Context::getContext();
        }

        // TODO : smart page management
        if ($page_number < 1) {
            $page_number = 1;
        }
        if ($page_size < 1) {
            $page_size = 1;
        }

        // $original_order_by = $order_by;
        // if($order_by == 'position' || $order_by == 'custom1') {
        //     $order_by = 'quantity';
        // }

        if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way)) {
            return false;
        }

        $aliases = Db::getInstance()->executeS('SELECT * FROM ps_kodminasearch_alias');
        $aliasesBySoundex = array_reduce($aliases, function ($result, $alias) {
            $result[$alias['soundex_from']] = $alias;
            return $result;
        }, array());

        // sanitize search expression
        $original_query = $expr;
        $query = self::sanitize($expr, $id_lang, false, $context->language->iso_code);
        // get db instance
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        // split query to words
        $query = preg_replace('/\/+/', ' ', $query);

        $query_with_numbers = $query;
        $query = preg_replace('/\S*\d+\S*/', '', $query);

        $words = explode(' ', $query);
        // if no words, return empty results
        if (!count($words)) {
            return ($ajax ? array() : array('total' => 0, 'result' => array()));
        }

        /** TRUNCATED */

        // create soundex element array
        $soundex_array = array();
        foreach ($words as $word) {
            if (is_numeric($word)) {
                // if word is number, do not soundex, but return wrapped number in special format
                $soundex_array[] = "[n]$word\[\/n]";
            } else if (preg_match('/\\d/', $word) > 0) {
                $soundex_array[] = "[s]$word\[\/s]";
            } else if (in_array($word, $this->exactValues)) {
                $soundex_array[] = "[s]$word\[\/s]";
            } else if (strlen($word) > 3) {
                $soundex_array[] = $this->soundex($word);
            } else if (!empty($word)) {
                $soundex_array[] = "[s]$word\[\/s]";
            }
        }
        $soundex_array = array_filter($soundex_array, 'is_string');
        // create soundex search array, and create sql to search products by soundex
        // also create name search array and create sql to score results
        $soundex_search_array = array();
        foreach ($soundex_array as $soundex_item) {
            if (isset($aliasesBySoundex[$soundex_item])) {
                $alias = $aliasesBySoundex[$soundex_item];
                $soundex_alias = $alias['soundex_to'];
                $soundex_search_array[] = "(ksi.soundex LIKE '%$soundex_item%' OR ksi.soundex LIKE '%$soundex_alias%')";
            } else {
                $soundex_search_array[] = "ksi.soundex LIKE '%$soundex_item%'";
            }
        }
        $soundex_search_like = implode(' AND ', $soundex_search_array);
        $name_search_array = array();
        foreach ($words as $word) {
            if (isset($aliasesBySoundex[$this->soundex($word)])) {
                $alias = $aliasesBySoundex[$this->soundex($word)];
                $to_alias = $alias['to'];
                $name_search_array[] = "(ksi.name LIKE '%$word%' OR ksi.name LIKE '%$to_alias%')";
            }
            $name_search_array[] = "(ksi.name LIKE '%$word%')";
        }
        $name_search_score = '((' . implode(' + ', $name_search_array) . ') / LENGTH(ksi.name) ) as position';
        $soundexSql = "SELECT ksi.id_product, $name_search_score FROM ps_kodminasearch_index ksi WHERE $soundex_search_like";
        $sql_groups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sql_groups = 'AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1');
        }

        $active_products = $db->executeS('
            SELECT DISTINCT cp.`id_product`
            FROM `' . _DB_PREFIX_ . 'category_product` cp
            ' . (Group::isFeatureActive() ? 'INNER JOIN `' . _DB_PREFIX_ . 'category_group` cg ON cp.`id_category` = cg.`id_category`' : '') . '
            INNER JOIN `' . _DB_PREFIX_ . 'category` c ON cp.`id_category` = c.`id_category`
            INNER JOIN `' . _DB_PREFIX_ . 'product` p ON cp.`id_product` = p.`id_product`
            ' . Shop::addSqlAssociation('product', 'p', false) . '
            WHERE c.`active` = 1
            AND product_shop.`active` = 1
            AND product_shop.`visibility` IN ("both", "search")

            ' . $sql_groups, true, false);

        $eligible_products = array();
        foreach ($active_products as $row) {
            $eligible_products[] = $row['id_product'];
        }

        $eligible_products2 = array();
        $search_products = Db::getInstance()->executeS($soundexSql);
        foreach ($search_products as $row) {
            $eligible_products2[] = $row['id_product'];
        }

        $eligible_products3 = array();
        $references = array_map(function ($reference) {
            return "'" . trim(strtolower($reference)) . "'";
        }, explode(' ', $original_query));
        if (count($references)) {
            $reference_products = Db::getInstance()->executeS('SELECT id_product FROM ps_kodminasearch_reference WHERE reference IN (' . implode(', ', $references) . ')');
            foreach($reference_products as $row) {
                $eligible_products3[] = $row['id_product'];
            }
        }

        $eligible_products = array_unique(array_merge(array_intersect($eligible_products, array_unique($eligible_products2)), $eligible_products3));

        $product_pool = '';
        foreach ($eligible_products as $id_product) {
            $product_pool .= (int) $id_product . ',';
        }

        if (empty($product_pool)) {
            return ($ajax ? array() : array('total' => 0, 'result' => array()));
        }
        $product_pool = ((strpos($product_pool, ',') === false) ? (' = ' . (int) $product_pool . ' ') : (' IN (' . rtrim($product_pool, ',') . ') '));

        if (empty($product_pool)) {
            return ($ajax ? array() : array('total' => 0, 'result' => array()));
        }

        if ($ajax) {
            $sql = 'SELECT DISTINCT p.id_product, pl.name pname, cl.name cname,
						cl.link_rewrite crewrite, pl.link_rewrite prewrite,
                        ' . $name_search_score . '
                    FROM ' . _DB_PREFIX_ . 'product p
					INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                        p.`id_product` = pl.`id_product`
						AND pl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl') . '
                        )
                        ' . Shop::addSqlAssociation('product', 'p') . '
                        INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (
                            product_shop.`id_category_default` = cl.`id_category`
                            AND cl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('cl') . '
                            )
                        LEFT JOIN ' . _DB_PREFIX_ . 'kodminasearch_index ksi ON p.id_product = ksi.id_product
					WHERE p.`id_product` ' . $product_pool . '
					ORDER BY position DESC LIMIT 10';
            return $db->executeS($sql, true, false);
        }

        if (strpos($order_by, '.') > 0) {
            $order_by = explode('.', $order_by);
            $order_by = pSQL($order_by[0]) . '.`' . pSQL($order_by[1]) . '`';
        }
        $alias = '';
        if ($order_by == 'price') {
            $alias = 'product_shop.';
        } elseif (in_array($order_by, array('date_upd', 'date_add'))) {
            $alias = 'p.';
        }
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
				pl.`description_short`, pl.`available_now`, pl.`available_later`, pl.`link_rewrite`, pl.`name`,
			 image_shop.`id_image` id_image, il.`legend`, m.`name` manufacturer_name,
             IF(stock.quantity > 0, 1, 0) as has_quantity,
             ' . $name_search_score . ',
				DATEDIFF(
					p.`date_add`,
					DATE_SUB(
						"' . date('Y-m-d') . ' 00:00:00",
						INTERVAL ' . (Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
					)
				) > 0 new' . (Combination::isFeatureActive() ? ', product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.`id_product_attribute`,0) id_product_attribute' : '') . '
                ' 
                
                . /** TRUNCATED */ 
                
                '
				' . Product::sqlStock('p', 0) . '
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'kodminasearch_index` ksi ON p.id_product = ksi.id_product
                LEFT JOIN `'._DB_PREFIX_.'kodmina_providers` kp ON p.id_kodmina_provider = kp.id_provider
				WHERE p.`id_product` ' . $product_pool . '
                GROUP BY product_shop.id_product';
                if($order_by == 'position' || $order_by == 'custom1') {
                    $sql .= ' ORDER BY has_quantity DESC, kp.sort_index ASC, quantity DESC';
                }else{
                    $sql .= '' . ($order_by ? ' ORDER BY  ' . $alias . $order_by : '') . ($order_way ? ' ' . $order_way : '') . '
                    ' . ($order_by = ' quantity' ? ', position DESC' : '') . '';
                }
                
				$sql .= ' LIMIT ' . (int) (($page_number - 1) * $page_size) . ',' . (int) $page_size;
        $result = $db->executeS($sql, true, false);

        $sql = 'SELECT COUNT(*)
				FROM ' . _DB_PREFIX_ . 'product p
				' . Shop::addSqlAssociation('product', 'p') . '
				INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl') . '
				)
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE p.`id_product` ' . $product_pool;
        $total = $db->getValue($sql, false);

        if (!$result) {
            $result_properties = false;
        } else {
            $result_properties = Product::getProductsProperties((int) $id_lang, $result);
        }

        return array('total' => $total, 'result' => $result_properties);

        return $results;
    }

    public function soundex($expr)
    {
        return Db::getInstance()->getValue("SELECT SOUNDEX('$expr') as soundex");
    }

    public function index()
    {
        Db::getInstance()->execute('TRUNCATE `ps_kodminasearch_index`');
        Db::getInstance()->execute('TRUNCATE `ps_kodminasearch_reference`');
        $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $products = Db::getInstance()->executeS("SELECT
            p.id_product
            FROM
                ps_product_lang pl
                    INNER JOIN
                ps_product p ON p.id_product = pl.id_product
            WHERE pl.id_shop = $id_shop AND pl.id_lang = $id_lang");

        foreach ($products as $product) {
            $this->indexProduct($product['id_product']);
        }
    }

    public function removeProductFromIndex($id_product)
    {
        Db::getInstance()->execute("DELETE FROM ps_kodminasearch_index WHERE id_product = $id_product");
    }

    public function indexProduct($id_product)
    {
        /** TRUNCATED */

        $name = Tools::replaceAccentedChars(trim(preg_replace('/\s+/', ' ', $name)));
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/i', ' ', $name);
        $name = preg_replace('/[\/\-]+/', ' ', $name);
        $name = preg_replace('/[^ąčęėįšųūža-z0-9\/\-\s\+]+/i', '', $name);

        $name_with_numbers = $name;

        $words = preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $name, -1, PREG_SPLIT_NO_EMPTY);

        $words_with_numbers = explode(' ', $name_with_numbers);
        foreach ($words_with_numbers as $word_with_number) {
            if (in_array($word_with_number, $this->exactValues)) {
                $words[] = $word_with_number;
                $name_with_numbers = str_replace($word_with_number, '', $name_with_numbers);
            }
        }

        preg_match_all('!\d+!', $name_with_numbers, $numbers);
        foreach ($numbers[0] as $number) {
            $words[] = $number;
        }
        preg_match_all('/\S*\d+\S*/', $name_with_numbers, $words_with_numbers);
        foreach ($words_with_numbers[0] as $word_with_number) {
            $word_without_number = preg_replace('/\d+/', '', $word_with_number);
            $words[] = $word_without_number;
        }
        $words = array_unique($words);
        $soundex = array();
        foreach ($words as $word) {
            if (is_numeric($word)) {
                $soundex[] = "[n]$word\[\/n]";
            } else if (preg_match('/\\d/', $word) > 0) {
                $soundex[] = "[s]$word\[\/s]";
            } else if (in_array($word, $this->exactValues)) {
                $soundex[] = "[s]$word\[\/s]";
            } else if (strlen($word) > 3) {
                $soundex[] = $this->soundex($word);
            } else if (!empty($word)) {
                $soundex[] = "[s]$word\[\/s]";
            }
        }
        $soundex = array_unique($soundex);
        $soundex = strip_tags(implode(' ', $soundex));
        Db::getInstance()->execute("DELETE FROM ps_kodminasearch_index WHERE id_product = $id_product");
        Db::getInstance()->execute("INSERT INTO ps_kodminasearch_index VALUES (NULL, '$id_product', '$name', '$soundex')");
    }

    public function submitAlias()
    {
        $from = Tools::getValue('from');
        $to = Tools::getValue('to');
        $id_alias = Tools::getValue('alias');
        if (empty(trim($from))) {
            $this->context->controller->errors[] = Tools::displayError('"From" is required');
            return;
        }
        if (empty(trim($to))) {
            $this->context->controller->errors[] = Tools::displayError('"To" is required');
            return;
        }
        if ($id_alias) {
            $exists = Db::getInstance()->getValue("SELECT id_alias FROM ps_kodminasearch_alias WHERE `from` = '$from' AND `id_alias` <> $id_alias");
        } else {
            $exists = Db::getInstance()->getValue("SELECT id_alias FROM ps_kodminasearch_alias WHERE `from` = '$from'");
        }
        if ($exists) {
            $this->context->controller->errors[] = Tools::displayError('Alias is already exists');
            return;
        }
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));
        // $soundex_from = substr($this->soundex($from), 0, -1);
        // $soundex_to = substr($this->soundex($to), 0, -1);
        $soundex_from = $this->soundex($from);
        $soundex_to = $this->soundex($to);
        if ($id_alias) {
            $result = Db::getInstance()->execute("UPDATE ps_kodminasearch_alias SET `to` = '$to', `soundex_from` = '$soundex_from', `soundex_to` = '$soundex_to' WHERE id_alias = $id_alias");
        } else {
            $result = Db::getInstance()->execute("INSERT INTO ps_kodminasearch_alias VALUES (NULL, '$from', '$to', '$soundex_from', '$soundex_to')");
        }
        if (!$result) {
            $this->context->controller->errors[] = Tools::displayError('Error occured when saving alias');
            return;
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
        }
    }

    public function submitExact()
    {
        $word = Tools::getValue('word');
        $id_exact = Tools::getValue('exact');
        if (empty(trim($word))) {
            $this->context->controller->errors[] = Tools::displayError('"Phrase" is required');
            return;
        }
        if ($id_exact) {
            $exists = Db::getInstance()->getValue("SELECT id_exact FROM ps_kodminasearch_exact WHERE `word` = '$word' AND `id_exact` <> $id_exact");
        } else {
            $exists = Db::getInstance()->getValue("SELECT id_exact FROM ps_kodminasearch_exact WHERE `word` = '$word'");
        }
        if ($exists) {
            $this->context->controller->errors[] = Tools::displayError('Phrase is already exists');
            return;
        }
        $word = strtolower(trim($word));
        if ($id_exact) {
            $result = Db::getInstance()->execute("UPDATE ps_kodminasearch_exact SET `word` = '$word' WHERE id_exact = $id_exact");
        } else {
            $result = Db::getInstance()->execute("INSERT INTO ps_kodminasearch_exact VALUES (NULL, '$word')");
        }
        if (!$result) {
            $this->context->controller->errors[] = Tools::displayError('Error occured when saving exact phrase');
            return;
        } else {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
        }
    }

    public function deleteAlias()
    {
        $id_alias = Tools::getValue('delete_alias');
        if (!empty($id_alias)) {
            Db::getInstance()->execute("DELETE FROM ps_kodminasearch_alias WHERE id_alias = $id_alias");
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
    }

    public function deleteExact()
    {
        $id_exact = Tools::getValue('delete_exact');
        if (!empty($id_exact)) {
            Db::getInstance()->execute("DELETE FROM ps_kodminasearch_exact WHERE id_exact = $id_exact");
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitAlias')) {
            $this->submitAlias();
        } else if (Tools::isSubmit('cancelAlias')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
        } else if (Tools::isSubmit('delete_alias')) {
            $this->deleteAlias();
        } else if (Tools::isSubmit('submitExact')) {
            $this->submitExact();
        } else if (Tools::isSubmit('cancelExact')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
        } else if (Tools::isSubmit('delete_exact')) {
            $this->deleteExact();
        }
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
        $this->context->smarty->assign('url_index', $url_index);

        if (Tools::isSubmit('fullIndex')) {
            $this->index();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name);
        }

        $id_alias = Tools::getValue('alias');
        if (!empty($id_alias)) {
            $alias = Db::getInstance()->getRow('SELECT * FROM ps_kodminasearch_alias WHERE id_alias = ' . $id_alias);
            $this->context->smarty->assign('alias', $alias);
        }
        $id_exact = Tools::getValue('exact');
        if (!empty($id_exact)) {
            $exact = Db::getInstance()->getRow('SELECT * FROM ps_kodminasearch_exact WHERE id_exact = ' . $id_exact);
            $this->context->smarty->assign('exact', $exact);
        }

        $aliasList = $this->renderAliasList();
        $exactList = $this->renderExactList();

        $this->context->smarty->assign('ALIAS_LIST', $aliasList);
        $this->context->smarty->assign('EXACT_LIST', $exactList);

        return $this->display(__FILE__, '/views/templates/content.tpl');
    }

    public function renderAliasList()
    {
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
        $aliases = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kodminasearch_alias ORDER BY id_alias ASC');
        
        /** TRUNCATED */
        
        $helper->actions = ['editalias', 'deletealias'];
        $helper->shopLinkType = '';
        $helper->title = $this->l('Aliases');
        $helper->module = $this;
        $list = $helper->generateList($aliases, $this->fields_list);
        return $list;
    }

    public function renderExactList()
    {
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
        $phrases = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kodminasearch_exact ORDER BY id_exact ASC');
        $this->fields_list = array(
            'word' => array(
                'title' => $this->l('Phrase'),
                'type' => 'text',
                'remove_onclick' => true,
            ),
        );
        $helper = new HelperList();
        $helper->identifier = 'id_exact';
        $helper->show_toolbar = true;
        $helper->toolbar_btn = [
            'new' => [
                'href' => '/' . $url_index . '&exact',
                'desc' => $this->l('New phrase'),
            ],
        ];
        $helper->actions = ['editexact', 'deleteexact'];
        $helper->shopLinkType = '';
        $helper->title = $this->l('Exact phrases (require re-index)');
        $helper->module = $this;
        $list = $helper->generateList($phrases, $this->fields_list);
        return $list;
    }

    public function displayEditaliasLink($token, $id)
    {
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&alias=' . $id;
        $label = $this->l('Edit');
        return '<a href="/' . $url_index . '" class="edit btn btn-default">
                    <i class="icon-pencil"></i> ' . $label . '
                </a>';
    }

    public function displayDeletealiasLink($token, $id)
    {
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&delete_alias=' . $id;
        $label = $this->l('Delete');
        $confirm = $this->l('Are you sure to delete ?');
        return '<a href="/' . $url_index . '" onclick="if (confirm(\'' . $confirm . '\')){return true;}else{event.stopPropagation(); event.preventDefault();};" class="delete">
            <i class="icon-trash"></i> ' . $label . '
        </a>';
    }

    public function displayEditexactLink($token, $id)
    {
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&exact=' . $id;
        $label = $this->l('Edit');
        return '<a href="/' . $url_index . '" class="edit btn btn-default">
                    <i class="icon-pencil"></i> ' . $label . '
                </a>';
    }

    public function displayDeleteexactLink($token, $id)
    {
        $url_index = basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&delete_exact=' . $id;
        $label = $this->l('Delete');
        $confirm = $this->l('Are you sure to delete ?');
        return '<a href="/' . $url_index . '" onclick="if (confirm(\'' . $confirm . '\')){return true;}else{event.stopPropagation(); event.preventDefault();};" class="delete">
            <i class="icon-trash"></i> ' . $label . '
        </a>';
    }

    public function installSql()
    {
        $result = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `ps_kodminasearch_index` (
            `id_index` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `soundex` varchar(512) DEFAULT NULL,
            PRIMARY KEY (`id_index`),
            KEY `id_product` (`id_product`),
            KEY `name` (`name`),
            KEY `soundex` (`soundex`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `ps_kodminasearch_alias` (
            `id_alias` int(11) NOT NULL AUTO_INCREMENT,
            `from` varchar(128) NOT NULL,
            `to` varchar(128) NOT NULL,
            `soundex_from` varchar(128) NOT NULL,
            `soundex_to` varchar(128) NOT NULL,
            PRIMARY KEY (`id_alias`),
            UNIQUE KEY `UNIQUE` (`from`),
            KEY `soundex_from` (`soundex_from`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `ps_kodminasearch_exact` (
            `id_exact` int(11) NOT NULL AUTO_INCREMENT,
            `word` varchar(128) NOT NULL,
            PRIMARY KEY (`id_exact`),
            UNIQUE KEY `word` (`word`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `ps_kodminasearch_reference` (
            `id_reference` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `reference` varchar(255) NOT NULL,
            PRIMARY KEY (`id_reference`),
            UNIQUE KEY `id_product` (`id_product`),
            KEY `reference` (`reference`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        if (!$result) {
            return false;
        }

        return true;
    }

    public function dropSql()
    {
        $result = Db::getInstance()->execute('DROP TABLE IF EXISTS `ps_kodminasearch_index`');
        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute('DROP TABLE IF EXISTS `ps_kodminasearch_alias`');
        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute('DROP TABLE IF EXISTS `ps_kodminasearch_exact`');
        if (!$result) {
            return false;
        }

        $result = Db::getInstance()->execute('DROP TABLE IF EXISTS `ps_kodminasearch_reference`');
        if (!$result) {
            return false;
        }
        return true;
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->registerHook('actionProductSave') || !$this->registerHook('actionProductDelete')) {
            return false;
        }

        if (!$this->installSql()) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        if (!$this->dropSql()) {
            return false;
        }

        return true;
    }
}
