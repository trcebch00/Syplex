<?php

namespace Syplex;

class Error {
  public $name;
  public $message;
  public $fields;
  public $post;

  /**
   * @param String $message The error message.
   * @param Array  $fields  The names of the fields that the error applies to.
   * @param Array  $post    The POST data.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function __construct(String $message, Array $fields=NULL, Array $post=NULL) {
    $this->name = NULL;
    $this->message = $message;
    $this->fields = $fields;
    $this->post = $post;
  }

  /**
   * Commit the error data to the user's session so that it can be passed to the
   * next request and displayed to the user.
   *
   * @author Tyler O'Brien <contact@tylerobrien.com>
   * */
  public function save() {
    $errors = User::Session("Errors");
    
    if (!$errors) {
      $errors = [];
    }

    $errors["{$this->name}Error"] = get_object_vars($this);

    User::Session("Errors", $errors);
  }
}