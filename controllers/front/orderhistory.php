<?php

/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter;

class BinshopsrestOrderHistoryModuleFrontController extends AbstractAuthRESTController
{
    protected function processGetRequest()
    {
        //proccess single order
        if (Tools::getIsset('id_order')) {
            $id_order = Tools::getValue('id_order');
            if (Tools::isEmpty($id_order) or !Validate::isUnsignedId($id_order)) {

                $this->ajaxRender(json_encode([
                    'success' => false,
                    'code' => 404,
                    'message' => $this->trans('order not found', [], 'Modules.Binshopsrest.Order')
                ]));
                die;
            }

            //there is a duplication of code but a prevention of new object creation too
            $order = new Order($id_order, $this->context->language->id);
            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id){
                $order_to_display = (new OrderPresenter())->present($order);

                if (Tools::isEmpty($id_order) or !Validate::isLoadedObject($order)) {

                    $this->ajaxRender(json_encode([
                        'success' => true,
                        'code' => 404,
                        'message' => $this->trans('order not found', [], 'Modules.Binshopsrest.Order')
                    ]));
                    die;
                } else {

                    $this->ajaxRender(json_encode([
                        'success' => true,
                        'code' => 200,
                        'psdata' => $order_to_display
                    ]));
                    die;
                }
            }else{
                $this->ajaxRender(json_encode([
                    'success' => false,
                    'code' => 404,
                    'message' => $this->trans('order not found', [], 'Modules.Binshopsrest.Order')
                ]));
                die;
            }
        }

        //process all orders
        $customer_orders = Order::getCustomerOrders($this->context->customer->id);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $customer_orders
        ]));
        die;
    }
}
