<?php

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestSetcarriercheckoutModuleFrontController extends AbstractAuthRESTController
{

    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'GET not supported on this path'
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        if (Tools::getValue('id_carrier') || Tools::getValue('id_address')) {
            $deliveryOptionsFinder = new DeliveryOptionsFinder(
                $this->context,
                $this->getTranslator(),
                $this->objectPresenter,
                new PriceFormatter()
            );
            $session = new CheckoutSession(
                $this->context,
                $deliveryOptionsFinder
            );

            $delivery_option = array();
            //[7] = [',7']
            $delivery_option[Tools::getValue('id_address')] = Tools::getValue('id_carrier');

            //set carrier option
            $session->setDeliveryOption($delivery_option);
            $session->getSelectedDeliveryOption();

        } else {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 301,
                'psdata' => "id_carrier-required"
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => "id carrier has been successfully set"
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


