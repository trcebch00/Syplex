<?php

namespace Syplex;

class Security {
  static public $PasswordHash = NULL;

  /**
   * Determines if the given request, or the current POST request if no
   * data is passed, provided a valid Google reCAPTCHA result (i.e. if a human
   * passed the captcha's test.)
   *
   * @param Array $request The request data. Will default to $_POST if nothing is passed.
   *
   * @return Boolean
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function reCAPTCHA(Array $request=[]):bool {
    $secret = Config::$current->application["recaptcha"]["secret"];
    $response = $request["g-recaptcha-response"] ?? $_POST["g-recaptcha-response"];

    $handle = curl_init();

    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 3);
    curl_setopt($handle, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($handle, CURLOPT_POST, 3);
    curl_setopt($handle, CURLOPT_POSTFIELDS, "secret=$secret&response=$response&remoteip=$_SERVER[REMOTE_ADDR]");

    $response = json_decode(curl_exec($handle), true);

    curl_close($handle);

    return !!$response["success"];
  }

  /**
   * Returns a hash of the given password. PHP's built-in hashing
   * function(s) are preferred, but if they are not available then
   * a third-party phpass library will be used.
   *
   * @param String $password The password to be hashed.
   *
   * @return String The hashed password.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function HashPassword(String $password):String {
    if (function_exists("password_hash")) {
      return password_hash($password, PASSWORD_BCRYPT);
    } else {
      if (!self::$PasswordHash) {
        self::$PasswordHash = new \Hautelook\Phpass\PasswordHash(8, false);
      }
      return self::$PasswordHash->HashPassword($password);
    }
  }

  /**
   * Determines if the given password matches the given password hash. Will
   * return true if they are equal, otherwise false. The plaintext version
   * of the password should never be stored.
   *
   * @param String $password The plaintext version of the password.
   * @param String $hash     The hashed version of the password.
   *
   * @return Boolean
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function CheckPassword(String $password, String $hash):bool {
    if (function_exists("password_hash")) {
      return password_verify($password, $hash);
    } else {
      if (!self::$PasswordHash) {
        self::$PasswordHash = new \Hautelook\Phpass\PasswordHash(8, false);
      }
      return self::$PasswordHash->CheckPassword($password, $hash);
    }
  }

  /**
   * @param String $payload
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Encrypt(String $payload):String {
    $cipher = Config::$current->application["encryption"]["cipher"];
    $key = Config::$current->application["encryption"]["key"];

    $ivsize = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivsize);
    $encrypted = openssl_encrypt($payload, $cipher, $key, 1, $iv);

    return base64_encode("$iv$encrypted");
  }

  /**
   * @param String $payload
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Decrypt(String $payload):String {
    $cipher = Config::$current->application["encryption"]["cipher"];
    $key = Config::$current->application["encryption"]["key"];

    $payload = base64_decode($payload);
    $ivsize = openssl_cipher_iv_length($cipher);
    $iv = mb_substr($payload, 0, $ivsize, "8bit");
    $encrypted = mb_substr($payload, $ivsize, null, "8bit");

    return openssl_decrypt($encrypted, $cipher, $key, 1, $iv);
  }
}