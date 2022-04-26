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

class BinshopsrestPaymentoptionsModuleFrontController extends AbstractAuthRESTController
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

        $paymentOptionsFinder = new PaymentOptionsFinder();
        $isFree = 0 == (float)$session->getCart()->getOrderTotal(true, Cart::BOTH);
        $paymentOptions = $paymentOptionsFinder->present($isFree);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $paymentOptions
        ]));
        die;
    }
}
