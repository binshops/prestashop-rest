<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 * @license https://www.binshops.com
 */

require_once dirname(__FILE__) . '/../AbstractAuthRESTController.php';

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class BinshopsrestAddressModuleFrontController extends AbstractAuthRESTController
{

    protected function processGetRequest()
    {
        $address = new Address(
            Tools::getValue('id_address'),
            $this->context->language->id
        );

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $address
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        $psdata = array();
        $msg = "";
        $validate_obj = $this->validatePost();

        if (!$validate_obj['valid']) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 301,
                'psdata' => $validate_obj['errors']
            ]));
            die;
        }

        if (Tools::getValue('id_address')) {
            $msg = "Successfully updated address";
        } else {
            $msg = "Successfully added address";
        }

        $address = new Address(
            Tools::getValue('id_address'),
            $this->context->language->id
        );

        $deliveryOptionsFinder = new DeliveryOptionsFinder(
            $this->context,
            $this->getTranslator(),
            $this->objectPresenter,
            new PriceFormatter()
        );
        $session = new CheckoutSession(
            $this->context,
            $deliveryOptionsFinder
        );

        $address->firstname = $session->getCustomer()->firstname;
        $address->lastname = $session->getCustomer()->lastname;

        $address->alias = Tools::getValue('alias');
        $address->id_country = Tools::getValue('id_country');
        $address->country = Tools::getValue('country');
        $address->id_state = Tools::getValue('id_state');
        $address->postcode = Tools::getValue('postcode');
        $address->city = Tools::getValue('city');
        $address->address1 = Tools::getValue('address1');
        $address->address2 = Tools::getValue('address2');
        $address->company = Tools::getValue('company');
        $address->other = Tools::getValue('other');
        $address->phone = Tools::getValue('phone');
        $address->phone_mobile = Tools::getValue('phone_mobile');
        $address->vat_number = Tools::getValue('vat_number');

        Hook::exec('actionSubmitCustomerAddressForm', ['address' => &$address]);

        $persister = new CustomerAddressPersister(
            $this->context->customer,
            $this->context->cart,
            Tools::getToken(true, $this->context)
        );

        $saved = $persister->save(
            $address,
            Tools::getToken(true, $this->context)
        );

        if (!$saved) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 302,
                'psdata' => "internal-server-error"
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $saved,
            'message' => $msg
        ]));
        die;
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
        $_POST = json_decode(file_get_contents('php://input'), true);
        Tools::getValue('id_address');

        $address = new Address(
            Tools::getValue('id_address'),
            $this->context->language->id
        );

        if ($address->id) {
            $address->deleted = true;

            $persister = new CustomerAddressPersister(
                $this->context->customer,
                $this->context->cart
            );

            $saved = $persister->save(
                $address,
                Tools::getToken(true, $this->context)
            );
        } else {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 301,
                'message' => "There is not such address"
            ]));
            die;
        }

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $saved,
            'message' => "Address successfully deleted"
        ]));
        die;
    }

    public function validatePost()
    {
        $psdata = array();
        $psdata['valid'] = true;
        $psdata['errors'] = array();

        if (!Tools::getValue('alias')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "alias-required";
        }
        if (!Tools::getValue('postcode')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "postcode-required";
        }
        if (!Tools::getValue('address1')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "address1-required";
        }
        if (!Tools::getValue('id_country')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "id_country-required";
        }
        if (!Tools::getValue('country')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "country-required";
        }
        if (!Tools::getValue('id_state')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "id_state-required";
        }
        if (!Tools::getValue('city')) {
            $psdata['valid'] = false;
            $psdata['errors'][] = "city-required";
        }

        return $psdata;
    }
}


