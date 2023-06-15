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
use PrestaShop\PrestaShop\Adapter\ObjectPresenter;

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

        $restMenu = new RESTMainMenu();
        $menuItems = $restMenu->renderMenu($this->context, $mainMenu);
        $featuredProductsList = $this->getFeaturedProducts();
        $slidesList = $imagesSlider->getWidgetVariables(null, []);

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
                    $menuItems[$key]['image']['src'] =$this->context->link->getCatImageLink(
                        urlencode($item['slug']),
                        ($category->id_image),
                        'small_default'
                    );
                }else{
                    $menuItems[$key]['images'] = $retriever->getImage(
                        $category,
                        $category->id_image
                    );
                }
            }
        }

        $id_shop = (int) $this->context->shop->id;

        $psdata = array();
        $psdata['menuItems'] = $menuItems;
        $psdata['featuredProductsList'] = $featuredProductsList['products'];
        $psdata['numberOfFeaturedProd'] = 10;
        $psdata['banner'] = $this->getBanner();
        $psdata['slides'] = $slidesList['homeslider']['slides'];
        $psdata['currencies'] = $this->getCurrencies();
        $psdata['languages'] = $this->getLanguages();
        $psdata['logo_url'] = Tools::getHttpHost(true) . _PS_IMG_ .Configuration::get('PS_LOGO', null, null, $id_shop);

        $psdata['contact_info'] = $this->getContactInfo();

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => $messageCode,
            'psdata' => $psdata
        ]));
        die;
    }

    protected function getLanguages(){
        $languages = Language::getLanguages(true, $this->context->shop->id);

        foreach ($languages as &$lang) {
            $lang['name_simple'] = $this->getNameSimple($lang['name']);
        }

        return array(
            'languages' => $languages,
            'current_language' => array(
                'id_lang' => $this->context->language->id,
                'name' => $this->context->language->name,
                'name_simple' => $this->getNameSimple($this->context->language->name),
                'iso_code' => $this->context->language->iso_code
            )
        );
    }

    private function getNameSimple($name)
    {
        return preg_replace('/\s\(.*\)$/', '', $name);
    }

    protected function getCurrencies(){
        $current_currency = null;
        $serializer = new ObjectPresenter();
        $currencies = array_map(
            function ($currency) use ($serializer, &$current_currency) {
                $currencyArray = $serializer->present($currency);

                // serializer doesn't see 'sign' because it is not a regular
                // ObjectModel field.
                $currencyArray['sign'] = $currency->sign;

                $url = $this->context->link->getLanguageLink($this->context->language->id);

                $parsedUrl = parse_url($url);
                $urlParams = [];
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $urlParams);
                }
                $newParams = array_merge(
                    $urlParams,
                    [
                        'SubmitCurrency' => 1,
                        'id_currency' => $currency->id,
                    ]
                );
                $newUrl = sprintf('%s://%s%s%s?%s',
                    $parsedUrl['scheme'],
                    $parsedUrl['host'],
                    isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '',
                    $parsedUrl['path'],
                    http_build_query($newParams)
                );

                $currencyArray['url'] = $newUrl;

                if ($currency->id === $this->context->currency->id) {
                    $currencyArray['current'] = true;
                    $current_currency = $currencyArray;
                } else {
                    $currencyArray['current'] = false;
                }

                return $currencyArray;
            },
            Currency::getCurrencies(true, true)
        );

        return [
            'currencies' => $currencies,
            'current_currency' => $current_currency,
        ];
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

    public function getContactInfo(){
        $address = $this->context->shop->getAddress();

        $contact_infos = [
            'company' => Configuration::get('PS_SHOP_NAME'),
            'address' => [
                'formatted' => AddressFormat::generateAddress($address, [], '<br />'),
                'address1' => $address->address1,
                'address2' => $address->address2,
                'postcode' => $address->postcode,
                'city' => $address->city,
                'state' => (!empty($address->id_state) ? (new State($address->id_state))->name[$this->context->language->id] : null),
                'country' => (new Country($address->id_country))->name[$this->context->language->id],
            ],
            'phone' => Configuration::get('PS_SHOP_PHONE'),
            'fax' => Configuration::get('PS_SHOP_FAX'),
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'details' => Configuration::get('PS_SHOP_DETAILS'),
        ];

        return $contact_infos;
    }
}
