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

require_once dirname(__FILE__) . '/../AbstractCartRESTController.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;

/**
 * This REST endpoint adds a product to cart
 */
class BinshopsrestCartModuleFrontController extends AbstractCartRESTController
{
    protected function processGetRequest()
    {
        $this->updateCart();

        if (Configuration::isCatalogMode() && Tools::getValue('action') === 'show') {
            $this->ajaxRender(json_encode([
                'code' => 200,
                'success' => true,
                'message' => 'Just show - catalog mode is enabled and the action is show',
            ]));
            die;
        }

        if (!Tools::getValue('ajax')) {
            $this->checkCartProductsMinimalQuantities();
        }
        $presenter = new CartPresenter();
        $presented_cart = $presenter->present($this->context->cart, $shouldSeparateGifts = true);

        $products = $this->context->cart->getProducts(true);
        $link = Context::getContext()->link;

        foreach ($products as $key => $product) {
            $products[$key]['image_url'] = $link->getImageLink($product['link_rewrite'], $product['id_image'], Tools::getValue('image_size', ImageType::getFormattedName('small')));

            $products[$key]['attributes_array'] = $presented_cart['products'][$key]['attributes'];
        }

        $presented_cart['products'] = $products;

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => 'cart operation successfully done',
            'psdata' => $presented_cart
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

    protected function updateCart()
    {
        // Update the cart ONLY if $this->cookies are available, in order to avoid ghost carts created by bots
        if ($this->context->cookie->exists()
            && !$this->errors)
        {
            if (Tools::getIsset('add') || Tools::getIsset('update')) {
                $this->processChangeProductInCart();
            } elseif (Tools::getIsset('delete')) {
                $this->processDeleteProductInCart();
            } elseif (CartRule::isFeatureActive()) {
                if (Tools::getIsset('addDiscount')) {
                    if (!($code = trim(Tools::getValue('discount_name')))) {
                        $this->errors[] = $this->trans(
                            'You must enter a voucher code.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    } elseif (!Validate::isCleanHtml($code)) {
                        $this->errors[] = $this->trans(
                            'The voucher code is invalid.',
                            [],
                            'Shop.Notifications.Error'
                        );
                    } else {
                        if (($cartRule = new CartRule(CartRule::getIdByCode($code)))
                            && Validate::isLoadedObject($cartRule)
                        ) {
                            if ($error = $cartRule->checkValidity($this->context, false, true)) {
                                $this->errors[] = $error;
                            } else {
                                $this->context->cart->addCartRule($cartRule->id);
                            }
                        } else {
                            $this->errors[] = $this->trans(
                                'This voucher does not exist.',
                                [],
                                'Shop.Notifications.Error'
                            );
                        }
                    }
                } elseif (($id_cart_rule = (int) Tools::getValue('deleteDiscount'))
                    && Validate::isUnsignedId($id_cart_rule)
                ) {
                    $this->context->cart->removeCartRule($id_cart_rule);
                    CartRule::autoAddToCart($this->context);
                }
            }
        } elseif (!$this->isTokenValid() && Tools::getValue('action') !== 'show' && !Tools::getValue('ajax')) {
            $this->ajaxRender(json_encode([
                'code' => 301,
                'success' => false,
                'message' => 'cookie is not set',
            ]));
            die;
        }
    }
}
