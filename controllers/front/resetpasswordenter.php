<?php
/**
 * BINSHOPS | Best In Shops
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestResetpasswordenterModuleFrontController extends AbstractRESTController
{
    private $psdata;

    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $this->changePassword();

        $this->ajaxRender(json_encode([
            'success' => false,
            'code' => 200,
            'psdata' => "password reset successfully"
        ]));
        die;
    }

    protected function changePassword()
    {
        if (!($email = Tools::getValue('email')) || !Validate::isEmail($email)) {
            $this->errors[] = $this->trans('Invalid email address.', [], 'Shop.Notifications.Error');
        } else {
            $customer = new Customer();
            $customer->getByEmail($email);
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'binshopsrest_reset_pass_tokens`
            WHERE id_customer =' . $customer->id;
        $result = Db::getInstance()->executeS($sql);

        if (empty($result)) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $this->trans("this state is not expected", [], 'Modules.Binshopsrest.Auth')
            ]));
            die;
        } elseif (strtotime(end($result)['reset_password_validity']) < time()) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $this->trans("expired", [], 'Modules.Binshopsrest.Auth')
            ]));
            die;
        }

        $theCode = end($result)['reset_password_token'];

        if (Tools::getValue('pass-code') === $theCode) {
            if (!$passwd = Tools::getValue('passwd')) {
                $this->psdata = $this->trans('The password is missing: please enter your new password.', [], 'Shop.Notifications.Error');
            }

            if (!$confirmation = Tools::getValue('confirmation')) {
                $this->psdata = $this->trans('The confirmation is empty: please fill in the password confirmation as well', [], 'Shop.Notifications.Error');
            }

            if ($passwd && $confirmation) {
                if ($passwd !== $confirmation) {
                    $this->psdata = $this->trans('The password and its confirmation do not match.', [], 'Shop.Notifications.Error');
                }

                if (!Validate::isPasswd($passwd)) {
                    $this->psdata = $this->trans('The password is not in a valid format.', [], 'Shop.Notifications.Error');
                }
            }
            $customer->passwd = $this->get('hashing')->hash($password = Tools::getValue('passwd'), _COOKIE_KEY_);
            $customer->last_passwd_gen = date('Y-m-d H:i:s', time());

            if ($customer->update()) {
                Hook::exec('actionPasswordRenew', ['customer' => $customer, 'password' => $password]);
                $customer->removeResetPasswordToken();
                $customer->update();

                $mail_params = [
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                ];

                if (Mail::Send(
                    $this->context->language->id,
                    'password',
                    $this->trans(
                        'Your new password',
                        [],
                        'Emails.Subject'
                    ),
                    $mail_params,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname
                )
                ) {
                    $this->context->smarty->assign([
                        'customer_email' => $customer->email,
                    ]);
                    $this->success[] = $this->trans('Your password has been successfully reset and a confirmation has been sent to your email address: %s', [$customer->email], 'Shop.Notifications.Success');
                    $this->context->updateCustomer($customer);
                    $this->redirectWithNotifications('index.php?controller=my-account');
                } else {
                    $this->errors[] = $this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error');
                }
            } else {
                $this->errors[] = $this->trans('An error occurred with your account, which prevents us from updating the new password. Please report this issue using the contact form.', [], 'Shop.Notifications.Error');
            }
        } else {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'psdata' => $this->trans("code not matched", [], 'Modules.Binshopsrest.Auth')
            ]));
            die;
        }
    }
}
