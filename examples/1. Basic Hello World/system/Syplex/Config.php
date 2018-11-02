<?php

namespace Syplex;

class Config {
  static public $current = NULL;

  /**
   * @param String $configDir
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Load(String $configDir="/config") {
    self::$current = new Config($configDir);
  }

  /**
   * @param String $configDir
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(String $configDir="/config") {
    global $paths;

    foreach (glob("$paths[root]$configDir/*.php", GLOB_NOSORT) as $filename) {
      $this->{basename($filename,".php")} = require($filename);
    }
  }
}