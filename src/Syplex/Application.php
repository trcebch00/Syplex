<?php

namespace Syplex;

class Application extends Hookable {
  static public $current = NULL;

  public $events;
  public $routers;
  public $hooks;
  
  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct() {
    $this->events = [];
    $this->routers = [];
    $this->hooks = [
      "prepare" => NULL,
      "before" => NULL,
      "after" => NULL
    ];
  }

  /**
   * Adds a new event to the application which can then be triggered using the
   * \Syplex\Application::callEvent function. Typically this will be used for
   * any non-200 HTTP response, such as a 404 error.
   *
   * @param int|String $httpResponseCode The response code that this event is for, typically any non-200 code.
   * @param Callable   $callback         The callable that will be called and have its return value sent to the browser.
   *
   * @return Callable $callback The passed callback value.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addEvent($httpResponseCode, $callback) {
    return $this->events[$httpResponseCode] = $callback;
  }

  /**
   * @param \Syplex\Router\Router $router The router object to add to this applications collection of routers.
   *
   * @return \Syplex\Router\Router The passed $router value.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addRouter(Router\Router $router):Router\Router {
    return $this->routers[] = $router;
  }

  /**
   * Starts the application and handled the passed request, if there is one. If no request
   * is given then the request arguments in the $_SERVER global variable will be used.
   *
   * Will call either \Syplex\Application\runDebug or \Syplex\Application\runProduction
   * depending on which mode is set in the configuration.
   *
   * @param \Syplex\Router\Request $request
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function run(Router\Request $request=NULL) {
    self::$current = $this;

    $this->callHook("prepare");

    if (!$request) {
      $request = new Router\Request($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);
    }

    if (Config::$current->application["debug"]) {
      $this->runDebug($request);
    } else {
      $this->runProduction($request);
    }
  }

  /**
   * Starts the application in debug mode and handled the passed request.
   *
   * @param \Syplex\Router\Request $request
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function runDebug(Router\Request $request) {
    View::AddExtension("Twig_Extension_Debug");
    echo $this->getContent(
      $this->callHook("before", $this->handleRequest($request))
    );
  }

  /**
   * Starts the application in production mode and handled the passed request.
   *
   * @param \Syplex\Router\Request $request
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function runProduction(Router\Request $request) {
    $route = $this->handleRequest($request);

    if ($request->cacheable && $request->method === "GET" && !User::Session("Errors") && !User::Session("Results")) {
      $hash = hash("sha256", "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]$_SERVER[QUERY_STRING]");
    } else {
      $request->cacheable = false;
    }
    
    if (!$request->cacheable || !Cache::IsCached($hash)) {
      $request = $this->callHook("before", $request);
    }

    try {
      if ($request->cacheable) {
        echo Cache::Get($hash, [$this, "getContent"], [$route]);
      } else {
        echo $this->getContent($route);
      }
    } catch (Exception $error) {
      echo $this->callEvent("500");
      error_log($error);
    }
  }

  /**
   * Handles the passed route (i.e. the requested URI) and returns the value associated with it, typically
   * by calling the associated Controller's method. Will trigger a 404 event if no route is passed.
   *
   * @param \Syplex\Router\Route $route
   *
   * @return String The value to be sent to the browser.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function getContent(Router\Route $route=NULL):String {
    if ($route) {
      return $this->handleRoute($route);
    } else {
      return $this->callEvent("404");
    }
  }

  /**
   * @param int|String $httpResponseCode
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function callEvent($httpResponseCode):String {
    http_response_code($httpResponseCode);

    if (isset($this->events[$httpResponseCode])) {
      return call_user_func(
        $this->getController($this->events[$httpResponseCode])
      );
    } else {
      return "";
    }
  }

  /**
   * @param \Syplex\Router\Route $route
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function callRoute(Router\Route $route):String {
    if (is_null(Controller::$current)) {
      $controller = $this->getController($route->callback);
    } else {
      $controller = [Controller::$current, Controller::$callback];
    }

    if (strpos($route->callback, "@") !== false) {
      View::$params["Errors"] = User::Session("Errors");
      View::$params["Results"] = User::Session("Results");

      User::Session("Errors", NULL);
      User::Session("Results", NULL);
    }

    return call_user_func_array($controller, Router\Request::$current->params);
  }

  /**
   * Processes the request by determining if it is valid (i.e. if a route
   * exists that matches the request) and returning the found route.
   *
   * If no route can be found a NULL value will be returned.
   *
   * @param \Syplex\Router\Route $request
   *
   * @return \Syplex\Router\Route
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function handleRequest(Router\Request $request):Router\Route {
    foreach ($this->routers as $router) {
      if ($route = $router->handle($request)) {
        $controller = $this->getController($route->callback);

        Router\Request::$current = $request;

        Controller::$current = $controller[0];
        Controller::$callback = $controller[1];

        $router->callHook("before", $route);
        $route->callHook("prepare");

        return $route;
      }
    }

    return NULL;
  }

  /**
   * @param \Syplex\Router\Route $route
   * 
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function handleRoute(Router\Route $route):String {
    $route->callHook("before");
    $response = $this->callHook("after", $this->callRoute($route));
    $route->callHook("after");

    return $response;
  }
}