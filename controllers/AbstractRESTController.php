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

require_once dirname(__FILE__) . '/../classes/RESTTrait.php';

abstract class AbstractRESTController extends ModuleFrontController
{
    use RESTTrait;

    private $img1 = 'large';
    private $img2 = 'medium';
    private $img3 = '_default';

    public function init()
    {
        header('Content-Type: ' . "application/json");

        if (Tools::getValue('iso_currency')){
            $_GET['id_currency'] = (string)Currency::getIdByIsoCode(Tools::getValue('iso_currency'));
            $_GET['SubmitCurrency'] = "1";
        }

        parent::init();

        $response = [
            'success' => true,
            'code' => 210,
            'psdata' => null,
            'message' => 'empty'
        ];

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $response = $this->processGetRequest();
                break;
            case 'POST':
                $response = $this->processPostRequest();
                break;
            case 'PATCH':
            case 'PUT':
                $response = $this->processPutRequest();
                break;
            case 'DELETE':
                $response = $this->processDeleteRequest();
                break;
            default:
                // throw some error or whatever
        }

        $this->ajaxRender(json_encode($response));
        die;
    }

    public function formatPrice($price)
    {
        return Tools::displayPrice(
            $price,
            $this->context->currency,
            false,
            $this->context
        );
    }

    public function getImageType($type = 'large')
    {
        if ($type == 'large') {
            return $this->img1 . $this->img3;
        } elseif ($type == 'medium') {
            return $this->img2 . $this->img3;
        } else {
            return $this->img1 . $this->img3;
        }
    }
}
