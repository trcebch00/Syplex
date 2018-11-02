<?php

namespace Syplex\Router;

class Route extends \Syplex\Hookable {
  static public $current = NULL;

  public $cacheable;
  public $uri;
  public $callback;

  /**
   * @param String   $uri      The URI for the route.
   * @param Callable $callback The callable object to be called when handling this route.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(String $uri, $callback) {
    $this->cacheable = true;
    $this->uri = $uri;
    $this->callback = $callback;
  }
}