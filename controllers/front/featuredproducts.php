<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 * @license https://www.binshops.com
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
