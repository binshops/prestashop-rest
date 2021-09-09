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

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

/**
 * This REST endpoint gets featured products list
 *
 */
class BinshopsrestFeaturedproductsModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $featuredProducts = Module::getInstanceByName('ps_featuredproducts');
        $featuredProductsList = $featuredProducts->getWidgetVariables(null, []);

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => $featuredProductsList['products']
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'POST not supported on this path'
        ]));
        die;
    }

    protected function processPutRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'put not supported on this path'
        ]));
        die;
    }

    protected function processDeleteRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'delete not supported on this path'
        ]));
        die;
    }
}
