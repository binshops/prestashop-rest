<?php
require_once __DIR__ . '/../AbstractAuthRestController.php';

class BinshopsrestLogoutModuleFrontController extends AbstractAuthRestController
{
    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'code'=> 200,
            'success' => true,
            'psdata' => $this->context->customer
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


