<?php

namespace Syplex;

class Database {
  static public $pdoConnections = [];

  /**
   * @param String $dbname
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  static public function MakePDO(String $dbname) {
    $db = Config::$current->sql[$dbname];

    if ($db["type"] === "mssql") {
      $query = "$db[type]:server=$db[host];Database=$db[name]";
    } else {
      $query = "$db[type]:host=$db[host];dbname=$db[name];charset=$db[charset]";
    }

    if (Config::$current->application["debug"]) {
      $pdo = new \DebugBar\DataCollector\PDO\TraceablePDO(
        new \PDO($query, $db["username"], $db["password"])
      );
      $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      Debug::Prepare(["pdo" => $pdo]);
    } else {
      $pdo = new \PDO($query, $db["username"], $db["password"]);
      $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
    }

    return $pdo;
  }

  /**
   * @param String $dbname
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function open(String $dbname) {
    if (!isset(self::$pdoConnections[$dbname])) {
      self::$pdoConnections[$dbname] = self::MakePDO($dbname);
    }
    
    return self::$pdoConnections[$dbname];
  }

  /**
   * @param String $dbname
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function close(String $dbname) {
    self::$pdoConnections[$dbname] = NULL;
  }
}