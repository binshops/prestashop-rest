<?php

require_once __DIR__ . '/../AbstractProductListingRESTController.php';
define('PRICE_REDUCTION_TYPE_PERCENT' , 'percentage');

use PrestaShop\PrestaShop\Adapter\Search\SearchProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

/**
 * This REST endpoint gets details of a product
 *
 * This module can be used to search through products
 */
class BinshopsrestProductsearchModuleFrontController extends AbstractProductListingRESTController
{
    protected $search_string;
    protected $search_tag;

    protected function processGetRequest(){
        $this->search_string = Tools::getValue('s');
        if (!$this->search_string && !Tools::getValue('q')) {
            $this->ajaxRender(json_encode([
                'code'=> 301,
                'success' => false,
                'message' => 'query string is not specified'
            ]));
            die;
        }

        $this->search_tag = Tools::getValue('tag');

        $variables = $this->getProductSearchVariables();
        $productList = $variables['products'];
        $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
            $this->context->link
        );

        foreach ($productList as $key => $product){
            $productList[$key]['images'] = $retriever->getProductImages($product, $this->context->language);
        }

        $facets = array();
        foreach ($variables['facets']['filters']->getFacets() as $facet){
            array_push($facets, $facet->toArray());
        }

        $psdata = [
            'label' => $variables['label'],
            'products' => $productList,
            'sort_orders' => $variables['sort_orders'],
            'sort_selected' => $variables['sort_selected'],
            'pagination' => $variables['pagination'],
            'facets' => $facets,
            'active_filter' => $variables['facets']['activeFilters']
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
        return $this->getTranslator()->trans('Search results', array(), 'Shop.Theme.Catalog');
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
            ->setSortOrder(new SortOrder('product', 'position', 'desc'))
            ->setSearchString($this->search_string)
            ->setSearchTag($this->search_tag);

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
        return new SearchProductSearchProvider(
            $this->getTranslator()
        );
    }
}