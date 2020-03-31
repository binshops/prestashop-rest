<?php
require_once __DIR__ . '/../AbstractRESTController.php';

class BinshopsrestRegisterModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'GET not supported on this path'
        ]));
        die;
    }

    protected function processPostRequest(){
        $psdata = ""; $messageCode = 0; $success = true;
        $firstName = Tools::getValue('firstName');
        $lastName = Tools::getValue('lastName');
        $email = Tools::getValue('email');
        $password = Tools::getValue('password');
        $gender = Tools::getValue('gender');
        $newsletter = Tools::getValue('newsletter');

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
        } elseif (empty($firstName)) {
            $psdata = "First name required";
            $messageCode = 305;
        }elseif (empty($lastName)) {
            $psdata = "Last name required";
            $messageCode = 306;
        }elseif (empty($gender)) {
            $psdata = "gender required";
            $messageCode = 307;
        }elseif(Customer::customerExists($email, false, true)) {
            $psdata = "User already exists - checked by email";
            $messageCode = 308;
        }else{
            $guestAllowedCheckout = Configuration::get('PS_GUEST_CHECKOUT_ENABLED');
            $cp = new CustomerPersister(
                $this->context,
                $this->get('hashing'),
                $this->getTranslator(),
                $guestAllowedCheckout
            );
            try{
                $customer = new Customer();
                $customer->firstname = $firstName;
                $customer->lastname = $lastName;
                $customer->email = $email;
                $customer->id_gender = $gender;
                $customer->id_shop = (int) $this->context->shop->id;
                $customer->newsletter = $newsletter;

                $status = $cp->save($customer, $password);

                $psdata = array(
                    'registered' => $status,
                    'message' => 'User registered successfully',
                    'customer_id' => $customer->id,
                    'session_data' => (int)$this->context->cart->id
                );
            }catch (Exception $exception){
                $messageCode = 300;
                $psdata = "Internal Server Error";
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