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

class BinshopsrestSetcarriercheckoutModuleFrontController extends AbstractAuthRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
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
                'psdata' => $this->trans("id_carrier-required", [], 'Modules.Binshopsrest.Checkout')
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $this->trans("id carrier has been successfully set", [], 'Modules.Binshopsrest.Checkout')
        ]));
        die;
    }
}
