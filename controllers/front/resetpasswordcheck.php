<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 * @license https://www.binshops.com
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestResetpasswordcheckModuleFrontController extends AbstractRESTController
{

    protected function processGetRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'GET not supported on this path'
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);

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
                'psdata' => "this state is not expected"
            ]));
            die;
        } elseif (strtotime(end($result)['reset_password_validity']) < time()) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => "expired"
            ]));
            die;
        }

        $theCode = end($result)['reset_password_token'];

        if (Tools::getValue('pass-code') === $theCode) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => "success"
            ]));
            die;
        } else {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'psdata' => "code not matched"
            ]));
            die;
        }
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
