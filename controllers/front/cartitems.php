<?php
require_once __DIR__ . '/../AbstractRestController.php';

/**
 * This REST gets current user order list
*/

class BinshopsrestCartitemsModuleFrontController extends AbstractRestController
{

    protected function processGetRequest()
    {
        $messageCode = 200;
        $presented_cart = $this->context->cart->getProducts(true);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => $messageCode,
            'psdata' => $presented_cart
        ]));
        die;
    }

    protected function processPostRequest(){
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