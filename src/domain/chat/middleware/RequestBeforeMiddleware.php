<?php

namespace app\domain\chat\middleware;

use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use app\domain\chat\JSONResponse;
use app\domain\chat\exception\ChatException;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RequestBeforeMidleware
 *
 * @author argenisfd
 */
class RequestBeforeMiddleware {

    private $container = null;

    public function __construct(Container $pimple) {
        $this->container = $pimple;
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \app\domain\chat\JSONResponse
     * @throws ChatException
     */

    public function handle(Request $request) {
        
        $globalResponse = new JSONResponse();
        $settings=$this->container["settings"];
        /**
         * Check configuration
         */
        if (empty($settings->get("REDIS_HOST", null)) || empty($settings->get("REDIS_PORT", null)) || empty($settings->get("ALLOWED_DOMAINS", array()))) {
            throw new ChatException("Server error, invalid configuration.", 500);
        }

        /**
         * CORS check
         */
        $httpOrigin = $request->headers->get("http_origin", null);
        if ($settings->get("ALLOW_BLANK_REFERRER", false) || in_array($httpOrigin, $settings->get("ALLOWED_DOMAINS", array()))) {
            $globalResponse->headers->set('Access-Control-Allow-Credentials', 'true');
            if ($httpOrigin) {
                $globalResponse->headers->set('Access-Control-Allow-Origin', $httpOrigin);
            }
        } else {
            throw new ChatException("Not a valid origin.", 403);
        }

        /**
         * No cookie, no session ID.
         */
        if (empty($request->cookies->get('app'))) {
            throw new ChatException("Not a valid session.", 403);
        }
        if (!$this->container['redis']->isConnected()) {
            throw new ChatException("Server error, can\'t connect.", 500);
        }
        return $globalResponse;
    }

}
