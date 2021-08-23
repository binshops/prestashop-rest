<?php
/**
 * BINSHOPS
 *
 * @author BINSHOPS
 * @copyright BINSHOPS
 *
 */

require_once dirname(__FILE__) . '/../AbstractRESTController.php';
require_once dirname(__FILE__) . '/../../classes/RESTProductLazyArray.php';

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

/**
 * Description: This class bootstraps the main page of the application
 * */

class BinshopsrestBootstrapModuleFrontController extends AbstractRESTController
{
    protected $banner;

    protected function processGetRequest()
    {
        $messageCode = 200;
        $mainMenu = Module::getInstanceByName('ps_mainmenu');
        $this->banner = Module::getInstanceByName('ps_banner');
        $imagesSlider = Module::getInstanceByName('ps_imageslider');

        $menuItems = $mainMenu->getWidgetVariables(null, []);
        $featuredProductsList = $this->getFeaturedProducts();
        $slidesList = $imagesSlider->getWidgetVariables(null, []);
        $menuItems = $menuItems['children'];

        if ((boolean)Tools::getValue('menu_with_images', 0)){
            foreach ($menuItems as $key => $item) {
                $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
                    $this->context->link
                );
                $category = new Category(
                    Tools::substr($item['page_identifier'], -1),
                    $this->context->language->id
                );
                $menuItems[$key]['images'] = $retriever->getImage(
                    $category,
                    $category->id_image
                );
            }
        }

        $psdata = array();
        $psdata['menuItems'] = $menuItems;
        $psdata['featuredProductsList'] = $featuredProductsList['products'];
        $psdata['numberOfFeaturedProd'] = 10;
        $psdata['banner'] = $this->getBanner();
        $psdata['slides'] = $slidesList['homeslider']['slides'];

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

    protected function getBanner()
    {
        $imgname = Configuration::get('BANNER_IMG', $this->context->language->id);
        $image_url = "";

        if ($imgname && file_exists(_PS_MODULE_DIR_ . $this->banner->name . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $imgname)) {
            $image_url = $this->context->link->protocol_content . Tools::getMediaServer($imgname) . __PS_BASE_URI__ . 'modules/' . $this->banner->name . '/' . 'img/' . $imgname;
        }

        $banner_link = Configuration::get('BANNER_LINK', $this->context->language->id);
        if (!$banner_link) {
            $banner_link = $this->context->link->getPageLink('index');
        }

        return array(
            'image_url' => $image_url,
            'banner_link' => $this->updateUrl($banner_link),
            'banner_desc' => Configuration::get('BANNER_DESC', $this->context->language->id)
        );
    }

    private function updateUrl($link)
    {
        if (Tools::substr($link, 0, 7) !== "http://" && Tools::substr($link, 0, 8) !== "https://") {
            $link = "http://" . $link;
        }

        return $link;
    }

    public function getFeaturedProducts(){
        $category = new Category((int) Configuration::get('HOME_FEATURED_CAT'));

        $searchProvider = new CategoryProductSearchProvider(
            $this->context->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();
        $nProducts = Configuration::get('HOME_FEATURED_NBR');
        if ($nProducts < 0) {
            $nProducts = 12;
        }

        $query
            ->setResultsPerPage($nProducts)
            ->setPage(1)
        ;

        if (Configuration::get('HOME_FEATURED_RANDOMIZE')) {
            $query->setSortOrder(SortOrder::random());
        } else {
            $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
        }

        $result = $searchProvider->runQuery(
            $context,
            $query
        );

        $products_for_template = [];
        $settings = $this->getProductPresentationSettings();
        $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
            $this->context->link
        );

        foreach ($result->getProducts() as $rawProduct) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($rawProduct);
            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
                $retriever,
                $this->context->getTranslator()
            );

            $products_for_template[] = $lazy_product->getProduct();
        }

        return array(
            'products' => $products_for_template,
            'allProductsLink' => Context::getContext()->link->getCategoryLink($this->getConfigFieldsValues()['HOME_FEATURED_CAT']),
        );
    }

    public function getConfigFieldsValues()
    {
        return array(
            'HOME_FEATURED_NBR' => Tools::getValue('HOME_FEATURED_NBR', (int) Configuration::get('HOME_FEATURED_NBR')),
            'HOME_FEATURED_CAT' => Tools::getValue('HOME_FEATURED_CAT', (int) Configuration::get('HOME_FEATURED_CAT')),
            'HOME_FEATURED_RANDOMIZE' => Tools::getValue('HOME_FEATURED_RANDOMIZE', (bool) Configuration::get('HOME_FEATURED_RANDOMIZE')),
        );
    }

    private function getFactory()
    {
        return new ProductPresenterFactory($this->context, new TaxConfiguration());
    }

    protected function getProductPresentationSettings()
    {
        return $this->getFactory()->getPresentationSettings();
    }
}
