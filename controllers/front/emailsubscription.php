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

class BinshopsrestEmailsubscriptionModuleFrontController extends AbstractRESTController
{

    protected function processGetRequest()
    {
        $conditions = Configuration::get('NW_CONDITIONS', $this->context->language->id);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $conditions,
            'message' => $this->trans('Success', [], 'Modules.Binshopsrest.Subscription')
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $ps_emailsubscription = Module::getInstanceByName('ps_emailsubscription');
        $email = Tools::getValue('email', '');

        if ($ps_emailsubscription->isNewsletterRegistered($email) === 1 || $ps_emailsubscription->isNewsletterRegistered($email) === 2){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'message' => $this->trans('This email is already registered', [], 'Modules.Binshopsrest.Subscription')
            ]));
            die;
        }

        if ($this->registerGuest($email)) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'message' => $this->trans('Success', [], 'Modules.Binshopsrest.Subscription')
            ]));
            die;

        } elseif ($ps_emailsubscription->valid) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 300,
                'message' => $this->trans('Failure', [], 'Modules.Binshopsrest.Subscription')
            ]));
            die;
        }
    }

    protected function registerGuest($email, $active = true)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'emailsubscription (id_shop, id_shop_group, email, newsletter_date_add, ip_registration_newsletter, http_referer, active, id_lang)
                VALUES
                (' . $this->context->shop->id . ',
                ' . $this->context->shop->id_shop_group . ',
                \'' . pSQL($email) . '\',
                NOW(),
                \'' . pSQL(Tools::getRemoteAddr()) . '\',
                (
                    SELECT c.http_referer
                    FROM ' . _DB_PREFIX_ . 'connections c
                    WHERE c.id_guest = ' . (int) $this->context->customer->id . '
                    ORDER BY c.date_add DESC LIMIT 1
                ),
                ' . (int) $active . ',
                ' . $this->context->language->id . '
                )';

        return Db::getInstance()->execute($sql);
    }
}
