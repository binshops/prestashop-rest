<?php
/**
 * Description: This class bootstraps the main page of the application
 * */

require_once __DIR__ . '/../AbstractRESTController.php';

class BinshopsrestBootstrapModuleFrontController extends AbstractRESTController
{
    protected $banner;
    protected function processGetRequest()
    {
        $messageCode = 200;
        $mainMenu = Module::getInstanceByName('ps_mainmenu');
        $featuredProducts = Module::getInstanceByName('ps_featuredproducts');
        $this->banner = Module::getInstanceByName('ps_banner');
        $imagesSlider = Module::getInstanceByName('ps_imageslider');

        $menuItems = $mainMenu->getWidgetVariables(null, []);
        $featuredProductsList = $featuredProducts->getWidgetVariables(null, []);
        $slidesList = $imagesSlider->getWidgetVariables(null, []);
        $menuItems = $menuItems['children'];
        $retriever = new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever(
            $this->context->link
        );
        foreach ($menuItems as $key => $item){
            $category = new Category(
                substr($item['page_identifier'], -1),
                $this->context->language->id
            );
            $menuItems[$key]['images'] = $retriever->getImage(
                    $category,
                    $category->id_image
                );
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

    protected function processPostRequest(){
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

    protected function getBanner(){
        $imgname = Configuration::get('BANNER_IMG', $this->context->language->id);
        $image_url = "";

        if ($imgname && file_exists(_PS_MODULE_DIR_.$this->banner->name.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$imgname)) {
            $image_url = $this->context->link->protocol_content . Tools::getMediaServer($imgname) . __PS_BASE_URI__ . 'modules/'. $this->banner->name . '/' . 'img/' . $imgname;
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
        if (substr($link, 0, 7) !== "http://" && substr($link, 0, 8) !== "https://") {
            $link = "http://" . $link;
        }

        return $link;
    }
}