<?php

trait RESTTrait
{
    public function restRun(){
        header('Content-Type: ' . "application/json");
        if (Tools::getValue('iso_currency')){
            $_GET['id_currency'] = (string)Currency::getIdByIsoCode(Tools::getValue('currency'));
            $_GET['SubmitCurrency'] = "1";
        }

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

    protected function processGetRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('GET not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }

    protected function processPostRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('POST not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }

    protected function processPutRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('PUT not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }

    protected function processDeleteRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('DELETE not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }
}
