<?php

namespace Syplex;

class Depend {
  public $dependencies;
  public $local;
  public $remote;
  public $localRepo;
  public $remoteRepo;

  /**
   * @param String $filename
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function RemoveDirectoryFromPath(String $filename) {
    return preg_replace("@static/[^/]+/@", "", $filename);
  }

  /**
   * @param String $filename
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function RemoveDirectoryAndExtensionFromPath(String $filename) {
    return str_replace(["static/css/", "static/js/", ".css", ".js"], "", $filename);
  }

  /**
   * @param String $directory
   *
   * @return Array
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function LoadLocalRepo(String $directory=""):Array {
    return [
      "js" => self::LoadLocalJSRepo($directory),
      "css" => self::LoadLocalCSSRepo($directory)
    ];
  }

  /**
   * @param String $directory
   *
   * @return Array
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function LoadLocalJSRepo(String $directory=""):Array {
    global $paths;

    $oldCwd = getcwd();

    chdir("$paths[application]");

    $repo = glob("static/js/{$directory}*.{js,JS}", GLOB_NOSORT | GLOB_BRACE);
    $repo = array_combine(
      array_map("Syplex\\Depend::RemoveDirectoryAndExtensionFromPath", $repo),
      array_map("Syplex\\Depend::RemoveDirectoryFromPath", $repo)
    );

    chdir($oldCwd);

    return $repo;
  }

  /**
   * @param String $directory
   *
   * @return Array
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function LoadLocalCSSRepo(String $directory=""):Array {
    global $paths;

    $oldCwd = getcwd();

    chdir("$paths[application]");

    $repo = glob("static/css/{$directory}*.{css,CSS}", GLOB_NOSORT | GLOB_BRACE);
    $repo = array_combine(
      array_map("Syplex\\Depend::RemoveDirectoryAndExtensionFromPath", $repo),
      array_map("Syplex\\Depend::RemoveDirectoryFromPath", $repo)
    );

    chdir($oldCwd);

    return $repo;
  }

  /**
   * @param Array $repo
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(Array $repo=[]) {
    global $paths;

    $this->dependencies = [];
    $this->local = ["css" => [], "js" => []];
    $this->remote = ["css" => [], "js" => []];
    $this->localRepo = $repo["local"] ?? self::LoadLocalRepo();
    $this->remoteRepo = $repo["remote"] ?? Config::$current->cdn;

    if ($jsDirectories = glob("$paths[static]/js/*", GLOB_ONLYDIR | GLOB_NOSORT)) {
      foreach ($jsDirectories as $directory) {
        $this->localRepo["js"] = array_merge(
          $this->localRepo["js"], self::LoadLocalJSRepo(basename($directory) . "/")
        );
      }
    }

    if ($cssDirectories = glob("$paths[static]/css/*", GLOB_ONLYDIR | GLOB_NOSORT)) {
      foreach ($cssDirectories as $directory) {
        $this->localRepo["css"] = array_merge(
          $this->localRepo["css"], self::LoadLocalCSSRepo(basename($directory) . "/")
        );
      }
    }
  }

  /**
   * @return Array
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function getAll():Array {
    return [
      "local" => $this->local,
      "remote" => $this->remote
    ];
  }

  /**
   * @param String $target Whether the source is remote or local.
   * @param String $type   Whether the source is js or css.
   * @param String $name   The name of the source.
   * @param Array  $repo
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function add(String $target, String $type, String $name, Array $repo) {
    if (!array_key_exists($name, $this->{$target}[$type]) && isset($repo[$name])) {
      $lib = $repo[$name];

      if (is_string($lib)) {
        $this->{$target}[$type][$name][] = $lib;
      } else {
        if (array_key_exists("requires", $lib)) {
          if (is_string($lib["requires"])) {
            $this->add($target, $type, $lib["requires"], $repo);
          } else {
            foreach ($lib["requires"] as $required) {
              $this->add($target, $type, $required, $repo);
            }
          }

          if (is_string($lib["source"])) {
            $this->{$target}[$type][$name] = [$lib["source"]];
          } else {
            $this->{$target}[$type][$name] = [];
            foreach ($lib["source"] as $source) {
              $this->{$target}[$type][$name][] = $source;
            }
          }
        } else {
          $this->{$target}[$type][$name] = [];
          foreach ($lib as $lib) {
            $this->{$target}[$type][$name][] = $lib;
          }
        }
      }
    }
  }

  /**
   * @param String $name The name of the source.
   * @param Array  $repo
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addLocal($name, $repo=NULL) {
    if (!$repo) {
      $repo = $this->localRepo;
    }

    $this->addLocalJS($name, $repo["js"]);
    $this->addLocalCSS($name, $repo["css"]);
  }

  /**
   * @param String $name The name of the source.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addLocalJS($name, $repo=NULL) {
    $this->add("local", "js", $name, $repo ? $repo : $this->localRepo["js"]);
  }

  /**
   * @param String $name The name of the source.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addLocalCSS($name, $repo=NULL) {
    $this->add("local", "css", $name, $repo ? $repo : $this->localRepo["css"]);
  }

  /**
   * @param String $name The name of the source.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addRemote($name, $repo=NULL) {
    if (!$repo) {
      $repo = $this->remoteRepo;
    }

    $this->addRemoteJS($name, $repo["js"]);
    $this->addRemoteCSS($name, $repo["css"]);
  }

  /**
   * @param string $name The name of the source.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addRemoteJS($name, $repo=NULL) {
    $this->add("remote", "js", $name, $repo ? $repo : $this->remoteRepo["js"]);
  }

  /**
   * @param string $name The name of the source.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function addRemoteCSS($name, $repo=NULL) {
    $this->add("remote", "css", $name, $repo ? $repo : $this->remoteRepo["css"]);
  }
}