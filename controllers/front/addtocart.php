<?php
require_once __DIR__ . '/../AbstractRESTController.php';

/**
 * This REST endpoint adds a product to cart
 */
class BinshopsrestAddtocartModuleFrontController extends AbstractRESTController
{
    private $id_product = null;
    private $product = null;
    private $qty = null;

    protected function processGetRequest()
    {
        if (!(int) Tools::getValue('product_id', 0)) {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'message' => 'product id not specified'
            ]));
            die;
        }
        $this->id_product = Tools::getValue('product_id', 0);
        $this->product = new Product(
            $this->id_product,
            true,
            $this->context->language->id,
            $this->context->shop->id,
            $this->context
        );
        $this->qty = Tools::getValue('quantity');

        if (!Validate::isLoadedObject($this->product)) {
            $this->ajaxRender(json_encode([
                'code' => 302,
                'message' => 'product not found'
            ]));
            die;
        }elseif ($this->qty == 0 || !$this->qty) {
            $this->ajaxRender(json_encode([
                'code' => 303,
                'message' => 'null quantity or zero'
            ]));
            die;
        }else{
            // Add cart if no cart found
            if (!$this->context->cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
            }

            $customization_id = (int) Tools::getValue('id_customization');
            // Check customizable fields
            if (!$this->product->hasAllRequiredCustomizableFields() && !$customization_id) {
                $this->ajaxRender(json_encode([
                    'code' => 304,
                    'message' => 'Please fill in all of the required fields, and then save your customizations'
                ]));
                die;
            }

            $id_product_attribute = (int) Tools::getValue('id_product_attribute', Tools::getValue('ipa'));

            $update_quantity = $this->context->cart->updateQty(
                $this->qty,
                $this->id_product,
                $id_product_attribute
            );
            if(!$update_quantity){
                $this->ajaxRender(json_encode([
                    'code' => 305,
                    'message' => 'could not add to cart - error'
                ]));
                die;
            }else{
                $this->ajaxRender(json_encode([
                    'code' => 200,
                    'message' => 'added successfully'
                ]));
                die;
            }
        }
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