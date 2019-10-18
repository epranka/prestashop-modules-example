<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class KodminaProductStats extends ObjectModel
{
    public $total_quantity;
    // Without taxes
    public $total_wholesale_price;

    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'kodmina_products_stats',
        'primary' => 'id_kodmina_stats',
        'fields' => array(
            /** TRUNCATED */

            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public static function getBetween($date_from = null, $date_to = null)
    {
        $sql = new DbQuery();
        $sql = $sql->select('*')->from(self::getTableName(false));
        if ($date_from) {
            $sql->where('date_add >= "' . date_format(date_create($date_from), 'Y-m-d H:i:s') . '"');
        }
        if ($date_to) {
            $sql->where('date_add < "' . date_format(date_create($date_to)->modify('+1 day'), 'Y-m-d H:i:s') . '"');
        }
        $sql->orderBy('date_add', 'desc');
        $results = Db::getInstance()->executeS($sql);
        if (is_array($results)) {
            return $results;
        }
        throw new Exception("Invalid sql");
    }

    public static function getLast()
    {
        $sql = new DbQuery();
        $sql = $sql->select('id_kodmina_stats')->from(self::getTableName(false))->orderBy('`date_add` DESC');
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new KodminaProductStats($id);
        } else {
            return false;
        }
    }

    public static function getCurrentStats()
    {
        $query = '
        SELECT
            SUM(totals.r_quantity) as total_quantity,
            SUM(totals.r_total_prime_price) as total_wholesale_price
        FROM
            (SELECT
                p.id_product AS r_id_product,
                    SUM(sa.quantity) AS r_quantity,
                    (SUM(sa.quantity) * ROUND((p.wholesale_price / 1.21), 2)) AS r_total_prime_price,
                    (SELECT
                            SUM(ps.active)
                        FROM
                            ps_product_shop ps
                        INNER JOIN ps_shop pss ON ps.id_shop = pss.id_shop
                        WHERE
                            ps.id_product = sa.id_product
                                AND pss.deleted = 0
                                AND pss.id_shop_group = 1
                                AND pss.active = 1) AS r_active
            FROM
                ps_stock_available sa
            INNER JOIN ps_product p ON sa.id_product = p.id_product
            ' . (Module::isInstalled('kodminaproviders') ? 'WHERE p.id_kodmina_provider = 0' : '') . '
            GROUP BY sa.id_product
            HAVING r_active > 0 AND r_quantity > 0) AS totals
    ';
        $result = Db::getInstance()->executeS($query);
        if (is_array($result) && count($result)) {
            return $result[0];
        }
        throw new Exception("Invalid sql");
    }

    public static function saveCurrentStats()
    {
        $currentStats = KodminaProductStats::getCurrentStats();
        $stats = new KodminaProductStats();
        $stats->total_quantity = $currentStats['total_quantity'];
        $stats->total_wholesale_price = $currentStats['total_wholesale_price'];
        $stats->save();
        return $stats;
    }

    public static function getTableName($prefix = true)
    {
        return ($prefix ? _DB_PREFIX_ : '') . self::$definition['table'];
    }

    public static function createTable()
    {
        $sql =
        'CREATE TABLE IF NOT EXISTS `' . self::getTableName() . '` (
            
            /** TRUNCATED */

			PRIMARY KEY (`id_kodmina_stats`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    public static function dropTable()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . self::getTableName() . '`;');
    }
}
