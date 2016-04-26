<?php
namespace app\domain\chat;
use Symfony\Component\HttpFoundation\Response;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JSONResponse
 *
 * @author argenisfd
 */
class JSONResponse extends Response {
    
    public function setContent($content) {
        
        $this->headers->set("Content-Type", 'application/json; charset=utf-8');
        $this->setCharset("utf-8");
        return parent::setContent(json_encode($content));
    }
}
