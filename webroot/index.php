<?php

/**
 * Load composer libraries
 */
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use app\domain\chat\JSONResponse;
use app\domain\chat\RedisServiceProvider;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Pimple\Container;

/**
 * Load Service Container
 */
$pimple = new Container();

/**
 * Some constants
 */
define('FRIENDS_CACHE_PREFIX_KEY', 'chat:friends:{:userId}');
define('ONLINE_CACHE_PREFIX_KEY', 'chat:online:{:userId}');


/**
 * Load .env
 */
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();
/**
 * Load configuration
 */

$request = Request::createFromGlobals();
$pimple["request"] = $request;
$pimple->register(new RedisServiceProvider());
$request->attributes->set('pimple', $pimple);

$request->server->set("ALLOWED_DOMAINS", explode(',', getenv('ALLOWED_DOMAINS')));

$request->attributes->set("_controller", function(Request $request, JSONResponse $response) use ($pimple) {
    $controller = new app\domain\chat\FriendListController();
    return $controller->getFriendlistAction($request, $response, $pimple);
});

$dispatcher = new EventDispatcher();
$dispatcher->addListener(KernelEvents::CONTROLLER, function(FilterControllerEvent $event) use ($pimple) {
    $request = $event->getRequest();
    $globalResponse = new JSONResponse();
    $request->attributes->set("response", $globalResponse);
    /**
     * Check configuration
     */
    if (empty($request->server->get("REDIS_HOST", null)) || empty($request->server->get("REDIS_PORT", null)) || empty($request->server->get("ALLOWED_DOMAINS", array()))) {
        $event->setController(function() {
            return new JSONResponse(['error' => true, 'message' => 'Server error, invalid configuration.'], 500);
        });
    }

    /**
     * CORS check
     */
    $httpOrigin = $event->getRequest()->headers->get("http_origin", null);
    if ($request->server->get("ALLOW_BLANK_REFERRER", false) || in_array($httpOrigin, $request->get("ALLOWED_DOMAINS", array()))) {
        $globalResponse->headers->set('Access-Control-Allow-Credentials', 'true');
        if ($httpOrigin) {
            $globalResponse->headers->set('Access-Control-Allow-Origin', $httpOrigin);
        }
    } else {
        $event->setController(function() {
            return new JSONResponse(['error' => true, 'message' => 'Not a valid origin.'], 403);
        });
    }

    /**
     * No cookie, no session ID.
     */
    if (empty($request->cookies->get('app'))) {
        $event->setController(function() {
            return new JSONResponse(['error' => true, 'message' => 'Not a valid session.'], 403);
        });
    }
    if (!$pimple['redis']->isConnected()) {
        $event->setController(function() {
            return new JSONResponse(['error' => true, 'message' => 'Server error, can\'t connect.'], 500);
        });
    }
});

$resolver = new ControllerResolver();
$kernel = new HttpKernel($dispatcher, $resolver);
try {
    $response = $kernel->handle($request);
    $response->send();
} catch (Exception $e) {
    $errReponse = new JSONResponse(['error' => true, 'message' => 'Unknown exception. ' . $e->getMessage()], 500);
    $errReponse->send();
}
$kernel->terminate($request, $response);
