<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestHelloModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'Hello Binshops API!'
        ]));
        die;
    }
}
