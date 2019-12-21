<?php

/**
 * Any REST request which needs authentication must extend this class
*/

abstract class AbstractAuthRestController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        if (!$this->context->customer->isLogged() && $this->php_self != 'authentication' && $this->php_self != 'password'){
            $this->ajaxRender(json_encode([
                'code' => 410,
                'success' => false,
                'message' => 'User not authenticated'
            ]));
            die;
        }

        header('Content-Type: ' . "application/json");
        parent::init();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PATCH':
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;
            default:
                // throw some error or whatever
        }
    }

    abstract protected function processGetRequest();
    abstract protected function processPostRequest();
    abstract protected function processPutRequest();
    abstract protected function processDeleteRequest();
}