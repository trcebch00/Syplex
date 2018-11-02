<?php

namespace Syplex;

class User {
  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function IsRoot($ipaddress=NULL):bool {
    if (isset(Config::$current->application["rootips"])) {
      if (!$ipaddress) {
        $ipaddress = ip2long($_SERVER["REMOTE_ADDR"]);
      } elseif (is_string($ipaddress)) {
        $ipaddress = ip2long($ipaddress);
      }

      foreach (Config::$current->application["rootips"] as $rootip) {
        if ($ipaddress >= $rootip[0] && $ipaddress <= $rootip[1]) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Cookie($name=NULL) {
    if (!$name) {
      return $_COOKIE;
    }

    $args = func_get_args();
    $numArgs = func_num_args();

    if ($numArgs === 1) {
      return Utility::Get($_COOKIE, $name, NULL);
    } elseif ($numArgs === 3 && is_string($args[1]) && is_int($args[2])) {
      setcookie($name, $args[1], $args[2]);
    } elseif ($numArgs === 2 && is_int($args[1])) {
      setcookie($name, Utility::get($_COOKIE, $name, ""), $args[1]);
    } elseif ($numArgs === 2 && ($args[1] === false || $args[1] === NULL)) {
      setcookie($name, "", time()-1);
    } elseif ($numArgs === 2 && is_array($args[1])) {
      setcookie($name,
        $args[1]["value"] ?? Utility::Get($_COOKIE, $name, ""),
        $args[1]["expire"] ?? 0,
        $args[1]["path"] ?? "/",
        $args[1]["domain"] ?? "",
        $args[1]["secure"] ?? false,
        $args[1]["httponly"] ?? false
      );
    }
  }
  
  /**
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function SessionID():String {
    $sessionid = session_id();

    if (empty($sessionid)) {
      session_start();
      $sessionid = session_id();
    }

    return $sessionid;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Session($name=NULL) {
    $sessionid = session_id();

    if (empty($sessionid)) {
      session_start();
    }

    if (!$name) {
      return $_SESSION;
    }

    $args = func_get_args();
    $numArgs = func_num_args();

    if ($numArgs === 1) {
      return $_SESSION[$name] ?? NULL;
    } elseif ($args[1] === NULL) {
      unset($_SESSION[$name]);
    } else {
      return $_SESSION[$name] = $args[1];
    }
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function ClearSession() {
    $sessionid = session_id();

    if (empty($sessionid)) {
      session_start();
    }

    session_unset();
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function DestroySession() {
    $sessionid = session_id();

    if (empty($sessionid)) {
      session_start();
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), "", time() - 1209600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
      );
    }

    session_destroy();
  }
}