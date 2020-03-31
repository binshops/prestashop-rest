<?php
/**
 * Description: This class bootstraps the main page of the application
 * */

require_once __DIR__ . '/../AbstractRESTController.php';

class BinshopsrestBootstrapModuleFrontController extends AbstractRESTController
{
    protected function processGetRequest()
    {
        $messageCode = 200;
        $mainMenu = Module::getInstanceByName('ps_mainmenu');
        $featuredProducts = Module::getInstanceByName('ps_featuredproducts');
        $banner = Module::getInstanceByName('ps_banner');
        $imagesSlider = Module::getInstanceByName('ps_imageslider');

        $menuItems = $mainMenu->getWidgetVariables(null, []);
        $featuredProductsList = $featuredProducts->getWidgetVariables(null, []);
        $bannerItem = $banner->getWidgetVariables(null, []);
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
        $psdata['banner'] = $bannerItem;
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

}