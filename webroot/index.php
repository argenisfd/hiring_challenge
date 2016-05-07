<?php

/**
 * Load composer libraries
 */
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use app\domain\chat\JSONResponse;
use app\domain\chat\RedisServiceProvider;
use app\domain\chat\SettingsServiceProvider;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use app\domain\chat\exception\ChatException;
use Pimple\Container;
use app\domain\chat\middleware\RequestBeforeMiddleware;


/**
 * Load Service Container
 */
$pimple = new Container();


$request = Request::createFromGlobals();
$pimple->register(new SettingsServiceProvider());
$pimple->register(new RedisServiceProvider());

$pimple["settings"]->set("FRIENDS_CACHE_PREFIX_KEY","chat:friends:{:userId}");
$pimple["settings"]->set("ONLINE_CACHE_PREFIX_KEY","chat:online:{:userId}");


$request->attributes->set("_controller", function(Request $request, JSONResponse $response) use ($pimple) {
    $controller = new app\domain\chat\FriendListController($pimple);
    return $controller->getFriendlistAction($request, $response);
});

$dispatcher = new EventDispatcher();
$dispatcher->addListener(KernelEvents::REQUEST, function(GetResponseEvent $event) use ($pimple) {
    $before= new RequestBeforeMiddleware($pimple);
    $response=$before->handle($event->getRequest());
    $event->getRequest()->attributes->set("response",$response);
    
});

$resolver = new ControllerResolver();
$kernel = new HttpKernel($dispatcher, $resolver);
try {
    $response = $kernel->handle($request);
    $response->send();
}catch(ChatException $e){
    $response= new JSONResponse(['error' => true, 'message' =>$e->getMessage()],$e->getCode());
    $response->send();
} catch (Exception $e) {
    $response = new JSONResponse(['error' => true, 'message' => 'Unknown exception. ' . $e->getMessage()], 500);
    $response->send();
}
$kernel->terminate($request, $response);
