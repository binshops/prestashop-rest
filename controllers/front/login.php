<?php
require_once __DIR__ . '/../AbstractRestController.php';

class BinshopsrestLoginModuleFrontController extends AbstractRestController
{
    protected function processGetRequest()
    {
        $this->ajaxDie(json_encode([
            'success' => true,
            'operation' => 'get'
        ]));
    }

    protected function processPostRequest()
    {
        $this->ajaxDie(json_encode([
            'success' => true,
            'operation' => 'post'
        ]));
    }

    protected function processPutRequest()
    {
        $this->ajaxDie(json_encode([
            'success' => true,
            'operation' => 'put'
        ]));
    }

    protected function processDeleteRequest()
    {
        $this->ajaxDie(json_encode([
            'success' => true,
            'operation' => 'delete'
        ]));
    }
}