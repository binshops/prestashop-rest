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
require_once dirname(__FILE__) . '/../../classes/APIRoutes.php';

class BinshopsrestResetpasswordsetnewpassModuleFrontController extends AbstractRESTController
{
    private $customer;

    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $this->changePassword();

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'message' => $this->trans('Your password has been successfully reset and a confirmation has been sent to your email address: %s', [$this->customer->email], 'Shop.Notifications.Success'),
        ]));
        die;
    }

    protected function changePassword()
    {
        $token = Tools::getValue('token');
        $id_customer = (int) Tools::getValue('id_customer');
        $reset_token = Tools::getValue('reset_token');
        $email = Db::getInstance()->getValue(
            'SELECT `email` FROM ' . _DB_PREFIX_ . 'customer c WHERE c.`secure_key` = \'' . pSQL($token) . '\' AND c.id_customer = ' . $id_customer
        );
        if ($email) {
            $this->customer = new Customer();
            $this->customer->getByEmail($email);

            if (!Validate::isLoadedObject($this->customer)) {
                $this->errors[] = $this->trans('Customer account not found', [], 'Shop.Notifications.Error');
            } elseif (!$this->customer->active) {
                $this->errors[] = $this->trans('You cannot regenerate the password for this account.', [], 'Shop.Notifications.Error');
            } elseif ($this->customer->getValidResetPasswordToken() !== $reset_token) {
                $this->errors[] = $this->trans('The password change request expired. You should ask for a new one.', [], 'Shop.Notifications.Error');
            }

            if ($this->errors) {
                $this->ajaxRender(json_encode([
                    'code' => 310,
                    'success' => false,
                    'message' => $this->errors,
                ]));
                die;
            }

            if ($isSubmit = Tools::isSubmit('passwd')) {
                // If password is submitted validate pass and confirmation
                if (!$passwd = Tools::getValue('passwd')) {
                    $this->errors[] = $this->trans('The password is missing: please enter your new password.', [], 'Shop.Notifications.Error');
                }

                if (!$confirmation = Tools::getValue('confirmation')) {
                    $this->errors[] = $this->trans('The confirmation is empty: please fill in the password confirmation as well', [], 'Shop.Notifications.Error');
                }

                if ($passwd && $confirmation) {
                    if ($passwd !== $confirmation) {
                        $this->errors[] = $this->trans('The confirmation password doesn\'t match.', [], 'Shop.Notifications.Error');
                    }

                    if (!Validate::isPlaintextPassword($passwd)) {
                        $this->errors[] = $this->trans('The password is not in a valid format.', [], 'Shop.Notifications.Error');
                    }
                }
            }

            if (!$isSubmit || $this->errors) {
                // If password is NOT submitted OR there are errors, shows the form (and errors)
                $this->ajaxRender(json_encode([
                    'code' => 310,
                    'success' => false,
                    'message' => $this->errors,
                ]));
                die;
            } else {
                // Both password fields posted. Check if all is right and store new password properly.
                if (!$reset_token || (strtotime($this->customer->last_passwd_gen . '+' . (int) Configuration::get('PS_PASSWD_TIME_FRONT') . ' minutes') - time()) > 0) {
                    Tools::redirect('index.php?controller=authentication&error_regen_pwd');
                } else {
                    $this->customer->passwd = $this->get('hashing')->hash($password = Tools::getValue('passwd'), _COOKIE_KEY_);
                    $this->customer->last_passwd_gen = date('Y-m-d H:i:s', time());

                    if ($this->customer->update()) {
                        Hook::exec('actionPasswordRenew', ['customer' => $this->customer, 'password' => $password]);
                        $this->customer->removeResetPasswordToken();
                        $this->customer->update();

                        $mail_params = [
                            '{email}' => $this->customer->email,
                            '{lastname}' => $this->customer->lastname,
                            '{firstname}' => $this->customer->firstname,
                        ];

                        if (
                        Mail::Send(
                            $this->context->language->id,
                            'password',
                            $this->trans(
                                'Your new password',
                                [],
                                'Emails.Subject'
                            ),
                            $mail_params,
                            $this->customer->email,
                            $this->customer->firstname . ' ' . $this->customer->lastname
                        )
                        ) {
                            $this->context->updateCustomer($this->customer);
                        } else {
                            $this->errors[] = $this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error');
                        }
                    } else {
                        $this->errors[] = $this->trans('An error occurred with your account, which prevents us from updating the new password. Please report this issue using the contact form.', [], 'Shop.Notifications.Error');
                    }
                }
            }
        } else {
            $this->errors[] = $this->trans('We cannot regenerate your password with the data you\'ve submitted', [], 'Shop.Notifications.Error');
        }

        if ($this->errors) {
            $this->ajaxRender(json_encode([
                'code' => 310,
                'success' => false,
                'message' => $this->errors,
            ]));
            die;
        }
    }
}
