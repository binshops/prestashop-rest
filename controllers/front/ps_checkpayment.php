<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractPaymentRESTController.php';

class BinshopsrestPs_checkpaymentModuleFrontController extends AbstractPaymentRESTController
{
    protected function processRESTPayment()
    {
        $cart = $this->context->cart;

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
                'message' => $this->trans('This payment method is not available', [], 'Modules.Binshopsrest.Payment')
            ]));
            die;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => $this->trans('payment processing failed', [], 'Modules.Binshopsrest.Payment')
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
    }
}
