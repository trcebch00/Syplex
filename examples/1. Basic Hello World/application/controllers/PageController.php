<?php

use Syplex\Controller;

class PageController extends Controller {
  public function home() {
    return $this->view->render("home.html.twig");
  }

  public function greet($name) {
    return $this->view->render("hello.html.twig", [
      "name" => $name
    ]);
  }
}
