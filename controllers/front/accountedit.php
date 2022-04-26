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

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestAccounteditModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $psdata = null; $message = "success";
        $messageCode = 0;
        $success = true;
        $firstName = Tools::getValue('firstName');
        $lastName = Tools::getValue('lastName');
        $email = Tools::getValue('email');
        $password = Tools::getValue('password');
        $gender = Tools::getValue('gender');
        $newsletter = Tools::getValue('newsletter');

        $id_customer = Customer::customerExists($email, true, true);
        if ($id_customer && $id_customer != $this->context->customer->id) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 310,
                'psdata' => null,
                'message' => $this->trans('The email is already used, please choose another one or sign in', [], 'Modules.Binshopsrest.Account')
            ]));
            die;
        }

        $birthday = Tools::getValue('birthday');
        if (!empty($birthday) &&
            Validate::isBirthDate($birthday, $this->context->language->date_format_lite)
        ) {
            $dateBuilt = DateTime::createFromFormat(
                $this->context->language->date_format_lite,
                $birthday
            );
            $birthday = $dateBuilt->format('Y-m-d');
        }

        if (empty($email)) {
            $success = false;
            $message = "An email address required";
            $messageCode = 301;
        } elseif (!Validate::isEmail($email)) {
            $success = false;
            $message = "Invalid email address";
            $messageCode = 302;
        } elseif (empty($password)) {
            $success = false;
            $message = 'Password is not provided';
            $messageCode = 303;
        } elseif (!Validate::isPasswd($password)) {
            $success = false;
            $message = "Invalid Password";
            $messageCode = 304;
        } elseif (empty($firstName)) {
            $success = false;
            $message = "First name required";
            $messageCode = 305;
        } elseif (empty($lastName)) {
            $success = false;
            $message = "Last name required";
            $messageCode = 306;
        } elseif (empty($gender)) {
            $success = false;
            $message = "gender required";
            $messageCode = 307;
        } elseif (!Validate::isCustomerName($firstName)){
            $success = false;
            $message = "firstname bad format";
            $messageCode = 311;
        } elseif (!Validate::isCustomerName($lastName)){
            $success = false;
            $message = "lastname bad format";
            $messageCode = 312;
        } else {
            $guestAllowedCheckout = Configuration::get('PS_GUEST_CHECKOUT_ENABLED');
            $cp = new CustomerPersister(
                $this->context,
                $this->get('hashing'),
                $this->getTranslator(),
                $guestAllowedCheckout
            );
            try {
                $customer = new Customer($this->context->customer->id);
                $customer->firstname = $firstName;
                $customer->lastname = $lastName;
                $customer->email = $email;
                $customer->id_gender = $gender;
                $customer->id_shop = (int)$this->context->shop->id;
                $customer->newsletter = $newsletter;

                if (!empty($birthday)){
                    $customer->birthday = $birthday;
                }

                $clearTextPassword = Tools::getValue('password');
                $newPassword = Tools::getValue('new_password');

                $status = $cp->save(
                    $customer,
                    $clearTextPassword,
                    $newPassword,
                    true
                );

                if ($status) {
                    $messageCode = 200;
                    $message = 'User updated successfully';
                    $psdata = array(
                        'registered' => $status,
                        'message' => $this->trans('User updated successfully', [], 'Modules.Binshopsrest.Account'),
                        'customer_id' => $customer->id,
                        'session_data' => (int)$this->context->cart->id
                    );
                } else {
                    $success = false;
                    $messageCode = 350;
                    $message = 'could not update customer';
                    $psdata = array(
                        'registered' => $status,
                        'message' => $this->trans('Password Incorrect', [], 'Modules.Binshopsrest.Account'),
                        'customer_id' => $customer->id,
                        'session_data' => (int)$this->context->cart->id
                    );
                }
            } catch (Exception $exception) {
                $messageCode = 300;
                $message = $exception->getMessage();
                $success = false;
            }
        }

        $this->ajaxRender(json_encode([
            'success' => $success,
            'code' => $messageCode,
            'psdata' => $psdata,
            'message' => $message
        ]));
        die;
    }
}
