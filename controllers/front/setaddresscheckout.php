<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 * @license https://www.binshops.com
 */

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestSetaddresscheckoutModuleFrontController extends AbstractAuthRESTController
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
        if (Tools::getValue('id_address')) {
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

            $session->setIdAddressDelivery(Tools::getValue('id_address'));
            $session->setIdAddressInvoice(Tools::getValue('id_address'));
        } else {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 301,
                'psdata' => "id_address-required"
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => "id address has been successfully set"
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
