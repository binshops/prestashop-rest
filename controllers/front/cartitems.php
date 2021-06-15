<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

/**
 * This REST gets current user order list
 */
class BinshopsrestCartitemsModuleFrontController extends AbstractRESTController
{

    protected function processGetRequest()
    {
        $messageCode = 200;
        $presented_cart = $this->context->cart->getProducts(true);
        $link = Context::getContext()->link;

        foreach ($presented_cart as $key => $product) {
            $presented_cart[$key]['image_url'] = $link->getImageLink($product['link_rewrite'], $product['id_image'], Tools::getValue('image_size', "small_default"));
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => $messageCode,
            'psdata' => $presented_cart
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
