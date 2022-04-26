<?php

trait RESTTrait
{
    protected function processGetRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('GET not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }

    protected function processPostRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('POST not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }

    protected function processPutRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('PUT not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }

    protected function processDeleteRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => $this->trans('DELETE not supported on this path', [], 'Modules.Binshopsrest.Admin')
        ]));
        die;
    }
}
