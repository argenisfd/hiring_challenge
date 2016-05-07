<?php
namespace app\domain\chat;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SettingsServiceProvider
 *
 * @author argenisfd
 */
class SettingsServiceProvider implements ServiceProviderInterface {
    public function register(Container $pimple) {
        $pimple["settings"]=function($pimple){
            $fileName=dirname(__FILE__)."/../../../.env";
            if(!is_readable($fileName)){
                throw new \Exception(sprintf("file '%s' is not readable", $fileName ));
            }
            $values=parse_ini_file($fileName);
            $parameters=new ParameterBag($values);
            $parameters->set("ALLOWED_DOMAINS", explode(",", $values['ALLOWED_DOMAINS']));
            return $parameters;
        };
    }

}
