<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 * @license https://www.binshops.com
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

/**
 * This REST endpoint adds a product to cart
 */
class BinshopsrestRemovefromcartModuleFrontController extends AbstractRESTController
{
    protected $id_product;
    protected $id_product_attribute;
    protected $id_address_delivery;
    protected $customization_id;
    protected $qty;

    public function init()
    {
        $this->id_product = (int)Tools::getValue('id_product', null);
        $this->id_product_attribute = (int)Tools::getValue('id_product_attribute', Tools::getValue('ipa'));
        $this->customization_id = (int)Tools::getValue('id_customization');
        $this->qty = abs(Tools::getValue('qty', 1));
        $this->id_address_delivery = (int)Tools::getValue('id_address_delivery');
        parent::init();
    }

    protected function processGetRequest()
    {
        $customization_product = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'customization`'
            . ' WHERE `id_cart` = ' . (int)$this->context->cart->id
            . ' AND `id_product` = ' . (int)$this->id_product
            . ' AND `id_customization` != ' . (int)$this->customization_id
        );

        if (count($customization_product)) {
            $product = new Product((int)$this->id_product);
            if ($this->id_product_attribute > 0) {
                $minimal_quantity = (int)Attribute::getAttributeMinimalQty($this->id_product_attribute);
            } else {
                $minimal_quantity = (int)$product->minimal_quantity;
            }

            $total_quantity = 0;
            foreach ($customization_product as $custom) {
                $total_quantity += $custom['quantity'];
            }

            if ($total_quantity < $minimal_quantity) {
                $this->ajaxRender(json_encode([
                    'code' => 301,
                    'success' => false,
                    'message' => 'You must add %quantity% minimum quantity'
                ]));
                die;
            }
        }

        $data = array(
            'id_cart' => (int)$this->context->cart->id,
            'id_product' => (int)$this->id_product,
            'id_product_attribute' => (int)$this->id_product_attribute,
            'customization_id' => (int)$this->customization_id,
            'id_address_delivery' => (int)$this->id_address_delivery,
        );

        Hook::exec('actionObjectProductInCartDeleteBefore', $data, null, true);

        if ($this->context->cart->deleteProduct(
            $this->id_product,
            $this->id_product_attribute,
            $this->customization_id,
            $this->id_address_delivery
        )) {
            Hook::exec('actionObjectProductInCartDeleteAfter', $data);

            if (!Cart::getNbProducts((int)$this->context->cart->id)) {
                $this->context->cart->setDeliveryOption(null);
                $this->context->cart->gift = 0;
                $this->context->cart->gift_message = '';
                $this->context->cart->update();
            }
        }

        CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => 'product successfully removed from cart'
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
