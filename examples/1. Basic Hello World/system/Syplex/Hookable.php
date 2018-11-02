<?php

namespace Syplex;

class Hookable {
  public $hooks;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct() {
    $this->hooks = [
      "prepare" => NULL,
      "before" => NULL,
      "after" => NULL
    ];
  }

  /**
   * @param String   $type
   * @param Callable $callback
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addHook(String $type, $callback) {
    $this->hooks[$type] = $callback;
  }

  /**
   * @param String $type     The type of hook to call (i.e. prepare, before, or after.)
   * @param Mixed  $argument The data to be passed to the hook's callback.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function callHook(String $type, $argument=NULL) {
    if (isset($this->hooks[$type])) {
      $argument = call_user_func(
        $this->getController($this->hooks[$type]), $argument
      );
    }

    return $argument;
  }

  /**
   * @param Callable $callback
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function getController($callback) {
    if (preg_match("/(.*?)@(.*?)$/", $callback, $definition)) {
      return [new $definition[1], $definition[2]];
    } else {
      return $callback;
    }
  }
}