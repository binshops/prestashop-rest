<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestRegisterModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $psdata = "";
        $messageCode = 0;
        $success = true;
        $firstName = Tools::getValue('firstName');
        $lastName = Tools::getValue('lastName');
        $email = Tools::getValue('email');
        $password = Tools::getValue('password');
        $gender = Tools::getValue('gender');
        $newsletter = Tools::getValue('newsletter');

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
        } elseif (empty($firstName)) {
            $psdata = $this->trans("First name required", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 305;
        } elseif (empty($lastName)) {
            $psdata = $this->trans("Last name required", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 306;
        } elseif (Customer::customerExists($email, false, true)) {
            $psdata = $this->trans("User already exists - checked by email", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 308;
        } else {
            $guestAllowedCheckout = Configuration::get('PS_GUEST_CHECKOUT_ENABLED');
            $cp = new CustomerPersister(
                $this->context,
                $this->get('hashing'),
                $this->getTranslator(),
                $guestAllowedCheckout
            );
            try {
                $customer = new Customer();
                $customer->firstname = $firstName;
                $customer->lastname = $lastName;
                $customer->email = $email;
                $customer->id_gender = $gender;
                $customer->id_shop = (int)$this->context->shop->id;
                $customer->newsletter = $newsletter;

                $status = $cp->save($customer, $password);

                $messageCode = 200;
                $psdata = array(
                    'registered' => $status,
                    'message' => $this->trans('User registered successfully', [], 'Modules.Binshopsrest.Auth'),
                    'customer_id' => $customer->id,
                    'session_data' => (int)$this->context->cart->id
                );
            } catch (Exception $exception) {
                $messageCode = 300;
                $psdata = $exception->getMessage();
                $success = false;
            }
        }

        $this->ajaxRender(json_encode([
            'success' => $success,
            'code' => $messageCode,
            'psdata' => $psdata
        ]));
        die;
    }
}
