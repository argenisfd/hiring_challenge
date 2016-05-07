<?php
namespace app\domain\chat;

use Symfony\Component\HttpFoundation\Request;
use app\domain\chat\JSONResponse;
use Pimple\Container;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FriendListController
 *
 * @author argenisfd
 */
class FriendListController {
    /**
     *
     * @var Container 
     */
    
    private $container;
    
    public function __construct(Container $pimple) {
        $this->container=$pimple;
    }
    public function getFriendlistAction(Request $request, JSONResponse $response){
        $redis=$this->container['redis'];
        $settings=$this->container['settings'];
        $session = $redis->get(join(':', ['PHPREDIS_SESSION', $request->cookies->get("app")]));
        if (!empty($session['default']['id'])) {
            $friendsList = $redis->get(str_replace('{:userId}', $session['default']['id'], $settings->get("FRIENDS_CACHE_PREFIX_KEY")));
            if (!$friendsList) {
                // No friends list yet.
                $response->setContent([]);
                $response->setStatusCode(200,'No friends list yet');
                return $response;
            }
        } else {
            $response->setContent(['error' => true, 'message' => 'Friends list not available.']);
            $response->setStatusCode(404,'Friends list not available');
            return $response;
        }
        
        $friendUserIds = $friendsList->getUserIds();

        if (!empty($friendUserIds)) {
            $keys = array_map(function ($userId) use ($settings) {
                return str_replace('{:userId}', $userId, $settings->get("ONLINE_CACHE_PREFIX_KEY"));
            }, $friendUserIds);

            // multi-get for faster operations
            $result = $redis->mget($keys);

            $onlineUsers = array_filter(
                array_combine(
                    $friendUserIds,
                    $result
                )
            );

            if ($onlineUsers) {
                $friendsList->setOnline($onlineUsers);
            }
        }
        $response->setStatusCode(200);
        $response->setContent($friendsList->toArray());
        $response->setPrivate();
        $response->setMaxAge($settings->get("FRIENDLIST_HTTP_CACHE_TIME",0));
        return $response;
    }
}
