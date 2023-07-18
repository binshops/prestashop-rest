<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

use PrestaShop\PrestaShop\Core\Security\PasswordPolicyConfiguration;
use ZxcvbnPhp\Zxcvbn;

class BinshopsrestRegisterModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $psdata = "";
        $messageCode = 0;
        $hasError = false;
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
            $hasError = true;
        } elseif (!Validate::isEmail($email)) {
            $psdata = $this->trans("Invalid email address", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 302;
            $hasError = true;
        } elseif (empty($firstName)) {
            $psdata = $this->trans("First name required", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 305;
            $hasError = true;
        } elseif (empty($lastName)) {
            $psdata = $this->trans("Last name required", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 306;
            $hasError = true;
        } elseif (Customer::customerExists($email, false, true)) {
            $psdata = $this->trans("User already exists - checked by email", [], 'Modules.Binshopsrest.Auth');
            $messageCode = 308;
            $hasError = true;
        }else{
            if (version_compare(_PS_VERSION_, '8.0', '<=')) {
                if (!Validate::isPasswd($password)) {
                    $psdata = $this->trans("Invalid Password", [], 'Modules.Binshopsrest.Auth');
                    $messageCode = 309;
                    $hasError = true;
                }
            }else{
                if (Validate::isAcceptablePasswordLength($password) === false) {
                    $psdata = $this->trans('Password must be between %d and %d characters long',
                        [
                            Configuration::get(PasswordPolicyConfiguration::CONFIGURATION_MINIMUM_LENGTH),
                            Configuration::get(PasswordPolicyConfiguration::CONFIGURATION_MAXIMUM_LENGTH),
                        ],
                        'Modules.Binshopsrest.Auth');
                    $messageCode = 305;
                    $hasError = true;
                }

                if (Validate::isAcceptablePasswordScore($password) === false) {
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
                        $result = $zxcvbn->passwordStrength($password);
                        if (!empty($result['feedback']['warning'])) {
                            $psdata = $this->translator->trans(
                                $result['feedback']['warning'], [], 'Shop.Theme.Global'
                            );
                        } else {
                            $psdata = $globalErrorMessage;
                        }
                        foreach ($result['feedback']['suggestions'] as $suggestion) {
                            $psdata = $this->translator->trans($suggestion, [], 'Shop.Theme.Global');
                        }
                    } else {
                        $psdata = $globalErrorMessage;
                    }

                    $hasError = true;
                    $messageCode = 305;
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
