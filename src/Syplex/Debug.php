<?php

namespace Syplex;

class Debug {
  static public $instance = NULL;
  static public $pdoCollector = NULL;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Prepare($config=[]) {
    if (is_null(self::$instance)) {
      self::$instance = new \DebugBar\StandardDebugBar;
    }

    if (isset($config["pdo"])) {
      self::$pdoCollector = new \DebugBar\DataCollector\PDO\PDOCollector;
      self::$pdoCollector->addConnection($config["pdo"]);
      self::$instance->addCollector(self::$pdoCollector);
    }

    return self::$instance;
  }

  /**
   * @param Array|String $message
   *
   * @return Array|String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function EscapeMessage($message) {
    if (is_array($message)) {
      foreach ($message as $_ => &$value) {
        if (is_array($value)) {
          $value = self::EscapeMessage($value);
        } else {
          $value = str_replace("\"", "\\\"", $value);
        }
      }
    } else {
      $message = str_replace("\"", "\\\"", $message);
    }

    return $message;
  }

  /**
   * @param Mixed  $message        The data to be logged to the debug bar.
   * @param String $arrayMergeKey  The key in the existing messages array to merge the given message with (if the message is an array.)
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function LogMessage($message, String $arrayMergeKey="params") {
    if (Config::$current->application["debug"]) {
      if (is_null(self::$instance)) {
        self::Prepare();
      }

      if (is_array($message) && is_array($message[$arrayMergeKey])) {
        foreach ($message[$arrayMergeKey] as $index => $value) {
          self::$instance["messages"]->addMessage([
            $index => self::EscapeMessage($value)
          ]);
        }
      } else {
        self::$instance["messages"]->addMessage(self::EscapeMessage($message));
      }
    }
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function LogException($error) {
    if (Config::$current->application["debug"]) {
      if (is_null(self::$instance)) {
        self::Prepare();
      }

      self::$instance["exceptions"]->addException($error);
    }
  }
}