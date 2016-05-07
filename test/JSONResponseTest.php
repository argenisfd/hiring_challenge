<?php

use app\domain\chat\JSONResponse;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JSONResponseTest
 *
 * @author argenisfd
 */
class JSONResponseTest extends \PHPUnit_Framework_TestCase{
    
    public function testJsonResponseArray(){
        $data=["esto","es","una","prueba"];
        $response= new JSONResponse($data);
        $this->assertEquals($response->getContent() , json_encode($data));
        array_push($data, "un valor más");
        $this->assertNotEquals($response->getContent() , json_encode($data));
        $response->setContent($data);
        $this->assertEquals($response->getContent() , json_encode($data));
        
        
    }
    
    public function testJsonResponseArrayAssociative(){
        $data=["foo"=>"var"];
        $response= new JSONResponse($data);
        $this->assertEquals($response->getContent() , json_encode($data));
    }
    
}
