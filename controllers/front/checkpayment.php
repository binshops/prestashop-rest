<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

class BinshopsrestCheckpaymentModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');

            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => 'payment processing failed'
            ]));
            die;
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'ps_checkpayment') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 302,
                'message' => 'This payment method is not available'
            ]));
            die;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => 'payment processing failed'
            ]));
            die;
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $mailVars = [
            '{check_name}' => Configuration::get('CHEQUE_NAME'),
            '{check_address}' => Configuration::get('CHEQUE_ADDRESS'),
            '{check_address_html}' => str_replace("\n", '<br />', Configuration::get('CHEQUE_ADDRESS')), ];

        $ps_checkpayment = Module::getInstanceByName('ps_checkpayment');

        if (Validate::isLoadedObject($this->context->cart) && $this->context->cart->OrderExists() == false){
            $ps_checkpayment->validateOrder(
                (int) $cart->id,
                (int) Configuration::get('PS_OS_CHEQUE'),
                $total,
                $ps_checkpayment->displayName,
                null,
                $mailVars,
                (int) $currency->id,
                false,
                $customer->secure_key
            );
        }else{
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 302,
                'message' => $this->trans('Cart cannot be loaded or an order has already been placed using this cart', [], 'Admin.Payment.Notification')
            ]));
            die;
        }

        $order_presenter = new OrderPresenter();

        $order = new Order(Order::getIdByCartId((int) ($cart->id)));
        $presentedOrder = $order_presenter->present($order);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'message' => 'successful payment',
            'psdata' => [
                'order' => $presentedOrder
            ]
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
