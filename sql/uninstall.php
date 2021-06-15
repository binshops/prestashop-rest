<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 */

/**
 * In some cases you should not drop the tables.
 * Maybe the merchant will just try to reset the module
 * but does not want to loose all of the data associated to the module.
 */
$sql = array();

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
