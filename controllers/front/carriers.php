<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestCarriersModuleFrontController extends AbstractRESTController
{

    protected function processGetRequest()
    {
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
        $carriers = $session->getDeliveryOptions();

        foreach ($carriers as &$carrier) {
            unset($carrier['product_list']);
            unset($carrier['package_list']);
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $carriers
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


