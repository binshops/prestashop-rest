<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS - contact@binshops.com
 * @copyright BINSHOPS
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';

class BinshopsrestAddressformModuleFrontController extends AbstractRESTController
{

    protected function processGetRequest()
    {
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $availableCountries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $availableCountries = Country::getCountries($this->context->language->id, true);
        }

        $psdata = array();
        $psdata['countries'] = $availableCountries;

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'psdata' => $psdata
        ]));
        die;
    }

    protected function processPostRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'POST not supported on this path'
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
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'delete not supported on this path'
        ]));
        die;
    }
}


