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
        parent::init();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PATCH':
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;
            default:
                // throw some error or whatever
        }
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
