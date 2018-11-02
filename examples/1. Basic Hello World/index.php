<?php

ini_set("error_reporting", E_ALL);
ini_set("display_errors", "1");
ini_set("display_startup_errors", "0");

$paths = require_once(__DIR__."/bootstrap/paths.php");

require_once("$paths[root]/vendor/autoload.php");

spl_autoload_register(function($name) {
  global $paths;

  $namespace = str_replace("\\", "/", __NAMESPACE__) . "/";
  $class = str_replace("\\", "/", $name);

  if (strpos($class, "Syplex") !== false) {
    require_once("$paths[root]/system/$namespace$class.php");
  } else {
    $buffer = "";
    
    for ($i = strlen($class)-1; $i !== -1; $i--) {
      $buffer .= $class[$i];
      if (ord($class[$i]) > 64 && ord($class[$i]) < 91) {
        break;
      }
    }

    $type = strrev(strtolower($buffer)) . "s";

    if (file_exists("$paths[application]/$type/$class.php")) {
      require_once("$paths[application]/$type/$class.php");
    }
  }
});

Syplex\Config::Load("/config");

if (Syplex\Config::$current->application["debug"]) {
  ini_set("display_errors", "1");
  ini_set("display_startup_errors", "1");
  
  $whoops = new Whoops\Run;

  if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest") {
    $whoops->pushHandler(new Whoops\Handler\JsonResponseHandler);
  } else {
    $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
  }

  $whoops->register();

  if (PHP_VERSION[0] !== "7") {
    throw new Exception("PHP 7 is required. PHP " . PHP_VERSION . " is currently being used.", 1);
  }
} else {
  if (isset($_SERVER["HTTP_ACCEPT_ENCODING"]) && strpos($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip") !== false) {
    ob_start("ob_gzhandler");
  } else {
    ob_start();
  }

  $__syplex_encountered_error = false;

  register_shutdown_function(function() {
    global $__syplex_encountered_error;
    if ($__syplex_encountered_error) {
      ob_end_clean();
      echo (new Syplex\View)->render("errors/500.html.twig");
    } else {
      ob_end_flush();
    }
  });

  set_error_handler(function() {
    global $__syplex_encountered_error;
    $__syplex_encountered_error = true;
  });
}

if (isset(Syplex\Config::$current->application["rootips"])) {
  $rootips = [];

  foreach (Syplex\Config::$current->application["rootips"] as $rootip) {
    if (strpos($rootip,"*") === false) {
      $min = $max = ip2long($rootip);
    } else {
      $min = ip2long(str_replace("*", "0", $rootip));
      $max = ip2long(str_replace("*", "255", $rootip));
    }

    $rootips[] = [$min, $max];
  }

  Syplex\Config::$current->application["rootips"] = $rootips;
}

foreach (glob("$paths[root]/lib/*", GLOB_NOSORT | GLOB_ONLYDIR) as $directory) {
  require_once("$directory/autoload.php");
}

require_once("$paths[application]/main.php");