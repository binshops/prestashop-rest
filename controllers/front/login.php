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
            $psdata = $this->trans("An email address required", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 301;
        } elseif (!Validate::isEmail($email)) {
            $psdata = $this->trans("Invalid email address", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 302;
        } elseif (empty($password)) {
            $psdata = $this->trans('Password is not provided', [], 'Modules.Binshopsrest.Auth');
            $messageCode = 303;
        } elseif (!Validate::isPasswd($password)) {
            $psdata = $this->trans("Invalid Password", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 304;
        } else {
            Hook::exec('actionAuthenticationBefore');
            $customer = new Customer();
            $authentication = $customer->getByEmail(
                $email,
                $password
            );

            if (isset($authentication->active) && !$authentication->active) {
                $psdata = $this->trans('Your account isn\'t available at this time.', [], 'Modules.Binshopsrest.Auth');
                $messageCode = 305;
            } elseif (!$authentication || !$customer->id || $customer->is_guest) {
                $psdata = $this->trans("Authentication failed", [], 'Modules.Binshopsrest.Auth');
                $messageCode = 306;
            } else {
                $this->context->updateCustomer($customer);

                Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

                $messageCode = 200;
                $user = $this->context->customer;
                unset($user->secure_key);
                unset($user->passwd);
                unset($user->last_passwd_gen);
                unset($user->reset_password_token);
                unset($user->reset_password_validity);

                $psdata = array(
                    'status' => 'success',
                    'message' => $this->trans('User login successfully', [], 'Modules.Binshopsrest.Auth'),
                    'customer_id' => $customer->id,
                    'session_data' => (int)$this->context->cart->id,
                    'cart_count' => Cart::getNbProducts($this->context->cookie->id_cart),
                    'user' => $user
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
}
