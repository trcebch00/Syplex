<?php

namespace Syplex;

class Controller {
  static public $current = NULL;
  static public $callback = NULL;

  public $view;
  public $model;
  public $validator;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct() {
    $thisClass = get_class($this);

    $view_name = str_replace("Controller", "View", $thisClass);
    $model_name = str_replace("Controller", "Model", $thisClass);
    $validator_name = str_replace("Controller", "Validator", $thisClass);

    if (class_exists($view_name)) {
      $this->view = new $view_name;
    } else {
      $this->view = new View;
    }

    if (class_exists($model_name)) {
      $this->model = new $model_name;
    } else {
      $this->model = NULL;
    }

    if (class_exists($validator_name)) {
      $this->validator = new $validator_name;
    } else {
      $this->validator = NULL;
    }
  }

  /**
   * Renders a Twig template and returns said rendered HTML. This function is
   * just a shortcut function to \Syplex\View::render().
   *
   * @param String $template The filepath to the Twig template.
   * @param Array  $params   The parameters to pass to the Twig template.
   *
   * @return String The HTML rendered by Twig.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function render(String $template, Array $params=[]):String {
    return $this->view->render($template, $params);
  }
}