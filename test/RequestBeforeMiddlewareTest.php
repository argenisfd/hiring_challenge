<?php

use Symfony\Component\HttpFoundation\Request;
use app\domain\chat\middleware\RequestBeforeMiddleware;
use Pimple\Container;
use app\domain\chat\RedisServiceProvider;
use app\domain\chat\SettingsServiceProvider;
use app\domain\chat\exception\ChatException;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RequestBeforeMiddlewareTest
 *
 * @author argenisfd
 */
class RequestBeforeMiddlewareTest extends \PHPUnit_Framework_TestCase{
    
    private $pimple=null;
    private $is_init=false;
    private function loadSettings(){
        $this->pimple= new Container();
        $this->pimple->register(new SettingsServiceProvider());
        $this->pimple->register(new RedisServiceProvider());
    }
    
    protected function setUp() {
        $this->loadSettings();
        parent::setUp();
    }
    
    public function test01NoCookie(){
        $request= Request::create("/", "GET",[], [],[],$_SERVER);
        $middleware= new RequestBeforeMiddleware($this->pimple);
        try{
            $middleware->handle($request);
            $this->assertEquals(true,false, "exception is not throw");
        }  catch (\Exception $e){
            $this->assertInstanceOf('app\domain\chat\exception\ChatException', $e,$e->getMessage());
            $this->assertEquals($e->getMessage(), "Not a valid session.", "Test no cookie message");
            $this->assertEquals($e->getCode(), 403, "Test no status code");
        }
    }
    
    public function test02InvalidCookie(){
        $this->loadSettings();
        $request= Request::create("/", "GET",[], ['app1'=>"hash1"],[],$_SERVER);
        $middleware= new RequestBeforeMiddleware($this->pimple);
        try{
            $middleware->handle($request);
            //$this->assertEquals(true,false, "exception is not throw");
        }  catch (ChatException $e){
            $this->assertInstanceOf('app\domain\chat\exception\ChatException',$e, $e->getMessage());
            $this->assertEquals($e->getMessage(), "Not a valid session.", "Test no cookie message");
            $this->assertEquals(403,$e->getCode());
        }
    }
    public function test03ValidCookie(){
        $this->loadSettings();
        $request= Request::create("/", "GET",[], ['app'=>"hash1"],[],$_SERVER);
        $middleware= new RequestBeforeMiddleware($this->pimple);
        $response=$middleware->handle($request);
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
    }
    
    public function test04FailRedisInvalidSettings(){
        $this->loadSettings();
        $request= Request::create("/", "GET",[], ['app'=>"hash1"],[],$_SERVER);
        $this->pimple["settings"]->set("REDIS_HOST",null);
        $middleware= new RequestBeforeMiddleware($this->pimple);
        try{
        $response=$middleware->handle($request);
        }catch(\Exception $e){
            $this->assertInstanceOf('app\domain\chat\exception\ChatException', $e);
            $this->assertContains("configuration", $e->getMessage());
            $this->assertEquals(500, $e->getCode());
        }
    }
    public function test04FailRedisInvalidConnection(){
        $this->loadSettings();
        $request= Request::create("/", "GET",[], ['app'=>"hash1"],[],$_SERVER);
        $this->pimple["settings"]->set("REDIS_HOST","192.168.0.1");
        $middleware= new RequestBeforeMiddleware($this->pimple);
        try{
            $response=$middleware->handle($request);
        }catch(\Exception $e){
            $this->assertInstanceOf('app\domain\chat\exception\ChatException', $e,$e->getMessage());
            $this->assertContains("connect", $e->getMessage());
            $this->assertEquals(500, $e->getCode());
        }
    }
}
