<?php

namespace Syplex;

class Result {
  public $name;
  public $message;
  public $fields;
  public $post;

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(String $message, Array $fields=NULL, Array $post=NULL) {
    $this->name = NULL;
    $this->message = $message;
    $this->fields = $fields;
    $this->post = $post;
  }

  /**
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function save() {
    $results = User::Session("Results");
    
    if (!$results) {
      $results = [];
    }

    $results["{$this->name}Result"] = get_object_vars($this);

    User::Session("Results", $results);
  }
}