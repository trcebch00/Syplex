<?php

use Syplex\Controller;

class ErrorController extends Controller {
  public function error404() {
    return $this->view->render("404.html.twig");
  }
}