<?php

namespace Syplex;

class Model {
  public $db;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Make() {
    $class = get_called_class();
    return new $class;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct() {
    $this->db = new Database;
  }
}