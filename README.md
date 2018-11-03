# Syplex
...

# Example
```php
<?php

use Syplex\Application;
use Syplex\Controller;
use Syplex\Router\Router;

class PageController extends Controller {
  public function home() {
    return "Welcome.";
  }
  
  public function greet($name) {
    return "Hello, $name.";
  }
}

class ErrorController extends Controller {
  public function error404() {
    return "404 Not Found";
  }
}

$app = new Application;
$app->addEvent("404", "ErrorController@error404");

$router = new Router;
$router->addRoute("GET", "/", "PageController@index");
$router->addRoute("GET", "/hello/{name}", "PageController@greet");

$app->addRouter($router);
$app->run();
```
