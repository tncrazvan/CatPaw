<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace com\github\tncrazvan\catpaw\websocket;

/**
 * Description of WebSocketResponse
 *
 * @author Administrator
 */
class WebSocketResponse {
    //put your code here
    public $data;
    private int $chunksLength;
    public function __construct(&$data,int $chunksLength=1024) {
        $this->data = (string) $data;
        $this->chunksLength = $chunksLength;
    }
}
