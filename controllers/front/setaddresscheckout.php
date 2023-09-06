<?php
/**
 * BINSHOPS | Best In Shops
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestSetaddresscheckoutModuleFrontController extends AbstractAuthRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
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

        if (Tools::getValue('id_address_delivery') && Tools::getValue('id_address_invoice')) {
            $session->setIdAddressDelivery(Tools::getValue('id_address_delivery'));
            $session->setIdAddressInvoice(Tools::getValue('id_address_invoice'));
        } elseif(Tools::getValue('id_address')) {
            $session->setIdAddressDelivery(Tools::getValue('id_address'));
            $session->setIdAddressInvoice(Tools::getValue('id_address'));
        }
        else {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 301,
                'psdata' => $this->trans("id_address-required", [], 'Modules.Binshopsrest.Checkout')
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $this->trans("id address has been successfully set", [], 'Modules.Binshopsrest.Checkout')
        ]));
        die;
    }
}
