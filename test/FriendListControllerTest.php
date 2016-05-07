<?php
use app\domain\chat\FriendListController;
use app\domain\chat\SettingsServiceProvider;
use app\domain\chat\RedisServiceProvider;
use Pimple\Container;
use app\domain\chat\JSONResponse;
use Symfony\Component\HttpFoundation\Request;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FriendListControllerTest
 *
 * @author argenisfd
 */
class FriendListControllerTest extends \PHPUnit_Framework_TestCase {
    private $pimple;
    private function loadSettings(){
        $this->pimple= new Container();
        $this->pimple->register(new SettingsServiceProvider());
        $this->pimple->register(new RedisServiceProvider());
        $this->pimple["settings"]->set("FRIENDS_CACHE_PREFIX_KEY","chat:friends:{:userId}");
        $this->pimple["settings"]->set("ONLINE_CACHE_PREFIX_KEY","chat:online:{:userId}");
    }
    
    public function testInvalidCookie(){
        $this->loadSettings();
        $controller= new FriendListController($this->pimple);
        $request= Request::create("/", "GET",[], ['app'=>"hash11"],[],$_SERVER);
        $response=new JSONResponse();
        $response2=$controller->getFriendlistAction($request, $response);
        $this->assertEquals($response2, $response);
        $this->assertEquals($response2->getStatusCode(), 404);
    }
    public function testValidCookie(){
        $this->loadSettings();
        $controller= new FriendListController($this->pimple);
        $request= Request::create("/", "GET",[], ['app'=>"hash"],[],$_SERVER);
        $response=new JSONResponse();
        $response2=$controller->getFriendlistAction($request, $response);
        $this->assertEquals($response2, $response);
        $this->assertEquals($response2->getStatusCode(), 200);
        $this->assertNotEmpty(json_decode($response2->getContent()));
    }
}
