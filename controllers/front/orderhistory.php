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
                    'message' => 'order not found'
                ]));
                die;
            }

            //there is a duplication of code but a prevention of new object creation too

            $order = new Order($id_order, $this->context->language->id);

            if (Tools::isEmpty($id_order) or !Validate::isLoadedObject($order)) {

                $this->ajaxRender(json_encode([
                    'success' => true,
                    'code' => 404,
                    'message' => 'order not found'
                ]));
                die;
            } else {

                $this->ajaxRender(json_encode([
                    'success' => true,
                    'code' => 200,
                    'psdata' => $order
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
