<?php
/**
 * BINSHOPS | Best In Shops
 *
 * @author BINSHOPS | Best In Shops
 * @copyright BINSHOPS | Best In Shops
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestLoginModuleFrontController extends AbstractRESTController
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
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
        $psdata = "";
        $messageCode = 0;
        $email = Tools::getValue('email', '');
        $password = Tools::getValue('password', '');
        $cart_id = Tools::getValue('session_data', '');

        if (!empty($cart_id)) {
            $this->context->cart->id_currency = $this->context->currency->id;
            $this->context->cart = new Cart($cart_id);
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
            $this->context->cookie->write();
        }

        if (empty($email)) {
            $psdata = "An email address required";
            $messageCode = 301;
        } elseif (!Validate::isEmail($email)) {
            $psdata = "Invalid email address";
            $messageCode = 302;
        } elseif (empty($password)) {
            $psdata = 'Password is not provided';
            $messageCode = 303;
        } elseif (!Validate::isPasswd($password)) {
            $psdata = "Invalid Password";
            $messageCode = 304;
        } else {
            Hook::exec('actionAuthenticationBefore');
            $customer = new Customer();
            $authentication = $customer->getByEmail(
                $email,
                $password
            );

            if (isset($authentication->active) && !$authentication->active) {
                $psdata = 'Your account isn\'t available at this time.';
                $messageCode = 305;
            } elseif (!$authentication || !$customer->id || $customer->is_guest) {
                $psdata = "Authentication failed";
                $messageCode = 306;
            } else {
                $this->context->updateCustomer($customer);

                Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

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
