<?php

namespace Syplex\Router;

class Router extends \Syplex\Hookable {
  public $cacheable;
  public $baseURI;
  public $routes;

  /**
   * @param String $baseURI
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(String $baseURI="/") {
    $this->cacheable = true;
    $this->baseURI = $baseURI;
    $this->routes = [
      "HEAD" => [],
      "GET" => [],
      "POST" => [],
      "PUT" => [],
      "DELETE" => [],
      "OPTIONS" => []
    ];
  }

  /**
   * @param Array|String $method
   * @param String       $uri
   * @param Callable     $callback
   *
   * @return
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addRoute($method, $uri, $callback=NULL) {
    if (is_array($method)) {
      foreach ($method as $method) {
        $route = $this->addRoute($method, $uri, $callback);
      }
      return $route;
    } else {
      if (!$callback) {
        if (get_class($uri) === "Syplex\Router\Route") {
          return $this->routes[$method][] = $uri;
        }
      } else {
        return $this->routes[$method][] = new Route($uri, $callback);
      }
    }
  }

  /**
   * @param \Syplex\Router\Request $request The request to be handled.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function handle(Request $request) {
    if (strpos($request->uri, $this->baseURI) !== 0) {
      return false;
    }

    if ($this->baseURI !== "/") {
      if (strpos($requestURI, $this->baseURI) === 0) {
        $requestURI = substr($requestURI, strlen($this->baseURI));
      }
    } else {
      $requestURI = $request->uri;
    }

    $matched = false;

    foreach ($this->routes[$request->method] as $route) {
      if ($this->match($route, $request, $requestURI)) {
        $matched = true;
        break;
      }
    }

    if (isset($route) && !$this->cacheable) {
      $route->cacheable = false;
    }

    if (isset($route) && !$route->cacheable) {
      $request->cacheable = false;
    }

    return $matched ? $route : false;
  }

  /**
   * @param \Syplex\Router\Route   $route
   * @param \Syplex\Router\Request $request
   * @param String                 $requestURI
   *
   * @return Boolean
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function match(Route $route, Request $request, String $requestURI=NULL):bool {
    if (!$requestURI) {
      $requestURI = $request->uri;
    }

    $params = [];
    $expected = explode("/", $route->uri);
    $given = explode("/", $requestURI);

    /*
     * First check if no wildcards have been set and if the lengths do not match because
     * if that is the case then there is no matching route, so immediately return.
     * */

    if (strpos($route->uri, "*") === false && count($expected) !== count($given)) {
      return false;
    }

    foreach ($expected as $index => $value) {
      /*
       * An asterisk means everything that follows will always match.
       * */

      if ($value === "*") {
        break;
      }

      /*
       * Check for a {} param in this part of the URI.
       * */

      $begin = strpos($value, "{");
      $end = strpos($value, "}", $begin);

      if ($begin !== false && $end !== false) {
        // Check if the found {} param has any regex attached to it.
        if (($end-$begin) > 2 && substr_count($value, ":") === 1) {
          // Check if the found regex matches what was given in the URI.
          $pivot = strpos($value, ":", $begin);
          if (preg_match("/^" .  substr($value, $pivot+1, $end-$pivot) . "$/", $given[$index], $regex_param)) {
            $params[$regex_param[0]] = $given[$index];
          } else {
            return false;
          }
        } else {
          // No regex found in param, so it's treated as wildcard and passes.
          $params[substr($value, $begin+1, $end-$begin-1)] = $given[$index];
        }
      } elseif (!isset($given[$index]) || $value !== $given[$index]) {
        // No param found and the text literal doesn't match what was given in the URI.
        return false;
      }
    }

    $request->params = $params;

    return true;
  }
}