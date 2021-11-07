<?php

abstract class AbstractCartRESTController extends CartControllerCore {
    public function init()
    {
        header('Content-Type: ' . "application/json");
        parent::init();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PATCH':
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;
            default:
                // throw some error or whatever
        }
    }

    abstract protected function processGetRequest();

    abstract protected function processPostRequest();

    abstract protected function processPutRequest();

    abstract protected function processDeleteRequest();

    protected function checkCartProductsMinimalQuantities()
    {
        $productList = $this->context->cart->getProducts();

        foreach ($productList as $product) {
            if ($product['minimal_quantity'] > $product['cart_quantity']) {
                // display minimal quantity warning error message
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    [
                        '%product%' => $product['name'],
                        '%quantity%' => $product['minimal_quantity'],
                    ],
                    'Shop.Notifications.Error'
                );
            }
        }
    }
}
