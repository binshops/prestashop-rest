<?php
/**
 * BINSHOPS | Best In Shops
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'binshopsrest_reset_pass_tokens` (
    `id_pass_tokens` int(11) NOT NULL AUTO_INCREMENT,
    `reset_password_token` varchar(255) NOT NULL,
    `reset_password_validity` varchar(255) NOT NULL,
    `id_customer` int(11) NOT NULL,
    `last_token_gen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (`id_pass_tokens`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
