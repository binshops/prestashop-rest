<?php

trait RESTTrait
{
    protected function processGetRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'GET not supported on this path'
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

    protected function processPutRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'PUT not supported on this path'
        ]));
        die;
    }

    protected function processDeleteRequest(){
        $this->ajaxRender(json_encode([
            'success' => true,
            'message' => 'DELETE not supported on this path'
        ]));
        die;
    }
}
