<?php

use Syplex\Application;
use Syplex\Controller;
use Syplex\Router\Router;

$app = new Application;
$app->addEvent("404", "ErrorController@error404");

$router = new Router;
$router->addRoute("GET", "/", "PageController@index");
$router->addRoute("GET", "/hello/{name}", "PageController@greet");

$app->addRouter($router);
$app->run();