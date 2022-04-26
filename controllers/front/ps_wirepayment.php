<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractPaymentRESTController.php';

class BinshopsrestPs_wirepaymentModuleFrontController extends AbstractPaymentRESTController
{
    protected function processRESTPayment(){
        $cart = $this->context->cart;

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module)
            if ($module['name'] == 'ps_wirepayment')
            {
                $authorized = true;
                break;
            }
        if (!$authorized){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 303,
                'message' => $this->trans('This payment method is not available.', [], 'Modules.Binshopsrest.Payment')
            ]));
            die;
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => $this->trans('payment processing failed', [], 'Modules.Binshopsrest.Payment')
            ]));
            die;
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = array(
            '{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
            '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
            '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
        );

        $ps_wirepayment = Module::getInstanceByName('ps_wirepayment');

        $ps_wirepayment->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
    }
}
