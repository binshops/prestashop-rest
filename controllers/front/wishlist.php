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

require_once dirname(__FILE__) . '/../AbstractProductListingRESTController.php';
require_once dirname(__FILE__) . '/../../classes/RESTProductLazyArray.php';
define('PRICE_REDUCTION_TYPE_PERCENT', 'percentage');

use PrestaShop\Module\BlockWishList\Search\WishListProductSearchProvider;
use PrestaShop\Module\BlockWishList\Access\CustomerAccess;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrderFactory;

class BinshopsrestWishlistModuleFrontController extends AbstractProductListingRESTController
{
    public $ssl = true;

    private $wishlist;

    protected function processGetRequest()
    {
        switch (Tools::getValue('action')){
            case 'list':
                $this->listWishlists();
                break;
        }
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

    /**
     * Action Methods
     * **********************************************************************
    */

    private function listWishlists(){
        $infos = WishList::getAllWishListsByIdCustomer($this->context->customer->id);
        if (empty($infos)) {
            $wishlist = new WishList();
            $wishlist->id_shop = $this->context->shop->id;
            $wishlist->id_shop_group = $this->context->shop->id_shop_group;
            $wishlist->id_customer = $this->context->customer->id;
            $wishlist->name = Configuration::get('blockwishlist_WishlistDefaultTitle', $this->context->language->id);
            $wishlist->default = 1;
            $wishlist->add();

            $infos = WishList::getAllWishListsByIdCustomer($this->context->customer->id);
        }

        if (false === empty($infos)) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => $infos,
                'message' => 'success'
            ]));
            die;
        }
    }

    /**
     * Helper methods
     * **********************************************************************
    */

    private function assertWriteAccess(WishList $wishlist)
    {
        if ((new CustomerAccess($this->context->customer))->hasWriteAccessToWishlist($wishlist)) {
            return;
        }

        $this->ajaxRender(json_encode([
            'success' => false,
            'code' => 340,
            'message' => $this->trans('You\'re not allowed to manage this list.', [], 'Modules.Blockwishlist.Shop')
        ]));
        die;
    }

    public function getListingLabel()
    {
        return $this->getTranslator()->trans('Your Wishlist', array(), 'Shop.Theme.Catalog');
    }

    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query->setSortOrder(
            new SortOrder(
                'product',
                Tools::getProductsOrder('by'),
                Tools::getProductsOrder('way')
            )
        );

        return $query;
    }

    protected function getDefaultProductSearchProvider()
    {
        return new WishListProductSearchProvider(
            Db::getInstance(),
            $this->wishlist,
            new SortOrderFactory($this->getTranslator()),
            $this->getTranslator()
        );
    }
}
