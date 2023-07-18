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

use PrestaShop\PrestaShop\Core\Security\PasswordPolicyConfiguration;
use ZxcvbnPhp\Zxcvbn;

class BinshopsrestAccounteditModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $psdata = null; $message = "success"; $hasError = false;
        $messageCode = 0;
        $success = true;
        $firstName = Tools::getValue('firstName');
        $lastName = Tools::getValue('lastName');
        $email = Tools::getValue('email');
        $password = Tools::getValue('password');
        $gender = Tools::getValue('gender');
        $newsletter = Tools::getValue('newsletter');
        $newPassword = Tools::getValue('new_password');

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
            $success = false; $hasError = true;
            $message = "An email address required";
            $messageCode = 301;
        } elseif (!Validate::isEmail($email)) {
            $success = false; $hasError = true;
            $message = "Invalid email address";
            $messageCode = 302;
        } elseif (empty($password)) {
            $success = false; $hasError = true;
            $message = 'Password is not provided';
            $messageCode = 303;
        } elseif (empty($firstName)) {
            $success = false; $hasError = true;
            $message = "First name required";
            $messageCode = 305;
        } elseif (empty($lastName)) {
            $success = false; $hasError = true;
            $message = "Last name required";
            $messageCode = 306;
        } elseif (empty($gender)) {
            $success = false; $hasError = true;
            $message = "gender required";
            $messageCode = 307;
        } elseif (!Validate::isCustomerName($firstName)){
            $success = false; $hasError = true;
            $message = "firstname bad format";
            $messageCode = 311;
        } elseif (!Validate::isCustomerName($lastName)){
            $success = false; $hasError = true;
            $message = "lastname bad format";
            $messageCode = 312;
        }elseif ($newPassword){
            if (version_compare(_PS_VERSION_, '8.0', '<=')) {
                if (!Validate::isPasswd($newPassword)) {
                    $success = false; $hasError = true;
                    $message = "Invalid Password";
                    $messageCode = 304;
                }
            }else{
                if (Validate::isAcceptablePasswordLength($newPassword) === false) {
                    $message = $this->trans('Password must be between %d and %d characters long',
                        [
                            Configuration::get(PasswordPolicyConfiguration::CONFIGURATION_MINIMUM_LENGTH),
                            Configuration::get(PasswordPolicyConfiguration::CONFIGURATION_MAXIMUM_LENGTH),
                        ],
                        'Modules.Binshopsrest.Auth');
                    $messageCode = 305;
                    $hasError = true;
                }

                if (Validate::isAcceptablePasswordScore($newPassword) === false) {
                    $wordingsForScore = [
                        $this->translator->trans('Very weak', [], 'Shop.Theme.Global'),
                        $this->translator->trans('Weak', [], 'Shop.Theme.Global'),
                        $this->translator->trans('Average', [], 'Shop.Theme.Global'),
                        $this->translator->trans('Strong', [], 'Shop.Theme.Global'),
                        $this->translator->trans('Very strong', [], 'Shop.Theme.Global'),
                    ];
                    $globalErrorMessage = $this->translator->trans(
                        'The minimum score must be: %s',
                        [
                            $wordingsForScore[(int) Configuration::get(PasswordPolicyConfiguration::CONFIGURATION_MINIMUM_SCORE)],
                        ],
                        'Shop.Notifications.Error'
                    );
                    if ($this->context->shop->theme->get('global_settings.new_password_policy_feature') !== true) {
                        $zxcvbn = new Zxcvbn();
                        $result = $zxcvbn->passwordStrength($newPassword);
                        if (!empty($result['feedback']['warning'])) {
                            $message = $this->translator->trans(
                                $result['feedback']['warning'], [], 'Shop.Theme.Global'
                            );
                        } else {
                            $message = $globalErrorMessage;
                        }
                        foreach ($result['feedback']['suggestions'] as $suggestion) {
                            $message = $this->translator->trans($suggestion, [], 'Shop.Theme.Global');
                        }
                    } else {
                        $message = $globalErrorMessage;
                    }

                    $hasError = true;
                    $messageCode = 304;
                }
            }
        }

        if (!$hasError){
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
