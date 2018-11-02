<?php

namespace Syplex;

class View {
  static public $params = [];
  static public $extensions = [];

  public $depend;
  public $loader;
  public $environment;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function AddExtension($name) {
    self::$extensions[] = $name;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(Array $config=[]) {
    global $paths;

    if (!$config) {
      $config = [
        "cache" => "$paths[root]/cache/twig",
        "debug" => Config::$current->application["debug"],
        "auto_reload" => true
      ];
    }

    $this->depend = new Depend;
    $this->loader = new \Twig_Loader_Filesystem("$paths[application]/templates/");
    $this->environment = new \Twig_Environment($this->loader, $config);

    foreach (self::$extensions as $extension) {
      $this->environment->addExtension(new $extension);
    }
  }

  /**
   * Renders a Twig template and returns said rendered HTML.
   *
   * @param String $template The filepath to the Twig template.
   * @param Array  $params   The parameters to pass to the Twig template.
   *
   * @return String The HTML rendered by Twig.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function render(String $template, Array $params=[]):String {
    $params = array_merge(
      self::$params, $params, ["repo" => $this->depend->getAll()]
    );

    if (Config::$current->application["debug"]) {
      Debug::LogMessage(["params" => $params]);
    }

    return $this->environment->render($template, $params);
  }
}