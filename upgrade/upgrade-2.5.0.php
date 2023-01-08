<?php
/**
 * BINSHOPS REST API
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_5_0($module)
{
    $module->registerHook('actionDispatcherBefore');
    return true;
}
