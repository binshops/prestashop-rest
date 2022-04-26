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

require_once dirname(__FILE__) . '/../AbstractProductListingRESTController.php';
require_once dirname(__FILE__) . '/../../classes/RESTProductLazyArray.php';
define('PRICE_REDUCTION_TYPE_PERCENT', 'percentage');

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

/**
 * This REST endpoint gets details of a product
 *
 * This module can be used to get category products, pagination and faceted search
 */
class BinshopsrestCategoryproductsModuleFrontController extends AbstractProductListingRESTController
{
    protected function processGetRequest()
    {
        if ((int)Tools::getValue('id_category')){
            $id_category = (int)Tools::getValue('id_category');
        }elseif (Tools::getValue('slug')){
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . "category_lang`
            WHERE link_rewrite = '" . Tools::getValue('slug') . "'";
            $result = Db::getInstance()->executeS($sql);

            if (empty($result)){
                $this->ajaxRender(json_encode([
                    'code' => 302,
                    'success' => false,
                    'message' => $this->trans('There is not a category with this slug', [], 'Modules.Binshopsrest.Category')
                ]));
                die;
            }else{
                $this->id_category = $result[0]['id_category'];
                $id_category = $result[0]['id_category'];
                $_POST['id_category'] = $id_category;
            }
        }else{
            $this->ajaxRender(json_encode([
                'code' => 301,
                'success' => false,
                'message' => $this->trans('Id category or slug not specified', [], 'Modules.Binshopsrest.Category')
            ]));
            die;
        }

        $this->category = new Category(
            $id_category,
            $this->context->language->id
        );

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

        $facets = array();
        foreach ($variables['facets']['filters']->getFacets() as $facet) {
            array_push($facets, $facet->toArray());
        }

        $psdata = [
            'description' => $this->category->description,
            'active' => $this->category->active,
            'images' => $this->getImage(
                $this->category,
                $this->category->id_image
            ),
            'label' => $variables['label'],
            'products' => $productList,
            'sort_orders' => $variables['sort_orders'],
            'sort_selected' => $variables['sort_selected'],
            'pagination' => $variables['pagination'],
            'facets' => $facets
        ];

        if (Tools::getValue('with_category_tree')){
            $this->context->cookie->last_visited_category = $id_category;
            $categoryTreeModule = Module::getInstanceByName('ps_categorytree');
            $categoryTreeVariables = $categoryTreeModule->getWidgetVariables();
            $psdata['categories'] = $categoryTreeVariables['categories'];
        }

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
            'psdata' => $psdata
        ]));
        die;
    }

    public function getListingLabel()
    {
        if (!Validate::isLoadedObject($this->category)) {
            $this->category = new Category(
                (int)Tools::getValue('id_category'),
                $this->context->language->id
            );
        }

        return $this->trans(
            'Category: %category_name%',
            array('%category_name%' => $this->category->name),
            'Shop.Theme.Catalog'
        );
    }

    /**
     * Gets the product search query for the controller.
     * That is, the minimum contract with which search modules
     * must comply.
     *
     * @return \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery
     */
    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setIdCategory($this->category->id)
            ->setSortOrder(new SortOrder('product', Tools::getProductsOrder('by'), Tools::getProductsOrder('way')));

        return $query;
    }

    /**
     * We cannot assume that modules will handle the query,
     * so we need a default implementation for the search provider.
     *
     * @return \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface
     */
    protected function getDefaultProductSearchProvider()
    {
        return new CategoryProductSearchProvider(
            $this->getTranslator(),
            $this->category
        );
    }
}
