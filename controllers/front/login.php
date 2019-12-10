<?php
require_once __DIR__ . '/../AbstractRestController.php';

class BinshopsrestLoginModuleFrontController extends AbstractRestController
{
    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'GET not supported on this path'
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $psdata = ""; $messageCode = 0;
        $email = Tools::getValue('email', '');
        $password = Tools::getValue('password', '');
        $cart_id = Tools::getValue('session_data', '');

        if (!empty($cart_id)) {
            $this->context->cart->id_currency = $this->context->currency->id;
            $this->context->cart = new Cart($cart_id);
            $this->context->cookie->id_cart = (int) $this->context->cart->id;
            $this->context->cookie->write();
        }

        if (empty($email)) {
            $psdata = "An email address required";
            $messageCode = 301;
        } elseif (!Validate::isEmail($email)) {
            $psdata = "Invalid email address";
            $messageCode = 302;
        }elseif (empty($password)) {
            $psdata = 'Password is not provided';
            $messageCode = 303;
        } elseif (!Validate::isPasswd($password)) {
            $psdata = "Invalid Password";
            $messageCode = 304;
        }else{
            $customer = new Customer();
            Hook::exec('actionBeforeAuthentication');

            $authentication = $customer->getByEmail(trim($email), trim($password));
            if (isset($authentication->active) && !$authentication->active) {
                $psdata = 'Your account isn\'t available at this time.';
                $messageCode = 305;
            }elseif (!$authentication || !$customer->id) {
                $psdata = "Authentication failed";
                $messageCode = 306;
            }else {
                $this->context->cookie->id_customer = (int) ($customer->id);
                $this->context->cookie->customer_lastname = $customer->lastname;
                $this->context->cookie->customer_firstname = $customer->firstname;
                $this->context->cookie->logged = 1;
                $customer->logged = 1;
                $this->context->cookie->is_guest = $customer->isGuest();
                $this->context->cookie->passwd = $customer->passwd;
                $this->context->cookie->email = $customer->email;

                // Add customer to the context
                $this->context->customer = $customer;
                if (Configuration::get('PS_CART_FOLLOWING') &&
                    (empty($this->context->cookie->id_cart) ||
                        Cart::getNbProducts($this->context->cookie->id_cart) == 0) &&
                    $id_cart = (int) Cart::lastNoneOrderedCart($this->context->customer->id)) {
                    $this->context->cart = new Cart($id_cart);
                } else {
                    $id_carrier = (int) $this->context->cart->id_carrier;
                    if (!$this->context->cart->id_address_delivery) {
                        $this->context->cart->id_carrier = 0;
                        $this->context->cart->setDeliveryOption(null);
                        $d_id = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
                        $this->context->cart->id_address_delivery = $d_id;
                        $i_id = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
                        $this->context->cart->id_address_invoice = $i_id;
                    }
                }
                $this->context->cart->id_customer = (int) $customer->id;
                $this->context->cart->secure_key = $customer->secure_key;

                if (isset($id_carrier) && $id_carrier && Configuration::get('PS_ORDER_PROCESS_TYPE')) {
                    $delivery_option = array($this->context->cart->id_address_delivery => $id_carrier . ',');
                    $this->context->cart->setDeliveryOption($delivery_option);
                }

                $this->context->cart->id_currency = $this->context->currency->id;
                $this->context->cart->save();
                $this->context->cookie->id_cart = (int) $this->context->cart->id;
                $this->context->cookie->write();
                $this->context->cart->autosetProductAddress();

                Hook::exec('actionAuthentication', array('customer' => $this->context->customer));
                $messageCode = 200;
                $psdata = array(
                    'status' => 'success',
                    'message' => 'User login successfully',
                    'customer_id' => $customer->id,
                    'session_data' => (int)$this->context->cart->id,
                    'cart_count' => Cart::getNbProducts($this->context->cookie->id_cart)
                );

                // Login information have changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
            }
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => $messageCode,
            'psdata' => $psdata
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