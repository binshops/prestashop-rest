<?php

require_once __DIR__ . '/../AbstractProductListingRESTController.php';
define('PRICE_REDUCTION_TYPE_PERCENT' , 'percentage');

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
        $id_category = (int) Tools::getValue('id_category');
        $this->category = new Category(
            $id_category,
            $this->context->language->id
        );

        if (!$id_category){
            $this->ajaxRender(json_encode([
                'code'=> 301,
                'success' => false,
                'message' => 'id category not specified'
            ]));
            die;
        }

        $variables = $this->getProductSearchVariables();
        $productList = $variables['products'];
        $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
            $this->context->link
        );

        foreach ($productList as $key => $product){
            $productList[$key]['images'] = $retriever->getProductImages($product, $this->context->language);
        }

        $psdata = [
            'description' => $this->category->description,
            'active' => $this->category->active,
            'images' => $this->getImage(
                $this->category,
                $this->category->id_image
            ),
            'label' => $variables['label'],
            //todo: keep only required fields for products
            'products' => $productList,
            'sort_orders' => $variables['sort_orders'],
            'sort_selected' => $variables['sort_selected'],
            'pagination' => $variables['pagination']
            //todo: handle faceted_search
        ];

        $this->ajaxRender(json_encode([
            'code' => 200,
            'success' => true,
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


    public function getListingLabel()
    {
        if (!Validate::isLoadedObject($this->category)) {
            $this->category = new Category(
                (int) Tools::getValue('id_category'),
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