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

class BinshopsrestResetpasswordemailModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

        $this->sendRenewPasswordLink();

        $this->ajaxRender(json_encode([
            'success' => $this->trans("Successfully send rest-code", [], 'Modules.Binshopsrest.Auth'),
            'code' => 200
        ]));
        die;
    }

    protected function sendRenewPasswordLink()
    {
        if (!($email = Tools::getValue('email')) || !Validate::isEmail($email)) {
            $this->errors[] = $this->trans('Invalid email address.', [], 'Shop.Notifications.Error');
        } else {
            $customer = new Customer();
            $customer->getByEmail($email);
            if (null === $customer->email) {
                $customer->email = Tools::getValue('email');
            }

            if (!Validate::isLoadedObject($customer)) {
                $this->success[] = $this->trans(
                    'If this email address has been registered in our shop, you will receive a link to reset your password at %email%.',
                    ['%email%' => $customer->email],
                    'Shop.Notifications.Success'
                );
                $this->setTemplate('customer/password-infos');
            } elseif (!$customer->active) {
                $this->errors[] = $this->trans('You cannot regenerate the password for this account.', [], 'Shop.Notifications.Error');
            } elseif ((strtotime($customer->last_passwd_gen . '+' . ($minTime = (int)Configuration::get('PS_PASSWD_TIME_FRONT')) . ' minutes') - time()) > 0) {
                $this->errors[] = $this->trans('You can regenerate your password only every %d minute(s)', [(int)$minTime], 'Shop.Notifications.Error');
            } else {
                $gen_code = $this->hasRecentResetPasswordToken($customer);
                if (empty($gen_code)) {
                    $gen_code = $this->stampResetPasswordToken($customer);
                }

                $mailParams = [
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                    '{gen_code}' => $gen_code,
                ];

                if (Mail::Send(
                    $this->context->language->id,
                    'password_query_mobile',
                    $this->trans(
                        'Password query confirmation',
                        [],
                        'Emails.Subject'
                    ),
                    $mailParams,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname
                )
                ) {
                    $this->success[] = $this->trans('If this email address has been registered in our shop, you will receive a link to reset your password at %email%.', ['%email%' => $customer->email], 'Shop.Notifications.Success');
                    $this->setTemplate('customer/password-infos');
                } else {
                    $this->errors[] = $this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error');
                }
            }
        }
    }

    public function stampResetPasswordToken($customer)
    {
        $digits = 5;
        $rand = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
        $validity = (int)Configuration::get('PS_PASSWD_RESET_VALIDITY') ?: 1440;
        $reset_password_validity = date('Y-m-d H:i:s', strtotime('+' . $validity . ' minutes'));

        $db = Db::getInstance();
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'binshopsrest_reset_pass_tokens` (`reset_password_token`, `reset_password_validity`,`id_customer`)
				VALUES (\'' . $rand . '\',\'' . $reset_password_validity . '\',' . $customer->id . ')';
        $db->execute($sql);
        return $rand;
    }

    public function hasRecentResetPasswordToken($customer)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'binshopsrest_reset_pass_tokens`
            WHERE id_customer =' . $customer->id;
        $result = Db::getInstance()->executeS($sql);

        if (empty($result)) {
            return false;
        } elseif (strtotime(end($result)['reset_password_validity']) < time()) {
            return false;
        }

        return end($result)['reset_password_token'];
    }
}
