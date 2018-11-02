<?php

namespace Syplex\Router;

class Request {
  static public $current = NULL;

  public $cacheable;
  public $method;
  public $uri;
  public $params;

  /**
   * Ensures that the given URI is always in an expected format. This removes
   * any extraneous information in the URI, such as the domain name or the
   * existence of index.php, and also removes any trailing slashes.
   *
   * This is ensure that the URI will work with a CMS.
   *
   * @param String $uri The URI to be parsed.
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function ParseURI(String $uri):String {
    $request_uri = parse_url($uri, PHP_URL_PATH);
    $request_uri = preg_replace("/^.*\/index\.php/", "", urldecode($request_uri));

    if (!strlen($request_uri)) {
      $request_uri = "/";
    } elseif (strlen($request_uri) > 1 && $request_uri[strlen($request_uri)-1] === "/") {
      $request_uri = substr($request_uri, 0, -1);
    }

    return $request_uri;
  }

  /**
   * @param String $method The HTTP request method (e.g. GET or POST).
   * @param String $uri    The URI for the request.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(String $method, String $uri) {
    $this->cacheable = true;
    $this->method = $method;
    $this->uri = self::ParseURI($uri);
    $this->params = [];
  }
}