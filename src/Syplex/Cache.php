<?php

namespace Syplex;

class Cache {
  static public $instance = NULL;
  static public $didCache = false;

  /**
   * Creates an instance of phpFastCache on the first call and returns
   * said instance. Any subequent calls will only return the created
   * instance; nothing else will be created/done.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Prepare() {
    global $paths;

    if (is_null(self::$instance)) {
      \phpFastCache\CacheManager::setDefaultConfig([
        "path" => "$paths[root]/cache/phpfastcache"
      ]);
      self::$instance = \phpFastCache\CacheManager::getInstance(
        Config::$current->application["cache"]["driver"]
      );
    }
    
    return self::$instance;
  }

  /**
   * Returns the given input string as a hashed string in the format that
   * the \Syplex\Cache functions expect.
   *
   * @param String
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function MakeKey(String $data):String {
    return hash("sha256", $data);
  }

  /**
   * Checks if the given key exists in the cache.
   *
   * @param String
   *
   * @return bool
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function IsCached(String $key):bool {
    return self::Prepare()->getItem($key)->isHit();
  }

  /**
   * Retrieves the cached data that is represented by the given
   * key. If no data has been cached with the given key then
   * the passed callback will be called, with the given arguments
   * passed (if any arguments where given) and the return value
   * of the callback is cached and returned.
   *
   * If no expiration duration is given the default defined in the
   * global config file will be used.
   *
   * @param String   $key
   * @param Callable $callback
   * @param Array    $arguments
   * @param int      $expires
   *
   * @return String
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Get(String $key, $callback, Array $arguments=NULL, int $expires=NULL):String {
    global $application;

    if (!Config::$current->application["cache"]["enabled"]) {
      if (is_null($arguments)) {
        return call_user_func($callback);
      } else {
        return call_user_func_array($callback, $arguments);
      }
    } else {
      if (!self::$instance) {
        self::Prepare();
      }

      $item = self::$instance->getItem($key);

      if (!$item->isHit()) {
        if (is_null($arguments)) {
          $pageContent = call_user_func($callback);
        } else {
          $pageContent = call_user_func_array($callback, $arguments);
        }

        $expires = $expires ? $expires : Config::$current->application["cache"]["expire"];

        $item->set($pageContent)->expiresAfter($expires);

        self::$instance->save($item);
        self::$didCache = true;
      }

      return $item->get();
    }
  }

  /**
   * Removes the data represented by the passed key from the cache. If the
   * key does not exist this will do nothing.
   *
   * @param String $key
   *
   * @return void
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function Flush(String $key) {
    self::Prepare()->deleteItem($key);
  }

  /**
   * Removes all data from the cache. If nothing exists in the cache
   * this will do nothing.
   *
   * @return void
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function FlushAll() {
    self::Prepare()->clear();
  }
}