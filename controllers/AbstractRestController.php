<?php

abstract class AbstractRestController extends ModuleFrontController
{
    public function init()
    {
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