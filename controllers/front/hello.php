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
require_once dirname(__FILE__) . '/../../classes/APIRoutes.php';

class BinshopsrestHelloModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $endpoints = array();
        $routes = APIRoutes::getRoutes();
        foreach ($routes as $route){
            if ($route['rule'] !== 'rest' && $route['rule'] !== 'rest/'){
                $endpoints[] = $route['rule'];
            }
        }

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => 'PrestaShop REST API',
            'psdata' => [
                'postman_link' => 'https://documenter.getpostman.com/view/1491681/TzkyP1UC',
                'endpoints' => $endpoints
            ]
        ]));
        die;
    }
}
