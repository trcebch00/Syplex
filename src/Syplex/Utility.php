<?php

namespace Syplex;

class Utility {
  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function reCAPTCHAValid($params=[]) {
    if (!isset($params["g-recaptcha-response"])) {
      return false;
    }

    $handle = curl_init();

    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 3);
    curl_setopt($handle, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($handle, CURLOPT_POST, 3);
    curl_setopt($handle, CURLOPT_POSTFIELDS, "secret={$params["reCAPTCHASecretKey"]}&response={$params["g-recaptcha-response"]}&remoteip=$_SERVER[REMOTE_ADDR]");

    $response = json_decode(curl_exec($handle), true);

    curl_close($handle);

    return !!$response["success"];
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function SQLInjectionAttempt($payload) {
    return false;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function BanIPAddress(String $reason=NULL, String $ipaddress=NULL) {
    global $paths;

    $reason = $reason ?? "None given.";
    $ipaddress = $ipaddress ?? $_SERVER["REMOTE_ADDR"];

    $lines = explode(PHP_EOL, file_get_contents("$paths[root]/.htaccess"));
    $length = count($lines);
    $begin = NULL;
    $end = NULL;

    for ($i = 0; $i < $length; $i++) {
      if (!$begin) {
        if (strpos($lines[$i], "BEGIN BANS") !== false) {
          $begin = $i;
        }
      } elseif (!$end && strpos($lines[$i], "END BANS") !== false) {
        $end = $i;
        break;
      }
    }

    file_put_contents("$paths[root]/.htaccess",
      implode(PHP_EOL,
        array_merge(
          array_slice($lines, 0, $begin + 1),
          [
            "SetEnvIf Remote_Addr \"$ipaddress\" banned",
            "  # Reason: $reason",
            "  # Added: " . self::DateFormat("now", "M/d/Y g:i A", "UTC", "America/Toronto")
          ],
          array_slice($lines, $end)
        )
      )
    );
  }

  /**
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function BrowserName():String {
    return (new \Browser)->getBrowser();
  }

  /**
   * @param String $filename
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function RemoveFileExtension(String $filename):String {
    return pathinfo($filename, PATHINFO_FILENAME);
  }

  /**
   * @param String|int $when
   * @param String     $format
   * @param String     $fromTimezone
   * @param String     $toTimezone
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function DateFormat($when, String $format, String $fromTimezone=NULL, String $toTimezone=NULL):String {
    if (!$fromTimezone) {
      $fromTimezone = new \DateTimeZone("UTC");
    } else {
      $fromTimezone = new \DateTimeZone($fromTimezone);
    }

    if ($when) {
      if (is_numeric($when)) {
        $date = new \DateTime("now", $fromTimezone);
        $date->setTimestamp((int)$when);
      } else {
        $date = new \DateTime($when, $fromTimezone);
      }

      if ($toTimezone) {
        $date->setTimezone(new \DateTimeZone($toTimezone));
      }

      return $date->format($format);
    } else {
      return NULL;
    }
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function CurrencyToInt($currency):int {
    $currency = trim((string)$currency);
    $length = strlen($currency);

    if (!$length) {
      return NULL;
    }

    $decimalPosition = strpos($currency, ".");

    if ($decimalPosition === false) {
      $currency = "$currency00";
    } elseif ($decimalPosition === ($length-2) && is_numeric($currency[$length-1])) {
      $currency = "$currency0";
    }

    return (int)filter_var($currency, FILTER_SANITIZE_NUMBER_FLOAT);
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Random($min, $max):int {
    $max++;

    if ($min === $max || $min > $max) {
      throw new \Exception("Minimum value must be less than maximum value.");
    }

    $range = $max-$min;
    $log = log($range, 2);
    $bytes = (int)($log/8)+1;
    $bits = (int)$log+1;
    $filter = (int)(1<<$bits)-1;

    do {
      $random = $filter & hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
    } while ($random >= $range);

    return $min + $random;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Generate($length, $asciiCharacters=[[48,57],[65,90],[97,122]]):String {
    $result = "";

    if (is_array($asciiCharacters)) {
      $end = count($asciiCharacters) - 1;
      while ($length--) {
        list($min,$max) = $asciiCharacters[self::Random(0,$end)];
        $result .= chr(self::Random($min,$max));
      }
    } else {
      $end = strlen($asciiCharacters) - 1;
      while ($length--) {
        $result .= $asciiCharacters[self::Random(0,$end)];
      }
    }

    return $result;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function UniqueId($length, $callback) {
    $ascii = [[48,57], [65,90], [97,122]];
    $id = self::Generate($length, $ascii);

    if (call_user_func($callback, $id)) {
      return $id;
    } else {
      return self::UniqueId($length, $callback);
    }
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function IsCrawler($useragent=NULL) {
    if (!$useragent && !$useragent = $_SERVER["HTTP_USER_AGENT"] ?? NULL) {
      return false;
    }

    return (new \Jaybizzle\CrawlerDetect\CrawlerDetect)->isCrawler($useragent);
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function IsValidRFCEmail(String $email):Bool {
    $validator = new \Egulias\EmailValidator\EmailValidator;
    $rfc = new \Egulias\EmailValidator\Validation\RFCValidation;

    return $validator->isValid($email, $rfc);
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Get($haystack, $needle, $default=NULL) {
    if (is_array($haystack)) {
      return array_key_exists($needle, $haystack) ? $haystack[$needle] : $default;
    } else if (is_object($haystack)) {
      return property_exists($haystack, $needle) ? $haystack->$needle : $default;
    } else {
      throw new \Exception("The passed haystack must be an array or object. ". gettype($haystack) ." given.");
    }
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Defined($haystack, $needle, $default=NULL) {
    $value = self::Get($haystack, $needle, $default);

    if (is_string($value)) {
      if (strlen(trim($value))) {
        return $value;
      } else {
        return $default;
      }
    }

    return $value ? $value : $default;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function CheckPOST(Array $keys, bool $allowEmpty=false, bool $isArray=false, $index=NULL) {
    $missing = [];

    foreach ($keys as $key) {
      if (!$isArray) {
        if (isset($_POST[$key])) {
          $value = trim($_POST[$key]);
          if ($allowEmpty || strlen($value)) {
            continue;
          }
        }
        $missing[] = $key;
      } else if (is_null($index)) {
        if (isset($_POST[$key])) {
          $i = -1;
          foreach ($_POST[$key] as $value) {
            $i++;
            $value = trim($value);
            if (!$allowEmpty && !strlen($value)) {
              $missing[] = [$key, $i];
            }
          }
        } else {
          $missing[] = [$key, NULL];
        }
      } else {
        if (isset($_POST[$key]) && isset($_POST[$key][$index])) {
          $value = trim($_POST[$key][$index]);
          if ($allowEmpty || strlen($value)) {
            continue;
          }
        }
        $missing[] = [$key, $index];
      }
    }

    return $missing;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function StaticURI(String $type, String $uri, bool $useDirectory=true, bool $useSiteRoot=true) {
    global $paths;

    \SiteModel::Make()->get();

    if ($useSiteRoot) {
      $root = \SiteModel::$current["Meta"]["Root"] ?? "";
    } else {
      $root = "";
    }

    if ($useDirectory) {
      if (!$dir = \SiteModel::$current["Directory"]) {
        $dir = "/";
      } else {
        $dir .= "/";
      }
    }

    if ($useDirectory) {
      return "$root/static/$type/$dir$uri?" . (file_exists("$paths[static]/$type/$dir$uri") ? filemtime("$paths[static]/$type/$dir$uri") : "");
    } else {
      return "$root/static/$type/$uri?" . (file_exists("$paths[static]/$type/$uri") ? filemtime("$paths[static]/$type/$uri") : "");
    }
  }

  /**
   * @param String                  $directory
   * @param \Leafo\ScssPhp\Compiler $scss
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function CompileSCSSDirectory(String $directory, \Leafo\ScssPhp\Compiler $scss=NULL) {
    if (is_null($scss)) {
      $scss = new \Leafo\ScssPhp\Compiler;
      $scss->registerFunction("static_uri", function($filename) {
        return "url('" . self::StaticURI("img", $filename[0][2][0]) . "')";
      });
    }

    $children = glob("$directory/**", GLOB_NOSORT);

    if (!empty($children)) {
      foreach ($children as $child) {
        if (is_dir($child)) {
          self::CompileSCSSDirectory($child, $scss);
        } else {
          self::CompileSCSSFile($child, $scss);
        }
      }
    }
  }

  /**
   * @param String                  $directory
   * @param \Leafo\ScssPhp\Compiler $scss
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function CompileSCSSFile(String $file, \Leafo\ScssPhp\Compiler $scss) {
    $css_file = str_replace(["/scss/", ".scss"], ["/css/", ".css"], $file);

    if (!file_exists($css_file) || filemtime($file) > filemtime($css_file)) {
      file_put_contents($css_file, $scss->compile(file_get_contents($file)));
    }
  }

  /**
   * @param String $directory
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function MinifyCSSDirectory(String $directory) {
    $children = glob("$directory/**", GLOB_NOSORT);

    if (!empty($children)) {
      foreach ($children as $child) {
        if (is_dir($child)) {
          self::MinifyCSSDirectory($child);
        } else {
          self::MinifyCSSFile($child);
        }
      }
    }
  }

  /**
   * @param String $filepath
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function MinifyCSSFile(String $filepath) {
    if (!preg_match("/\.min\.css$/", $filepath)) {
      $min_file = str_replace(".css", ".min.css", $filepath);
      if (!file_exists($min_file) || filemtime($filepath) > filemtime($min_file)) {
        $minifier = new \MatthiasMullie\Minify\CSS($filepath);
        $minifier->minify($min_file);
      }
    }
  }

  /**
   * @param String $directory
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function MinifyJSDirectory(String $directory) {
    $children = glob("$directory/**", GLOB_NOSORT);

    if (!empty($children)) {
      foreach ($children as $child) {
        if (is_dir($child)) {
          self::MinifyJSDirectory($child);
        } else {
          self::MinifyJSFile($child);
        }
      }
    }
  }

  /**
   * @param String $filepath
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function MinifyJSFile(String $filepath) {
    if (!preg_match("/\.min\.js$/", $filepath)) {
      $min_file = str_replace(".js", ".min.js", $filepath);
      if (!file_exists($min_file) || filemtime($filepath) > filemtime($min_file)) {
        $minifier = new \MatthiasMullie\Minify\JS($filepath);
        $minifier->minify($min_file);
      }
    }
  }

  /**
   * Formats strings based on given arguments in a similar manner to
   * Python's String.format() function.
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Format():String {
    $args = func_get_args();
    $result = array_shift($args);
    $replacementsMade = 0;

    while (($begin = strpos($result, "{")) !== false) {
      if (($end = strpos($result, "}", $begin)) === false) {
        break;
      }

      $query = substr($result, $begin + 1, $end - $begin - 1);
      $replacement = "";

      if (!strlen($query)) {
        $replacement = $args[$replacementsMade];
      } elseif (is_numeric($query)) {
        $replacement = $args[(int)$query];
      } else {
        foreach ($args as $_ => $value) {
          if (is_array($value) && array_key_exists($query, $value)) {
            $replacement = $value[$query];
          } elseif (is_object($value) && property_exists($query, $value)) {
            $replacement = $value->{$query};
          }
        }
      }

      $result = substr($result, 0, $begin) . $replacement . substr($result, $end + 1);
      $replacementsMade++;
    }

    return $result;
  }
}