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

class BinshopsrestResetpasswordbyemailModuleFrontController extends AbstractRESTController
{
    private $psdata;

    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);
        $this->psdata = $this->trans("pass reset mail successfully sent", [], 'Modules.Binshopsrest.Auth');

        $this->sendRenewPasswordLink();

        $this->ajaxRender(json_encode([
            'success' => false,
            'code' => 200,
            'psdata' => $this->psdata
        ]));
        die;
    }

    protected function sendRenewPasswordLink()
    {
        if (!($email = (trim(Tools::getValue('email')))) || !Validate::isEmail($email)) {
            $this->psdata = $this->trans('Invalid email address.', [], 'Shop.Notifications.Error');
        } else {
            $customer = new Customer();
            $customer->getByEmail($email);
            if (null === $customer->email) {
                $customer->email = Tools::getValue('email');
            }

            if (!Validate::isLoadedObject($customer)) {
            } elseif (!$customer->active) {
                $this->psdata = $this->trans('You cannot regenerate the password for this account.', [], 'Shop.Notifications.Error');
            } elseif ((strtotime($customer->last_passwd_gen . '+' . ($minTime = (int)Configuration::get('PS_PASSWD_TIME_FRONT')) . ' minutes') - time()) > 0) {
                $this->psdata = $this->trans('You can regenerate your password only every %d minute(s)', [(int)$minTime], 'Shop.Notifications.Error');
            } else {
                if (!$customer->hasRecentResetPasswordToken()) {
                    $customer->stampResetPasswordToken();
                    $customer->update();
                }

                $mailParams = [
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                    '{url}' => Configuration::get('BINSHOPSREST_FRONT_END_SERVER_URL') . "/password-recovery?" . 'token=' . $customer->secure_key . '&id_customer=' . (int)$customer->id . '&reset_token=' . $customer->reset_password_token
                ];

                if (Mail::Send(
                    $this->context->language->id,
                    'password_query',
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
                } else {
                    $this->psdata = $this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error');
                }
            }
        }
    }
}
