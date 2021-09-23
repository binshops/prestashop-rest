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
require_once dirname(__FILE__) . '/../../classes/RESTMainMenu.php';
require_once dirname(__FILE__) . '/../../classes/RESTProductLazyArray.php';

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

/**
 * Description: This class bootstraps the main page of the application
 * */

class BinshopsrestLightbootstrapModuleFrontController extends AbstractRESTController
{
    protected $banner;

    protected function processGetRequest()
    {
        $messageCode = 200;
        $mainMenu = Module::getInstanceByName('ps_mainmenu');

        $restMenu = new RESTMainMenu();

        $menuItems = $restMenu->renderMenu($this->context, $mainMenu);

        if (Tools::getValue('menu_with_images', false)){
            foreach ($menuItems as $key => $item) {
                $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
                    $this->context->link
                );
                $category = new Category(
                    Tools::substr($item['page_identifier'], -1),
                    $this->context->language->id
                );
                if (Tools::getValue('menu_with_images', 'all') === "single"){
                    $menuItems[$key]['image']['src'] =$this->context->link->getImageLink(
                        urlencode($item['slug']),
                        ($category->id . '-' . $category->id_image),
                        $this->getImageType('large')
                    );
                }else{
                    $menuItems[$key]['images'] = $retriever->getImage(
                        $category,
                        $category->id_image
                    );
                }
            }
        }

        $psdata = array();
        $psdata['menuItems'] = $menuItems;

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => $messageCode,
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
