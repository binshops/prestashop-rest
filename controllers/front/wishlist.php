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
        if (empty($this->context->customer->id)){
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 300,
                'message' => $this->trans('You must be logged in to use wishlist', [], 'Modules.Binshopsrest.Wishlist')
            ]));
            die;
        }

        switch (Tools::getValue('action')){
            case 'list':
                $this->listWishlists();
                break;
            case 'addProductToWishlist':
                $this->addToWishlist();
                break;
            case 'viewWishlist':
                $this->viewWishlist();
                break;
            case 'deleteProductFromWishList':
                $this->deleteProductFromWishList();
                break;
            case 'createWishlist':
                $this->createWishlist();
                break;
            case 'deleteWishlist':
                $this->deleteWishlist();
                break;
            case 'renameWishlist':
                $this->renameWishlist();
                break;
        }
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
                'message' => $this->trans('success', [], 'Modules.Binshopsrest.Wishlist')
            ]));
            die;
        }
    }

    private function addToWishlist(){
        $id_product = Tools::getValue('id_product');
        $product = new Product($id_product);
        if (!Validate::isLoadedObject($product)) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 310,
                'message' => $this->trans('There was an error adding the product', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }

        $idWishList = Tools::getValue('idWishList');

        $id_product_attribute = Tools::getValue('id_product_attribute');
        if (!$id_product_attribute){
            $id_product_attribute = $product->getDefaultIdProductAttribute();
        }

        $quantity = Tools::getValue('quantity');
        if (0 === $quantity) {
            $quantity = $product->minimal_quantity;
        }

        if (false === $this->assertProductAttributeExists($id_product, $id_product_attribute) && $id_product_attribute !== 0) {

            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 320,
                'message' => $this->trans('There was an error while adding the product attributes', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }

        if (!$idWishList){
            $infos = WishList::getAllWishListsByIdCustomer($this->context->customer->id);
            if (empty($infos)) {
                $wishlist = new WishList();
                $wishlist->id_shop = $this->context->shop->id;
                $wishlist->id_shop_group = $this->context->shop->id_shop_group;
                $wishlist->id_customer = $this->context->customer->id;
                $wishlist->name = Configuration::get('blockwishlist_WishlistDefaultTitle', $this->context->language->id);
                $wishlist->default = 1;
                $wishlist->token = $this->generateWishListToken();

                $wishlist->add();

                $infos = WishList::getAllWishListsByIdCustomer($this->context->customer->id);
            }
            $wishlist = $infos[0];
            $idWishList = $wishlist['id_wishlist'];
            $wishlist = new WishList($wishlist['id_wishlist']);
        }else{
            $wishlist = new WishList($idWishList);
        }

        // Exit if not owner of the wishlist
        $this->assertWriteAccess($wishlist);

        $productIsAdded = $wishlist->addProduct(
            $idWishList,
            $this->context->customer->id,
            $id_product,
            $id_product_attribute,
            $quantity
        );

        $newStat = new Statistics();
        $newStat->id_product = $id_product;
        $newStat->id_product_attribute = $id_product_attribute;
        $newStat->id_shop = $this->context->shop->id;
        $newStat->save();

        if (false === $productIsAdded) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 330,
                'message' => $this->trans('There was an error adding the product', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }

        Hook::exec('actionWishlistAddProduct', [
            'idWishlist' => $idWishList,
            'customerId' => $this->context->customer->id,
            'idProduct' => $id_product,
            'idProductAttribute' => $id_product_attribute,
        ]);

        $wishlistData = $this->getWishlistData($idWishList);

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => 200,
            'message' => $this->trans('Product added', [], 'Modules.Blockwishlist.Shop'),
            'psdata' =>[
                'wishlistName' => $this->wishlist->name,
                'label' => $wishlistData['label'],
                'products' => $wishlistData['products']
            ]
        ]));
        die;
    }

    private function viewWishlist(){
        $idWishList = Tools::getValue('id_wishlist');
        $wishlistData = $this->getWishlistData($idWishList);

        $psdata = [
            'wishlistName' => $this->wishlist->name,
            'label' => $wishlistData['label'],
            'products' => $wishlistData['products']
        ];

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => $psdata,
            'message' => $this->trans('success', [], 'Modules.Binshopsrest.Wishlist')
        ]));
        die;
    }

    private function deleteProductFromWishList(){
        $id_product = Tools::getValue('id_product');
        $idWishList = Tools::getValue('idWishList');
        $id_product_attribute = Tools::getValue('id_product_attribute');
        $product = new Product($id_product);

        if (!$id_product){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 310,
                'message' => $this->trans('Id product is not set', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }

        if (!$id_product_attribute){
            $id_product_attribute = $product->getDefaultIdProductAttribute();
        }

        if (!$idWishList){
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
            $wishlist = $infos[0];
            $idWishList = $wishlist['id_wishlist'];
            $wishlist = new WishList($wishlist['id_wishlist']);
        }else{
            $wishlist = new WishList($idWishList);
        }

        $this->assertWriteAccess($wishlist);

        $isDeleted = WishList::removeProduct(
            $idWishList,
            $this->context->customer->id,
            $id_product,
            $id_product_attribute
        );

        $wishlistData = $this->getWishlistData($idWishList);

        if ($isDeleted) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'message' => $this->trans('Product successfully removed', [], 'Modules.Blockwishlist.Shop'),
                'psdata' =>[
                    'wishlistName' => $wishlist->name,
                    'label' => $wishlistData['label'],
                    'products' => $wishlistData['products']
                ]
            ]));
            die;
        }
    }

    private function createWishlist(){
        $wishlistName = Tools::getValue('name');

        if (!$wishlistName){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 310,
                'message' => $this->trans('Wishlist name required', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }

        $wishlist = new WishList();
        $wishlist->name = $wishlistName;
        $wishlist->id_shop_group = $this->context->shop->id_shop_group;
        $wishlist->id_customer = $this->context->customer->id;
        $wishlist->id_shop = $this->context->shop->id;
        $wishlist->token = $this->generateWishListToken();

        if ($wishlist->save()) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'psdata' => [
                    'name' => $wishlist->name,
                    'id_wishlist' => $wishlist->id,
                ],
                'message' => $this->trans('The list has been properly created', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }
    }

    private function deleteWishlist(){
        if (!Tools::getValue('idWishList')){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 310,
                'message' => $this->trans('Wishlist id required', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }
        $wishlist = new WishList(Tools::getValue('idWishList'));

        $this->assertWriteAccess($wishlist);

        if (true === (bool) $wishlist->delete()) {
            $this->ajaxRender(json_encode([
                'success' => true,
                'code' => 200,
                'message' => $this->trans('List has been removed', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }
    }

    private function renameWishlist(){
        $wishlistName = Tools::getValue('name');
        $idWishList = Tools::getValue('idWishList');

        if (!$idWishList){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 310,
                'message' => $this->trans('Wishlist id required', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }elseif(!$wishlistName){
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 320,
                'message' => $this->trans('Wishlist name required', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }

        $wishlist = new WishList($idWishList);
        // Exit if not owner of the wishlist
        $this->assertWriteAccess($wishlist);

        $wishlist->name = $wishlistName;

        if ($wishlist->save()) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'code' => 200,
                'message' => $this->trans('List has been renamed', [], 'Modules.Blockwishlist.Shop')
            ]));
            die;
        }
    }

    /**
     * Helper methods
     * **********************************************************************
     */

    private function getWishlistData($idWishlist){
        if (!$idWishlist){
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
            $wishlist = $infos[0];
            $this->wishlist = new WishList($wishlist['id_wishlist']);
        }else{
            $this->wishlist = new WishList($idWishlist);
        }

        $this->assertReadAccess($this->wishlist);

        $variables = $this->getProductSearchVariables();
        $productList = $variables['products'];
        $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
            $this->context->link
        );

        $settings = $this->getProductPresentationSettings();

        foreach ($productList as $key => $product) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($product);

            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                $retriever,
                $this->context->getTranslator()
            );

            $productList[$key] = $lazy_product->getProduct();
        }

        return [
            'products' => $productList,
            'label' => $variables['label']
        ];
    }

    private function generateWishListToken()
    {
        return strtoupper(substr(sha1(uniqid((string) rand(), true) . _COOKIE_KEY_ . $this->context->customer->id), 0, 16));
    }

    private function assertProductAttributeExists($id_product, $id_product_attribute)
    {
        return Db::getInstance()->getValue(
            'SELECT pas.`id_product_attribute` ' .
            'FROM `' . _DB_PREFIX_ . 'product_attribute` pa ' .
            'INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON (pas.id_product_attribute = pa.id_product_attribute) ' .
            'WHERE pas.id_shop =' . (int) $this->context->shop->id . ' AND pa.`id_product` = ' . (int) $id_product . ' ' .
            'AND pas.id_product_attribute = ' . (int) $id_product_attribute
        );
    }

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

    public function assertReadAccess(WishList $wishlist)
    {
        $this->assertWriteAccess($wishlist);
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
