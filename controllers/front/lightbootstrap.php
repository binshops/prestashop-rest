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
require_once dirname(__FILE__) . '/../../classes/RESTUtils.php';
require_once dirname(__FILE__) . '/../../classes/RESTUtilsLegacy.php';

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

        $id_shop = (int) $this->context->shop->id;

        $psdata = array();
        $psdata['menuItems'] = $menuItems;

        if (version_compare(_PS_VERSION_, '9.0', '<=')) {
            $psdata['currencies'] = RESTUtilsLegacy::getCurrencies($this->context);
        }else{
            $psdata['currencies'] = RESTUtils::getCurrencies($this->context);
        }

        $psdata['languages'] = $this->getLanguages();
        $psdata['logo_url'] = Tools::getHttpHost(true) . _PS_IMG_ .Configuration::get('PS_LOGO', null, null, $id_shop);

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
}
