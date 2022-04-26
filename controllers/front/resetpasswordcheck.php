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

class BinshopsrestResetpasswordcheckModuleFrontController extends AbstractRESTController
{
    protected function processPostRequest()
    {
        $_POST = json_decode(Tools::file_get_contents('php://input'), true);

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
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $this->trans("success", [], 'Modules.Binshopsrest.Auth')
            ]));
            die;
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
