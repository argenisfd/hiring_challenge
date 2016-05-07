<?php
namespace app\domain\chat;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RedisServiceProvider
 *
 * @author argenisfd
 */
class RedisServiceProvider implements ServiceProviderInterface {
    public function register(Container $pimple)
    {
        $pimple["redis"]=function($pimple)  {
            try{
            $redis = new \Redis();
            $redis->connect($pimple["settings"]->get("REDIS_HOST"),$pimple["settings"]->get("REDIS_PORT") );
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            }catch(\Exception $e){
                
            }
            return $redis;
        };
        
    }
}
