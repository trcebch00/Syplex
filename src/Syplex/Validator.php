<?php

namespace Syplex;

class Validator extends Hookable {
  public $name;
  public $checkers;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct() {
    $thisClass = get_class($this);

    $this->name = str_replace("Validator", "", $thisClass);
    $this->checkers = [];

    foreach (get_object_vars($this) as $key => $value) {
      $class = str_replace("Validator", "{$key}Validator", $thisClass);
      if (class_exists($class)) {
        $this->{$key} = new $class;
      }
    }

    $this->prepare();
  }

  /**
   * @param Callable $callback
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addCheck($callback) {
    $this->checkers[] = $callback;
  }

  /**
   * @return Boolean
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function valid():bool {
    foreach ($this->checkers as $check) {
      if ($error = call_user_func($check)) {
        $error->name = $this->name;
        $error->save();
        return false;
      }
    }

    return true;
  }

  /**
   * @param String $message
   * @param Array  $fields
   * @param Array  $post
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function triggerError(String $message, Array $fields=NULL, Array $post=NULL) {
    $error = new Error($message, $fields, $post);
    $error->name = $this->name;
    $error->save();
  }

  /**
   * @param String $message
   * @param Array  $fields
   * @param Array  $post
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function triggerResult(String $message, Array $fields=NULL, Array $post=NULL) {
    $result = new Result($message, $fields, $post);
    $result->name = $this->name;
    $result->save();
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function prepare() {}
}