<?php
/**
 * Description: This class bootstraps the main page of the application
 * */

require_once __DIR__ . '/../AbstractRestController.php';

class BinshopsrestBootstrapModuleFrontController extends AbstractRestController
{
    protected function processGetRequest()
    {
        $psdata = "";$messageCode = 200; $success = true;
        $mainMenu = Module::getInstanceByName('ps_mainmenu');
        $featuredProducts = Module::getInstanceByName('ps_featuredproducts');
        $banner = Module::getInstanceByName('ps_banner');
        $imagesSlider = Module::getInstanceByName('ps_imageslider');

        $menuItems = $mainMenu->getWidgetVariables(null, []);
        $featuredProductsList = $featuredProducts->getWidgetVariables(null, []);
        $bannerItem = $banner->getWidgetVariables(null, []);
        $slidesList = $imagesSlider->getWidgetVariables(null, []);

        $psdata = array();
        $psdata['menuItems'] = $menuItems['children'];
        $psdata['featuredProductsList'] = $featuredProductsList['products'];
        $psdata['banner'] = $bannerItem;
        $psdata['slides'] = $slidesList['homeslider']['slides'];

        $this->ajaxRender(json_encode([
            'success' => true,
            'code' => $messageCode,
            'psdata' => $psdata
        ]));
    }

    protected function processPostRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'POST not supported on this path'
        ]));
    }

    protected function processPutRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'put not supported on this path'
        ]));
    }

    protected function processDeleteRequest()
    {
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'delete not supported on this path'
        ]));
    }

}